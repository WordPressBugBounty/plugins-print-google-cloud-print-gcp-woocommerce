<?php

namespace Zprint;

use Zprint\API\AutoPrintSettings;
use Zprint\API\QuickPrint;
use Zprint\API\Webhook;

class API
{
	const API_NAMESPACE = 'zprint';

	private $quickPrint;

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'init']);

		/* QuickPrint also binds non-REST hooks (template_redirect, cleanup scheduler) — instantiate on plugin boot. */
		$this->quickPrint = new QuickPrint(self::API_NAMESPACE . '/v1');
	}

	public function init()
	{
		new Webhook(self::API_NAMESPACE . '/v1');
		new AutoPrintSettings(self::API_NAMESPACE . '/v1');
	}
}
