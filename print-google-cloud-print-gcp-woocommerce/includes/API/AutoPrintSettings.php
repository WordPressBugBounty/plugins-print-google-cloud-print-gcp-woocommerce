<?php

namespace Zprint\API;

use WP_REST_Request;
use WP_REST_Response;
use Zprint\Order;
use Zprint\Printer;

class AutoPrintSettings
{
	public function __construct(string $namespace)
	{
		register_rest_route($namespace, '/auto-print-settings', [
			'methods' => 'GET',
			'callback' => [$this, 'handler'],
			'permission_callback' => function () {
				return current_user_can('access_woocommerce_pos');
			},
		]);
	}

	public function handler(WP_REST_Request $request): WP_REST_Response
	{
		$enabled = Printer::isEnabledPrinting(Printer::POS_PRINT) || Printer::isEnabledPrinting(Printer::WEB_PRINT);
		$statuses = Order::getValidOrderStatusForWebPrinting();

		return new WP_REST_Response([
			'enabled' => $enabled,
			'statuses' => array_values($statuses),
		]);
	}
}
