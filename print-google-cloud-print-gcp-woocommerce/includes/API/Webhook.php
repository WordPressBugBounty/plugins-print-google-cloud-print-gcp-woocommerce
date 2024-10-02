<?php

namespace Zprint\API;

use Zprint\Client;
use Zprint\Log;

class Webhook
{
	private $namespace;

	public function __construct($namespace)
	{
		register_rest_route($namespace, '/webhook', [
			'methods' => 'POST',
			'callback' => [$this, 'handler'],
			'permission_callback' => '__return_true',
		]);
	}

	public function verifyRequest(\WP_REST_Request $request)
	{
		$time = $request->get_param('time');
		if (time() > $time + 60 * 5) {
			Log::info(Log::PRINTING, [
				'Webhook-Error-Connect', 'Old request'
			]);
			return new \WP_REST_Response(
				['errorCode' => 'ERR_TOO_OLD_REQUEST', 'message' => 'too old request'],
				400
			);
		}

		$body = json_decode($request->get_body(), JSON_OBJECT_AS_ARRAY);
		unset($body['hash']);

		$publicKey = Client::getAccess('publicKey');
		$secretKey = Client::getAccess('secretKey');

		$hash = hash('sha256', json_encode($body, JSON_UNESCAPED_SLASHES) . ':' . $secretKey);

		if ($request->get_param('hash') !== $hash || $request->get_param('publicKey') !== $publicKey) {
			Log::info(Log::PRINTING, [
				'Webhook-Error-Connect', 'Incorrect auth data'
			]);
			return new \WP_REST_Response(
				['errorCode' => 'ERR_UNAUTHORIZED', 'message' => 'Unauthorized'],
				401
			);
		}
	}

	public function handler(\WP_REST_Request $request)
	{
		$validationResponse = $this->verifyRequest($request);
		if($validationResponse) return $validationResponse;

		$type = $request->get_param('type');
		$id = $request->get_param('_id');

		$transient = 'zprint_webhook_request_' . $id;

		if (get_transient($transient)) {
			return $this->alreadyProcessedHandler($request);
		}
		set_transient($transient, time());

		switch ($type) {
			case 'test-connect':
				return $this->testConnectHandler($request);
			case 'print-job-status-update':
				return $this->printJobStatusUpdateHandler($request);
			default:
				return $this->defaultHandler($request);
		}
	}

	public function testConnectHandler($request)
	{
		Log::info(Log::PRINTING, [
			'Test-Connect'
		]);
		return new \WP_REST_Response([
			'received' => true
		]);
	}

	public function printJobStatusUpdateHandler(\WP_REST_Request $request)
	{
		$job = (object) $request->get_param('job');
		Log::info(Log::PRINTING, [
			$job->description,
			'update to ' . $job->status,
			'Job ' . $job->id,
		]);
		return new \WP_REST_Response(['received' => true], 200);
	}

	public function defaultHandler($request)
	{
		return new \WP_REST_Response('Unprocessable Type', 422);
	}

	public function alreadyProcessedHandler($request)
	{
		return new \WP_REST_Response('Already Processed', 200);
	}
}
