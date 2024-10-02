<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\Page;

return function (Page $setting_page) {
	$addons = new TabPage('addons');
	$addons
		->setLabel('singular_name', __('Marketplace', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($setting_page)
		->setArgument('hideForm', true)
		->setArgument('contentPage', function () {
			wp_enqueue_style('zprint_addons-style', plugins_url('assets/addons.css', PLUGIN_ROOT_FILE), [], PLUGIN_VERSION);

			Addons::render();
		});

	return $addons;
};
