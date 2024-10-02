<?php

namespace Zprint;

use Zprint\API\Webhook;

class API
{
	const API_NAMESPACE = 'zprint';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'init']);
	}

	public function init()
	{
		new Webhook(self::API_NAMESPACE . '/v1');
	}
}
