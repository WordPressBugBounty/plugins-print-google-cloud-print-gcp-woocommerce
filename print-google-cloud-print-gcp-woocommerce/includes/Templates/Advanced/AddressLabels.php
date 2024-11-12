<?php

namespace Zprint\Templates\Advanced;

use Zprint\Template\Advanced;
use Zprint\Template\Index;
use Zprint\Template\TemplateSettings;

class AddressLabels extends Advanced implements Index, TemplateSettings
{
	public function getName(): string
	{
		return __('Address Labels', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	}

	public function getSlug(): string
	{
		return 'address-labels';
	}

	public function getTemplateSettings(): array
	{
		return [
			'shipping' => [
				'address_label_type' => 'shipping',
			]
		];
	}

	public function getFormats(): array
	{
		return ['html' => true, 'plain' => true];
	}
}
