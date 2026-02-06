<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;

class LegacyClient
{
	public static function getAccess($key = null)
	{
		if (!in_array($key, [null, 'publicKey', 'secretKey'])) {
			throw new \Exception('Wrong key argument');
		}

		$data =  InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$setting = TabPage::get('setting');
				$public_input = Input::get('public key');
				$secret_input = Input::get('secret key');
				$api_box = Box::get('api keys');
				$publicKey = $public_input->getValue($api_box, null, $setting);
				$secretKey = $secret_input->getValue($api_box, null, $setting);
				return compact('publicKey', 'secretKey');
			});
		});

		if($key === null) {
			return $data;
		} else {
			return $data[$key];
		}
	}

	public static function hasAccess()
	{
		$data = self::getAccess();
		$publicKey = $data['publicKey'];
		$secretKey = $data['secretKey'];
		return $publicKey && $secretKey;
	}

	public static function checkGetAccess()
	{
		$keys = self::getAccess();

		if( !isset($_GET['publicKey'], $_GET['secretKey'])) return false;

		return $keys['publicKey'] === $_GET['publicKey'] &&
			$keys['secretKey'] === $_GET['secretKey'] &&
			$keys['publicKey'] !== '' &&
			$keys['secretKey'] !== '';
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
		$url .= '/api/connect-application';
		return $url;
	}

	public static function getRequest($url)
	{
		$base = self::getBaseUrl();

		$result = HttpClient::get($base . '/' . $url, [
			'headers' => ['Authorization' => 'Basic ' . self::getAuthHeader()],
		]);
		if (is_wp_error($result)) {
			throw new \Exception($result->get_error_message());
		}

		if ($result['headers']['content-type'] === 'application/json; charset=utf-8') {
			return json_decode($result['body']);
		} else {
			return $result['body'];
		}
	}

	public static function getAuthHeader()
	{
		$data = self::getAccess();
		$publicKey = $data['publicKey'];
		$secretKey = $data['secretKey'];
		return base64_encode($publicKey . ':' . $secretKey);
	}

	public static function postRequest($url, $data)
	{
		$base = self::getBaseUrl();

		$result = HttpClient::post($base . '/' . $url, [
			'body' => json_encode($data),
			'headers' => [
				'Authorization' => 'Basic ' . self::getAuthHeader(),
				'Content-Type' => 'application/json; charset=utf-8',
			],
			'data_format' => 'body',
		]);

		if (is_wp_error($result)) {
			throw new \Exception($result->get_error_message());
		}

		if ($result['headers']['content-type'] === 'application/json; charset=utf-8') {
			return json_decode($result['body']);
		} else {
			return $result['body'];
		}
	}
}
