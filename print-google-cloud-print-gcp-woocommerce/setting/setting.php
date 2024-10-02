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
};
