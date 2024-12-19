<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;

class Client
{
	public static function handleUpdateVersion() {
		if(!empty(Client::getVersion())) return;

		$keys = Client::getAccess();

		if (empty($keys['publicKey']) && empty($keys['secretKey'])) return;

		InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			Page::get('printer setting')->scope(function () {
				$setting = TabPage::get('setting');
				$api_input = Input::get('api version');
				$api_box = Box::get('api keys');

				$option = $api_input->nameInput($setting, $api_box);

				update_option($option, 'v0', true);
			});
		});
	}

	public static function getVersion()
	{
		return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$setting = TabPage::get('setting');
				$api_input = Input::get('api version');
				$api_box = Box::get('api keys');
				return $api_input->getValue($api_box, null, $setting);
			});
		});
	}

	public static function checkGetAccess() {
		switch (Client::getVersion()) {
			case 'v1': {
				return RestClient::checkGetAccess();
			}
			case 'v0':
			default: {
				return LegacyClient::checkGetAccess();
			}
		}
	}

	public static function getAccess($key = null)
	{
		switch (Client::getVersion()) {
			case 'v1': {
				return RestClient::getAccess($key);
			}
			case 'v0':
			default: {
				return LegacyClient::getAccess($key);
			}
		}
	}

	public static function hasAccess()
	{
		switch (Client::getVersion()) {
			case 'v1': {
				return RestClient::hasAccess();
			}
			case 'v0':
			default: {
				return LegacyClient::hasAccess();
			}
		}
	}

	public static function getRequest($url)
	{
		switch (Client::getVersion()) {
			case 'v1': {
				return RestClient::getRequest($url);
			}
			case 'v0':
			default: {
				return LegacyClient::getRequest($url);
			}
		}
	}

	public static function postRequest($url, $data)
	{
		switch (Client::getVersion()) {
			case 'v1': {
				return RestClient::postRequest($url, $data);
			}
			case 'v0':
			default: {
				return LegacyClient::postRequest($url, $data);
			}
		}
	}
}
