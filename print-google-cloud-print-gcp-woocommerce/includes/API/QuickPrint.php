<?php

/*
 * QuickPrint — drag-and-drop file print.
 *
 * Flow:
 *   1. Client POSTs multipart to /zprint/v1/quick-print (nonce + cap + rate-limited).
 *   2. The file is validated (UploadValidator) and stored by QuickPrintStorage.
 *   3. We dispatch a BizPrint job whose `url` is `?zprint_quickprint_file=<id>`.
 *      Like order printing, the url is sent unsigned: BizPrint signs it on every
 *      send (first send, reconnect resend, Reprint) and Client::checkGetAccess()
 *      verifies that signature when the print client fetches it.
 *   4. Files stay fetchable for the retention window, then an age-based sweep
 *      deletes them; serving also refuses anything past retention. Dispatch never
 *      deletes, on success or failure: a print client may fetch the url later and
 *      more than once, and a BizPrint error does not prove no job was created.
 */

namespace Zprint\API;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use Zprint\Client;
use Zprint\Log;
use Zprint\Printer;
use Zprint\Support\QuickPrintSettings;
use Zprint\Support\QuickPrintStorage;
use Zprint\Support\SupportedFormats;
use Zprint\Support\UploadValidator;
use Zprint\User;

class QuickPrint
{
	const NAMESPACE_SUFFIX  = '/quick-print';
	const RATE_LIMIT_WINDOW = 5 * MINUTE_IN_SECONDS;
	const RATE_LIMIT_MAX    = 15;
	const SWEEP_ACTION      = 'zprint_quickprint_sweep';

	private $namespace;

	public function __construct(string $namespace)
	{
		$this->namespace = $namespace;

		add_action('rest_api_init', array($this, 'registerRoutes'));
		add_action('template_redirect', array($this, 'serveFile'));
		add_action(self::SWEEP_ACTION, array($this, 'sweep'));
	}

