<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\Page;

return function (Page $setting_page) {
    $application = new TabPage('application');
    $application
        ->setLabel(
            'singular_name',
            __('Application', 'Print-Google-Cloud-Print-GCP-WooCommerce')
        )
        ->attachTo($setting_page);

	$keys_title = '<span class="zprint-setting-title">';
	$keys_title .= '<span class="zprint-setting-title__text">' . __('Application API Keys', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</span>';
	$keys_title .= '<a class="zprint-setting-title__link" href="https://print.bizswoop.app/applications" target="_blank">';
	$keys_title .= __('Application account settings', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	$keys_title .= '<span class="fas fa-external-link"></span></a></span>';

    $keys = new Box('api keys');
    $keys->setLabel('singular_name', $keys_title)
		->attachTo($application);

    $public = new Input('public key');
    $secret = new Input('secret key');
    $secret->setType(Input::TYPE_PASSWORD);

    $keys->attach($public);
    $keys->attach($secret);

	$apiVersion = new Input('api version');
	$apiVersion
		->setLabel('singular_name', 'API Version')
		->setType(Input::TYPE_API_SELECTOR)
		->attachTo($keys);

    $webhookUrl = new Input('webhook url');
    $webhookUrl
        ->setType(Input::TYPE_INFO)
        ->setArgument('content', function () {
            echo '<input type="text" class="large-text code" value="' .
                get_rest_url(null, '/zprint/v1/webhook') .
                '" onfocus="navigator.clipboard.writeText(this.value).then(function(){alert(\'Copied\')});this.blur()"/><br/>Only for <b>REST API</b> version';
        })
        ->attachTo($keys);
};
