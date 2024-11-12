<?php

namespace Zprint\Templates\Advanced;

use Zprint\Template\Advanced;
use Zprint\Template\Index;
use Zprint\Template\TemplateSettings;

class PackingSlip extends Advanced implements Index, TemplateSettings
{
	public function getName(): string
	{
		return __('Packing Slip', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	}

	public function getSlug(): string
	{
		return 'packing-slip';
	}

	public function getTemplateSettings(): array
	{
		return [
			'shipping' => [
				'method' => true,
				'delivery_pickup_type' => defined('\ZZHoursDelivery\ACTIVE') || defined('\ZOrder_Manager\ACTIVE')
			],
		];
	}

	public function getFormats(): array
	{
		return ['html' => true, 'plain' => false];
	}
}
