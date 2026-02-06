<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;

class RestClient
{
	public static function getAccess($key = null)
	{
		if (!in_array($key, [null, 'publicKey', 'secretKey'])) {
			throw new \Exception('Wrong key argument');
		}

		return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () use ($key) {
			return Page::get('printer setting')->scope(function () use ($key) {
				$setting = TabPage::get('setting');
				$api_box = Box::get('api keys');

				if ($key === null || $key === 'publicKey') {
					$public_input = Input::get('public key');
					$publicKey = $public_input->getValue($api_box, null, $setting);
					if ($key !== null) {
						return $publicKey;
					}
				}

				if ($key === null || $key === 'secretKey') {
					$secret_input = Input::get('secret key');
					$secretKey = $secret_input->getValue($api_box, null, $setting);
					if ($key !== null) {
						return $secretKey;
					}
				}

				return compact('publicKey', 'secretKey');
			});
		});
	}

	public static function hasAccess()
	{
		$data = self::getAccess();
		$publicKey = $data['publicKey'];
		$secretKey = $data['secretKey'];
		return $publicKey && $secretKey;
	}

	public static function filterQueryParams($queryParams)
	{
		$allowedParams = ['zprint_order', 'zprint_location', 'zprint_order_user', 'publicKey', 'time'];

		parse_str($queryParams, $params);

		$filteredParams = array_filter($params, function($key) use ($allowedParams) {
			return in_array($key, $allowedParams);
		}, ARRAY_FILTER_USE_KEY);

		return http_build_query($filteredParams);
	}

	public static function checkGetAccess()
	{
		if(!isset($_GET['publicKey']) || !isset($_GET['hash'])) return false;

		$time = +$_GET['time'];
		$serverTime = time();
		if ($serverTime - $time > 60 * 10) {
			return false;
		}

		$keys = self::getAccess();
		if ($keys['publicKey'] !== $_GET['publicKey'] || $keys['publicKey'] === '') {
			return false;
		}

		 $currentQueryArgs = self::getCurrentQueryArgs();
         $baseQuery = self::filterQueryParams($currentQueryArgs);

         $validateHash = hash('sha256', $baseQuery . ':' . $keys['secretKey']);
         $hash = $_GET['hash'];

		if ($validateHash !== $hash) {
			return false;
		}

		return true;
	}

	private static function getCurrentQueryArgs()
	{
		list($_, $queryPart) = array_pad(explode('?', $_SERVER['REQUEST_URI']), 2, '');
		return $queryPart;
	}

	private static function removeQueryArg($queryPart, $varname)
	{
		parse_str($queryPart, $qsvars);
		unset($qsvars[$varname]);
		$newQueryPart = http_build_query($qsvars);
		return $newQueryPart;
	}

	private static function getBaseUrl()
	{
		$url = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$setting = TabPage::get('general');
				$public_input = Input::get('print server');
				$box = Box::get('');
				return $public_input->getValue($box, null, $setting);
			});
		});
		$url .= '/api/connect-application/v1';
		return $url;
	}

	public static function getRequest($url, $allResponse = false)
	{
		$base = self::getBaseUrl();
		$access = self::getAccess();
		$publicKey = $access['publicKey'];
		$secretKey = $access['secretKey'];

		$requestUrl = $base . '/' . $url;
		$args = ['time' => time(), 'publicKey' => $publicKey];
		$baseArgsUrl = add_query_arg($args, $requestUrl);
		$baseArgForHash = str_replace('/?', '', add_query_arg($args, '/'));

		$hash = hash('sha256', $baseArgForHash . ':' . $secretKey);
		$hashedUrl = add_query_arg('hash', $hash, $baseArgsUrl);

		$headers = self::applyHeadersForUpdatesService();
		$result = HttpClient::get($hashedUrl, [
			'headers' => $headers,
		]);

		if (is_wp_error($result)) {
			throw new \Exception($result->get_error_message());
		}

		if($allResponse) return $result;

		if ($result['headers']['content-type'] === 'application/json; charset=utf-8') {
			return json_decode($result['body']);
		} else {
			return $result['body'];
		}
	}

	public static function postRequest($url, $data, $allResponse = false)
	{
		$base = self::getBaseUrl();
		$access = self::getAccess();
		$data['publicKey'] = $access['publicKey'];
		$data['time'] = time();

		$data['hash'] = hash(
			'sha256',
			json_encode($data, JSON_UNESCAPED_SLASHES) . ':' . $access['secretKey']
		);

		$headers = self::applyHeadersForUpdatesService([
			'Content-Type' => 'application/json; charset=utf-8',
		]);

		$result = HttpClient::post($base . '/' . $url, [
			'body' => json_encode($data),
			'headers' => $headers,
			'data_format' => 'body',
		]);

		if (is_wp_error($result)) {
			throw new \Exception($result->get_error_message());
		}

		if($allResponse) return $result;

		if ($result['headers']['content-type'] === 'application/json; charset=utf-8') {
			return json_decode($result['body']);
		} else {
			return $result['body'];
		}
	}

	private static function applyHeadersForUpdatesService($headers = [])
	{
		if (Client::getVersion() === 'v0') {
			$headers['Update-Application-Version'] = '1';
			$headers['Update-Application-WebhookUrl'] = get_rest_url(null, '/zprint/v1/webhook');
		}

		return $headers;
	}
}
