<?php

namespace Zprint\Debug;

use Zprint\Log;
use Zprint\Client;
use Zprint\Document;
use Zprint\Model\Location;
use Zprint\Exception\DB as DBException;
use Zprint\Support\AdminRequest;

defined('ABSPATH') or die('No script kiddies please!');

class Core
{
	const SUCCESS = 'SUCCESS';
	const ERROR = 'ERROR';
	const EXCEPTION = 'EXCEPTION';

	const ACTION_GET_PRINTERS = 'zprint_debug_get_printers';
	const ACTION_SEND_PRINT = 'zprint_debug_send_print_request';
	const NONCE = 'zprint_debug';

	public function __construct()
	{
		/* Support tab tools: Print Manager managers only, never public. */
		add_action('wp_ajax_' . self::ACTION_GET_PRINTERS, [$this, 'getPrinters']);
		add_action('wp_ajax_' . self::ACTION_SEND_PRINT, [$this, 'sendPrintRequest']);
	}

	public function getPrinters()
	{
		AdminRequest::authorize(self::NONCE);

		try {
			$response = Client::getRequest('printers');
		} catch (\Exception $exception) {
			Log::debug(Log::BASIC, [self::EXCEPTION, __METHOD__, $exception->getCode(), $exception->getMessage()]);

			wp_send_json_success($exception);

			die();
		}

		Log::debug(Log::BASIC, [self::SUCCESS, __METHOD__]);

		wp_send_json_success($response);
	}

	public function sendPrintRequest()
	{
		AdminRequest::authorize(self::NONCE);

		$data        = isset($_POST['data']) && is_array($_POST['data']) ? $_POST['data'] : [];
		$order_id    = absint($data['order_id'] ?? 0);
		$location_id = absint($data['location_id'] ?? 0);

		$order = $order_id ? wc_get_order($order_id) : false;
		if (!$order) {
			wp_send_json_error(['code' => 'invalid_order'], 400);
		}
		/* Location(0) builds an empty, unsaved location instead of failing. */
		if (!$location_id) {
			wp_send_json_error(['code' => 'invalid_location'], 400);
		}

		try {
			$location = new Location($location_id);
		} catch (DBException $exception) {
			wp_send_json_error($exception->getMessage());

			die();
		}

		$template_data = $location->getData();

		$multipart = [
			'description' => 'Order ' . $order_id,
			'url' => add_query_arg(
				[
					'zprint_order' => $order_id,
					'zprint_location' => $template_data['id'],
					'zprint_order_user' => $order->get_user_id(),
				],
				home_url()
			),
			'printOption' => Document::getTicket($template_data),
			'is_debug' => true
		];

		$printers_responses = [];
		$printers_responses_exceptions = [];
		foreach ($template_data['printers'] as $printer) {
			$multipart['printerId'] = $printer;

			try {
				$printers_responses[] = Client::postRequest('jobs', $multipart);
			} catch (\Exception $e) {
				Log::debug(Log::BASIC, [self::EXCEPTION, $e->getCode(), $e->getMessage()]);

				$printers_responses_exceptions = [
					'printerId'			=> $printer,
					'exceptionsMessage' => $e->getMessage()
				];
			}
		}

		$printers_success = array_map(function ($response) {
			if ($response->success) {
				$job = $response->job;

				if ($job) {
					Log::debug(Log::BASIC, [self::SUCCESS, $response->message, $job->title, $job->status, $job->id]);
				} else {
					Log::debug(Log::BASIC, [self::SUCCESS, $response->message]);
				}
			}
			if ($response->errorCode) {
				Log::debug(Log::BASIC, [self::ERROR, $response->errorCode, $response->message]);
			}

			return $response;
		}, $printers_responses);

		$printers_success = array_filter($printers_success);

		$response = [
			'successResponses' => $printers_success,
			'exceptionResponses' => $printers_responses_exceptions
		];

		wp_send_json_success($response);
	}
}
