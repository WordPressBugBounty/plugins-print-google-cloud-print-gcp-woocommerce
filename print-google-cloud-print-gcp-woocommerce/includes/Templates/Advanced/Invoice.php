<?php

namespace Zprint\Templates\Advanced;

use Zprint\Template\Advanced;
use Zprint\Template\Index;
use Zprint\Template\TemplateSettings;

class Invoice extends Advanced implements Index, TemplateSettings
{
	public function getName(): string
	{
		return __('Invoice', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	}

	public function getSlug(): string
	{
		return 'invoice';
	}

	public function getTemplateSettings(): array
	{
		return [
			'shipping' => [
				'cost' => true,
				'method' => true,
				'delivery_pickup_type' => defined('\ZZHoursDelivery\ACTIVE') || defined('\ZOrder_Manager\ACTIVE')
			],
			'total' => apply_filters(
				'Zprint\Templates\settingTotal',
				[
					'cost' => true
				],
				$this->getSlug()
			)
		];
	}

	public function getFormats(): array
	{
		return ['html' => true, 'plain' => false];
	}
}
