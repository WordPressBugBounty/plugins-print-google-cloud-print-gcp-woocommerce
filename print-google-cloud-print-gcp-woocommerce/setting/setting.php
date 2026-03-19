<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\Page;

return function (Page $setting_page) {
	$setting = new SettingsTabPage('setting');
	$setting
		->setLabel('singular_name', __('Settings', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($setting_page);

	$roles = wp_roles()->role_names;
	$roles = array_map(function ($value, $key) {
		return [$key, $value];
	}, array_values($roles), array_keys($roles));

	$locationUserRoles = new Box('location user roles');
	$locationUserRoles
		->setLabel('singular_name', __('Location User Roles', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setArgument('description', __('Select Roles for Location Mapping to Users', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($setting);

	$locationRolesInput = new Input('roles');
	$locationRolesInput
		->attachTo($locationUserRoles)
		->setLabel('singular_name', __('Selected Roles', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setArgument('default', ['scalar' => ['administrator', 'shop_manager']])
		->setType(Input::TYPE_CHECKBOX)
		->attachFew($roles);

	$accessUserRoles = new Box('access user roles');
	$accessUserRoles
		->setLabel('singular_name', __('Management Access User Roles', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setArgument('description', __('Select Roles for Access to Manage Print Settings', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($setting);

	$accessRolesInput = new Input('access roles');
	$accessRolesInput
		->attachTo($accessUserRoles)
		->setLabel('singular_name', __('Selected Roles', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setArgument('default', ['scalar' => ['administrator']])
		->setType(Input::TYPE_CHECKBOX_EXTENDED)
		->setArgument('disabled_items', [true])
		->attachFew($roles);

	$networkBox = new Box('network settings');
	$networkBox->setLabel(
		'singular_name',
		__('Network Settings', 'Print-Google-Cloud-Print-GCP-WooCommerce')
	)->attachTo($setting);

	$enableCustomTimeout = new Input('enable custom timeout');
	$enableCustomTimeout
		->setLabel(
			'singular_name',
			__('Custom Request Timeout', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Override the default WordPress HTTP timeout (5 seconds) for print service requests.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$timeoutValue = new Input('timeout seconds');
	$timeoutValue
		->setLabel(
			'singular_name',
			__('Timeout (seconds)', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('default', '10')
		->setArgument('description', __('Number of seconds to wait for print service response. Recommended: 10-30 seconds.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_NUMBER);

	$enableRetry = new Input('enable network retry');
	$enableRetry
		->setLabel(
			'singular_name',
			__('Enable Network Retry', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Retry failed print requests once on connection timeout or network errors. Note: May cause duplicate prints if the server received the original request.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$disableSslVerify = new Input('disable ssl verify');
	$disableSslVerify
		->setLabel(
			'singular_name',
			__('Disable SSL Verification', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Disable SSL certificate verification for print service requests. Only enable if you have TLS inspection or custom certificate authorities in your network.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Disable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$enableAsyncPrinting = new Input('enable async printing');
	$enableAsyncPrinting
		->setLabel(
			'singular_name',
			__('Background Print Processing', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Process print jobs in the background instead of during checkout. Checkout completes immediately and print jobs are sent asynchronously. Recommended for sites experiencing print service timeouts.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$enableFallbackQueue = new Input('enable fallback queue');
	$enableFallbackQueue
		->setLabel(
			'singular_name',
			__('Queue Failed Jobs', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('When a print job fails during checkout, automatically queue it for background retry. Jobs will retry with increasing delays up to 5 times. Only applies when Background Print Processing is disabled.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
};