	public function registerRoutes()
	{
		register_rest_route($this->namespace, self::NAMESPACE_SUFFIX, array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array($this, 'handleSubmit'),
			'permission_callback' => array($this, 'permissions'),
		));
	}

	public function permissions(WP_REST_Request $request)
	{
		if (!User::current_user_can_quick_print()) {
			return new WP_Error(
				'zprint_quick_print_forbidden',
				__('You do not have permission to print files.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 403)
			);
		}

		return true;
	}

	public function handleSubmit(WP_REST_Request $request)
	{
		$user_id = get_current_user_id();
		if (!$this->checkRateLimit($user_id)) {
			return new WP_Error(
				'zprint_quick_print_rate_limited',
				sprintf(
					/* translators: 1: max prints, 2: minutes. */
					__('Rate limit exceeded: %1$d prints per %2$d minutes.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
					self::RATE_LIMIT_MAX,
					(int) (self::RATE_LIMIT_WINDOW / MINUTE_IN_SECONDS)
				),
				array('status' => 429)
			);
		}

		$files = $request->get_file_params();
		if (empty($files['file']) || empty($files['file']['tmp_name'])) {
			return new WP_Error(
				'zprint_quick_print_no_file',
				__('No file was uploaded.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 400)
			);
		}

		$printer_id  = absint($request->get_param('printer_id'));
		$description = sanitize_text_field((string) $request->get_param('description'));
		if ($description === '') {
			$description = sprintf(
				/* translators: %s: file name. */
				__('Quick print: %s', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				(string) $files['file']['name']
			);
		}

		if (!$printer_id) {
			return new WP_Error(
				'zprint_quick_print_invalid_printer',
				__('Choose a destination printer.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 400)
			);
		}

		$available_printers = Printer::getPrinters();
		if (empty($available_printers) || !array_key_exists($printer_id, $available_printers)) {
			return new WP_Error(
				'zprint_quick_print_unknown_printer',
				__('That printer is not available on this site.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 400)
			);
		}

		$max_bytes = QuickPrintSettings::getMaxFileSizeBytes();
		if ((int) $files['file']['size'] > $max_bytes) {
			Log::warn(Log::BASIC, array('QuickPrint', 'rejected (oversize)', 'size=' . (int) $files['file']['size'], 'max=' . $max_bytes));
			return new WP_Error(
				'zprint_quick_print_too_large',
				sprintf(
					/* translators: %d: max size in MB. */
					__('That file is larger than the allowed limit of %d MB.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
					QuickPrintSettings::getMaxFileSizeMB()
				),
				array('status' => 413)
			);
		}

		$filetype = UploadValidator::validate((string) $files['file']['tmp_name'], (string) $files['file']['name']);
		if ($filetype === null) {
			Log::warn(Log::BASIC, array('QuickPrint', 'rejected (unsupported)', 'name=' . sanitize_file_name((string) $files['file']['name'])));
			return new WP_Error(
				'zprint_quick_print_unsupported',
				__('Only PDF and HTML files are accepted.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 415)
			);
		}

		if (!Client::hasAccess()) {
			return new WP_Error(
				'zprint_quick_print_no_api_keys',
				__('BizPrint API keys are not configured.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 500)
			);
		}

		$stored = $this->storeUpload($files['file'], $filetype);
		if (is_wp_error($stored)) {
			return $stored;
		}

		$this->recordRateLimit($user_id);

		$payload = array(
			'printerId'   => $printer_id,
			'url'         => add_query_arg(QuickPrintStorage::QUERY_ARG, $stored['id'], home_url('/')),
			'description' => $description,
			'printOption' => wp_json_encode(array(
				'version' => '1.0',
				'print'   => array(
					'copies' => array('copies' => 1),
				),
			)),
		);

		try {
			$response = Client::postRequest('jobs', $payload);
		} catch (Exception $e) {
			/* A timeout can still have created the job, so the file is kept for the sweep. */
			Log::error(Log::PRINTING, array('QuickPrint', 'dispatch exception', $e->getMessage()));
			return new WP_Error(
				'zprint_quick_print_dispatch_failed',
				$e->getMessage(),
				array('status' => 502)
			);
		}

		if (isset($response->errorCode)) {
			/* Not proof the job was rejected: BizPrint can create and send the job and
			   still return an error from a later step. The sweep removes the file. */
			Log::warn(Log::PRINTING, array('QuickPrint', $response->errorCode, $response->message ?? ''));
			return new WP_Error(
				'zprint_quick_print_api_error',
				isset($response->message) ? (string) $response->message : (string) $response->errorCode,
				array('status' => 502, 'error_code' => $response->errorCode)
			);
		}

		if (!isset($response->job)) {
			Log::warn(Log::PRINTING, array('QuickPrint', 'unexpected response'));
			return new WP_Error(
				'zprint_quick_print_unexpected_response',
				__('Unexpected response from print service.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
				array('status' => 502)
			);
		}

		Log::info(Log::PRINTING, array('QuickPrint', 'dispatched', 'Job ' . ($response->job->id ?? '?'), 'Printer ' . $printer_id));

		return new WP_REST_Response(array(
			'success'    => true,
			'job_id'     => $response->job->id ?? null,
			'printer_id' => $printer_id,
		), 200);
	}

	public function serveFile()
	{
		if (empty($_GET[QuickPrintStorage::QUERY_ARG])) {
			return;
		}

		/* Verifies the signature BizPrint added to the job url, as for order documents. */
		if (!Client::checkGetAccess()) {
			status_header(401);
			echo 'Error: No Access';
			exit();
		}

		$file_id = strtolower((string) wp_unslash($_GET[QuickPrintStorage::QUERY_ARG]));
		$dir     = $this->storageDir();
		$found   = (!is_wp_error($dir) && QuickPrintStorage::isValidId($file_id))
			? QuickPrintStorage::find($dir, $file_id, QuickPrintSettings::getRetentionHours() * HOUR_IN_SECONDS, time())
			: null;
		if (!$found) {
			status_header(404);
			echo 'Error: File not found';
			exit();
		}

		$mime = 'application/octet-stream';
		foreach (SupportedFormats::get() as $entry) {
			if (strtolower($entry['ext']) === $found['ext']) {
				$mime = strtolower($entry['mime']);
				break;
			}
		}

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		nocache_headers();
		status_header(200);
		header('Content-Type: ' . $mime);
		header('Content-Length: ' . filesize($found['path']));
		header('Content-Disposition: inline; filename="quick-print.' . $found['ext'] . '"');
		header('X-Content-Type-Options: nosniff');
		if ($mime === 'text/html') {
			/* Uploaded HTML must never run as this site in a browser. Print clients
			   only render it; scripts and same-origin access are not needed. */
			$csp = (string) apply_filters('zprint_quick_print_html_csp', 'sandbox');
			if ($csp !== '') {
				header('Content-Security-Policy: ' . $csp);
			}
		}

		if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'HEAD') {
			readfile($found['path']);
		}
		exit();
	}

	/* Age-based cleanup. Runs from its scheduled event and on every upload, so a
	   missed or lost event only delays deletion until the next one. */
	public function sweep()
	{
		$dir = $this->storageDir();
		if (is_wp_error($dir)) {
			return;
		}

		$retention = QuickPrintSettings::getRetentionHours() * HOUR_IN_SECONDS;
		QuickPrintStorage::sweep($dir, $retention, time());
		$this->scheduleSweep(QuickPrintStorage::nextExpiry($dir, $retention));
	}

	private function storageDir()
	{
		$upload_dir = wp_upload_dir(null, false);
		if (!empty($upload_dir['error'])) {
			return new WP_Error('zprint_quick_print_upload_dir', $upload_dir['error'], array('status' => 500));
		}
		return QuickPrintStorage::baseDir($upload_dir);
	}

	private function storeUpload(array $file, array $filetype)
	{
		$dir = $this->storageDir();
		if (is_wp_error($dir)) {
			return $dir;
		}

		$this->sweep();

		$id   = QuickPrintStorage::newId();
		$path = QuickPrintStorage::store($dir, $id, (string) $file['tmp_name'], $filetype['ext'], 'move_uploaded_file');
		if ($path === null) {
			return new WP_Error('zprint_quick_print_move', __('Could not save uploaded file.', 'Print-Google-Cloud-Print-GCP-WooCommerce'), array('status' => 500));
		}

		/* Scheduled before dispatch, so an interrupted request still gets cleaned up. */
		$this->scheduleSweep(time() + QuickPrintSettings::getRetentionHours() * HOUR_IN_SECONDS);

		return array('id' => $id, 'path' => $path);
	}

	/* Keeps one pending sweep, at the earliest time anything expires. */
	private function scheduleSweep(?int $at): void
	{
		if ($at === null) {
			return;
		}
		$at = max($at, time() + MINUTE_IN_SECONDS);

		$next = wp_next_scheduled(self::SWEEP_ACTION);
		if ($next !== false && $next <= $at) {
			return;
		}
		if ($next !== false) {
			wp_unschedule_event($next, self::SWEEP_ACTION);
		}
		wp_schedule_single_event($at, self::SWEEP_ACTION);
	}

	private function checkRateLimit(int $user_id): bool
	{
		$key     = 'zprint_qp_rate_' . $user_id;
		$history = get_transient($key);
		if (!is_array($history)) {
			$history = array();
		}

		$now    = time();
		$cutoff = $now - self::RATE_LIMIT_WINDOW;
		$history = array_values(array_filter($history, function ($ts) use ($cutoff) {
			return (int) $ts >= $cutoff;
		}));

		return count($history) < self::RATE_LIMIT_MAX;
	}

	private function recordRateLimit(int $user_id): void
	{
		$key     = 'zprint_qp_rate_' . $user_id;
		$history = get_transient($key);
		if (!is_array($history)) {
			$history = array();
		}

		$history[] = time();
		$cutoff    = time() - self::RATE_LIMIT_WINDOW;
		$history   = array_values(array_filter($history, function ($ts) use ($cutoff) {
			return (int) $ts >= $cutoff;
		}));

		set_transient($key, $history, self::RATE_LIMIT_WINDOW);
	}
}
