<?php

namespace Zprint\Templates;

use Zprint\Template\Basic;
use Zprint\Template\Index;
use Zprint\Template\TemplateSettings;

class Customer extends Basic implements Index, TemplateSettings
{
	public function getName()
	{
		return __('Customer Receipt', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	}

	public function getSlug()
	{
		return 'customer';
	}

	public function getTemplateSettings()
	{
		return [
			'shipping' => [
				'cost' => true,
				'customer_details' => true,
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
}
