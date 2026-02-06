<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;

class HttpClient
{
	const DEFAULT_TIMEOUT = 10;
	const WP_DEFAULT_TIMEOUT = 5;
	const RETRY_DELAY_MS = 500;

	/**
	 * Get network settings from the General tab.
	 *
	 * @return array
	 */
	public static function getNetworkSettings()
	{
		return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$general = TabPage::get('general');
				$networkBox = Box::get('network settings');

				$customTimeoutInput = Input::get('enable custom timeout');
				$customTimeoutValue = $customTimeoutInput->getValue($networkBox, null, $general);
				$customTimeoutEnabled = is_array($customTimeoutValue) && in_array('1', $customTimeoutValue);

				$timeoutInput = Input::get('timeout seconds');
				$timeoutSeconds = (int) $timeoutInput->getValue($networkBox, null, $general);
				if ($timeoutSeconds < 1) {
					$timeoutSeconds = self::DEFAULT_TIMEOUT;
				}

				$retryInput = Input::get('enable network retry');
				$retryValue = $retryInput->getValue($networkBox, null, $general);
				$retryEnabled = is_array($retryValue) && in_array('1', $retryValue);

				$sslInput = Input::get('disable ssl verify');
				$sslValue = $sslInput->getValue($networkBox, null, $general);
				$sslDisabled = is_array($sslValue) && in_array('1', $sslValue);

				return [
					'custom_timeout_enabled' => $customTimeoutEnabled,
					'timeout_seconds' => $timeoutSeconds,
					'retry_enabled' => $retryEnabled,
					'ssl_verify' => !$sslDisabled,
				];
			});
		});
	}

	/**
	 * Get the HTTP timeout value.
	 *
	 * @param string $method HTTP method (GET or POST)
	 * @param string $url Request URL
	 * @param array|null $data Request data
	 * @return int Timeout in seconds
	 */
	public static function getTimeout($method, $url, $data = null)
	{
		$settings = self::getNetworkSettings();

		if ($settings['custom_timeout_enabled']) {
			$timeout = $settings['timeout_seconds'];
		} else {
			// Use WordPress default
			$timeout = self::WP_DEFAULT_TIMEOUT;
		}

		return apply_filters('bizprint_http_timeout', $timeout, $method, $url, $data);
	}

	/**
	 * Get the SSL verify setting.
	 *
	 * @param string $method HTTP method
	 * @param string $url Request URL
	 * @return bool Whether to verify SSL certificates
	 */
	public static function getSslVerify($method, $url)
	{
		$settings = self::getNetworkSettings();
		$sslVerify = $settings['ssl_verify'];
		return apply_filters('bizprint_http_sslverify', $sslVerify, $method, $url);
	}

	/**
	 * Check if retry is enabled.
	 *
	 * @return bool
	 */
	public static function isRetryEnabled()
	{
		$settings = self::getNetworkSettings();
		return $settings['retry_enabled'];
	}

	/**
	 * Check if an error is retryable (cURL error 7 or 28).
	 *
	 * @param \WP_Error $error
	 * @return bool
	 */
	public static function isRetryableError($error)
	{
		if (!is_wp_error($error)) {
			return false;
		}

		$message = $error->get_error_message();
		return (bool) preg_match('/cURL error (7|28)/i', $message);
	}

	/**
	 * Log an HTTP error with context.
	 *
	 * @param string $method HTTP method
	 * @param string $url Full request URL
	 * @param \WP_Error $error
	 * @param bool $isRetry Whether this was a retry attempt
	 */
	public static function logError($method, $url, $error, $isRetry = false)
	{
		$parsedUrl = parse_url($url);
		$baseUrl = isset($parsedUrl['scheme']) && isset($parsedUrl['host'])
			? $parsedUrl['scheme'] . '://' . $parsedUrl['host']
			: 'unknown';
		$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';

		$errorMessage = $error->get_error_message();
		$retryLabel = $isRetry ? ' (retry attempt)' : '';

		$logParts = [
			'HTTP Request Failed' . $retryLabel,
			'Method: ' . $method,
			'Base URL: ' . $baseUrl,
			'Path: ' . $path,
			'Error: ' . $errorMessage,
		];

		if (preg_match('/cURL error 60/i', $errorMessage)) {
			$logParts[] = 'Hint: SSL certificate error. Check for TLS inspection, proxy, or missing CA certificates in your server environment.';
		}

		Log::error(Log::BASIC, $logParts);
	}

	/**
	 * Log a retry attempt.
	 *
	 * @param string $method HTTP method
	 * @param string $url Request URL
	 */
	public static function logRetry($method, $url)
	{
		$parsedUrl = parse_url($url);
		$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';

		Log::info(Log::BASIC, [
			'HTTP Request Retry',
			'Method: ' . $method,
			'Path: ' . $path,
		]);
	}

	/**
	 * Perform a GET request with timeout, SSL, retry, and logging support.
	 *
	 * @param string $url Full request URL
	 * @param array $args Additional wp_remote_get arguments
	 * @return array|\WP_Error Response or error
	 */
	public static function get($url, $args = [])
	{
		return self::request('GET', $url, $args);
	}

	/**
	 * Perform a POST request with timeout, SSL, retry, and logging support.
	 *
	 * @param string $url Full request URL
	 * @param array $args Additional wp_remote_post arguments
	 * @return array|\WP_Error Response or error
	 */
	public static function post($url, $args = [])
	{
		return self::request('POST', $url, $args);
	}

	/**
	 * Internal request handler with timeout, SSL, retry, and logging.
	 *
	 * @param string $method GET or POST
	 * @param string $url Request URL
	 * @param array $args Request arguments
	 * @return array|\WP_Error Response or error
	 */
	private static function request($method, $url, $args = [])
	{
		$data = isset($args['body']) ? $args['body'] : null;

		$args['timeout'] = self::getTimeout($method, $url, $data);
		$args['sslverify'] = self::getSslVerify($method, $url);

		$func = $method === 'POST' ? 'wp_remote_post' : 'wp_remote_get';
		$result = $func($url, $args);

		if (is_wp_error($result)) {
			$shouldRetry = self::isRetryEnabled() && self::isRetryableError($result);

			self::logError($method, $url, $result, false);

			if ($shouldRetry) {
				self::logRetry($method, $url);
				usleep(self::RETRY_DELAY_MS * 1000);

				$result = $func($url, $args);

				if (is_wp_error($result)) {
					self::logError($method, $url, $result, true);
				}
			}
		}

		return $result;
	}
}
