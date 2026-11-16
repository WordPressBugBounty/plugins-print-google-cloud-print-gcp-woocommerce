<?php

namespace Zprint\Admin;

use Zprint\Log;
use Zprint\User;
use Zprint\Support\AdminRequest;

defined('ABSPATH') or die('No script kiddies please!');

/*
 * Support tab "View log" and "Copy log". Log files are not web-readable, so
 * both go through WordPress and are limited to Print Manager managers.
 */
class LogAccess
{
	const NONCE = 'zprint_log_access';
	const VIEW_ACTION = 'zprint_view_log';

	public function __construct()
	{
		add_action('wp_ajax_zprint_copy_log', [$this, 'copyLog']);
		add_action('admin_post_' . self::VIEW_ACTION, [$this, 'viewLog']);
		/*
		 * Merges logs left by older versions. Only on admin page views (GET, not
		 * AJAX), so saving an order, which may print, never waits for it.
		 */
		add_action('admin_init', function () {
			if (!wp_doing_ajax() && 'GET' === ($_SERVER['REQUEST_METHOD'] ?? '')) {
				Log::secureStorage();
			}
		});
	}

	public static function viewUrl(string $type): string
	{
		return add_query_arg(
			[
				'action'   => self::VIEW_ACTION,
				'type'     => $type,
				'_wpnonce' => wp_create_nonce(self::NONCE),
			],
			admin_url('admin-post.php')
		);
	}

	public function copyLog(): void
	{
		AdminRequest::authorize(self::NONCE);

		$path = self::path(isset($_POST['type']) ? (string) wp_unslash($_POST['type']) : '');

		echo $path && is_file($path) ? file_get_contents($path) : '';

		wp_die();
	}

	public function viewLog(): void
	{
		$nonce = isset($_GET['_wpnonce']) ? (string) wp_unslash($_GET['_wpnonce']) : '';
		if (!current_user_can(User::PRINT_MANAGEMENT_CAP_KEY) || !wp_verify_nonce($nonce, self::NONCE)) {
			wp_die('', '', ['response' => 403]);
		}

		$path = self::path(isset($_GET['type']) ? (string) wp_unslash($_GET['type']) : '');

		if (!headers_sent()) {
			nocache_headers();
			header('Content-Type: text/plain; charset=utf-8');
			header('X-Content-Type-Options: nosniff');
		}

		if ($path && is_file($path)) {
			readfile($path);
		}
		/* admin-post.php ends the request after this action runs. */
	}

	private static function path(string $type): string
	{
		switch ($type) {
			case Log::BASIC:
				return Log::getBasicLogFilePath();
			case Log::PRINTING:
				return Log::getPrintLogFilePath();
			default:
				return '';
		}
	}
}
