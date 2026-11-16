<?php

namespace Zprint;

use Zprint\Aspect\Page;

$setting_page = new Page('printer setting');
if ( current_user_can( 'edit_others_shop_orders' ) ) {
	$setting_page->setArgument('parent_slug', 'woocommerce');
}
$setting_page
	->setArgument('capability', User::PRINT_MANAGEMENT_CAP_KEY )
	->setLabel('singular_name', __('Print Manager', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
	->scope(function (Page $setting_page) {
		/* Enqueue styles using $hook_suffix — works reliably on multisite
		   subdirectory installs where Page::isRequested() may fail due to
		   home_url() path doubling. */
		add_action('admin_enqueue_scripts', function ( $hook_suffix ) {
			if ( false === strpos( $hook_suffix, 'zp_bce0c' ) ) {
				return;
			}
			wp_enqueue_style('zprint_top_navbar_layout-style', plugins_url('assets/top-navbar-layout.css', PLUGIN_ROOT_FILE), [], PLUGIN_VERSION);
			wp_enqueue_style('zprint-fa', plugins_url('assets/fa/app.css', PLUGIN_ROOT_FILE), [], PLUGIN_VERSION);

			wp_enqueue_style(
				'zprint_setting',
				plugins_url('assets/setting.css', PLUGIN_ROOT_FILE),
				[],
				PLUGIN_VERSION
			);

			wp_enqueue_style(
				'zprint-admin-modern',
				plugins_url('assets/css/print-manager-admin.css', PLUGIN_ROOT_FILE),
				['zprint_top_navbar_layout-style', 'zprint_setting'],
				PLUGIN_VERSION
			);
		});

		call_user_func(include_once 'general.php', $setting_page);
		call_user_func(include_once 'printers.php', $setting_page);
		call_user_func(include_once 'application.php', $setting_page);
		call_user_func(include_once 'setting.php', $setting_page);
		call_user_func(include_once 'addons.php', $setting_page);
		call_user_func(include_once 'support.php', $setting_page);
	});

/* Sibling page (not a TabPage) so it can carry its own capability. */
include_once __DIR__ . '/quick-print.php';

function get_appearance_setting($name)
{
	global $zprint_appearance;
	$allowed_names = [
		'logo',
		'Check Header',
		'Company Name',
		'Company Info',
		'Order Details Header',
		'Footer Information #1',
		'Footer Information #2'
	];

	if (!in_array($name, $allowed_names)) {
		return false;
	}

	$data = $zprint_appearance[$name];

	if ($name === 'logo') {
		if (empty($data)) {
			return false;
		}
		$path = get_attached_file($data);
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		return $base64;
	}
	return $data;
}
