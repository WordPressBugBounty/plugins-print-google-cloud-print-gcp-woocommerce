<?php

namespace Zprint\Templates\Advanced;

use Zprint\Template\Advanced;
use Zprint\Template\Index;
use Zprint\Template\TemplateSettings;

class ShippingLabel extends Advanced implements Index, TemplateSettings
{
	public function getName(): string
	{
		return __('Shipping Label', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	}

	public function getSlug(): string
	{
		return 'shipping-label';
	}

	public function getTemplateSettings(): array
	{
		return [
			'shipping' => [
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
		return ['html' => true, 'plain' => true];
	}
}
