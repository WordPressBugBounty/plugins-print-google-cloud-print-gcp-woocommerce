<?php

namespace Zprint;

class Plugin {
	public function __construct() {
		new Plugin\Notice();
	}

	public static function getUrl($path, $raw = false) {
		$plugin_data = get_plugin_data(PLUGIN_ROOT_FILE);
		$url = plugins_url($path, PLUGIN_ROOT_FILE);

		if ($raw) {
			return $url;
		}

		return add_query_arg('version', $plugin_data['Version'], $url);
	}
}
