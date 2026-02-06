<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\Page;

return function (Page $setting_page) {
	$general = new TabPage('general');
	$general
		->setLabel('singular_name', __('General', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($setting_page)
		->setArgument('contentPage', function () {
			?>
			<div class="zprint-connection-box">
				<a class="zprint-connection-card" href="https://print.bizswoop.app/" target="_blank">
									<span class="zprint-connection-card__header">
										<span class="zprint-connection-card__icon fal fa-draw-circle"></span>
										<span class="zprint-connection-card__title">
												<?= esc_html__('Dashboard', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
										</span>
									</span>
					<span class="zprint-connection-card__btn">
										<?= esc_html__('Open', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
									</span>
				</a>
				<a class="zprint-connection-card" href="https://getbizprint.com/documentation/" target="_blank">
									<span class="zprint-connection-card__header">
										<span class="zprint-connection-card__icon fal fa-shapes"></span>
										<span class="zprint-connection-card__title">
												<?= esc_html__('Documentation', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
										</span>
									</span>
					<span class="zprint-connection-card__btn">
										<?= esc_html__('View', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
									</span>
				</a>
				<a class="zprint-connection-card" href="https://getbizprint.com/quick-start-guide/" target="_blank">
									<span class="zprint-connection-card__header">
										<span class="zprint-connection-card__icon fal fa-rocket-launch"></span>
										<span class="zprint-connection-card__title">
												<?= esc_html__('Quick Start Guide', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
										</span>
									</span>
					<span class="zprint-connection-card__btn">
										<?= esc_html__('Launch', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
									</span>
				</a>
			</div>
			<?php
		});

	$aop = new Box('automatic order printing');
	$aop->setLabel(
		'singular_name',
		__('Automatic Order Printing', 'Print-Google-Cloud-Print-GCP-WooCommerce')
	)->attachTo($general);

	$enable_aop = new Input('enable automatic printing');
	$enable_aop
		->setLabel(
			'singular_name',
			__('Enable Automatic Printing', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($aop)
		->setType(Input::TYPE_CHECKBOX);

	if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE) {
		$enable_aop
			->attach(['web', __('Website Orders', 'Print-Google-Cloud-Print-GCP-WooCommerce')])
			->attach(['pos', __('Point of Sale Orders', 'Print-Google-Cloud-Print-GCP-WooCommerce')])
			->attach([
				'order_only',
				__('Orders Saved in Point of Sale', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
			]);
	} else {
		$enable_aop->attach(['web', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
	}

	$web_auto_statuses = new Input('web orders automatic print statuses');

	if (defined('\ZPOS\ACTIVE') && \ZPOS\ACTIVE) {
		$web_auto_statuses->setLabel(
			'singular_name',
			__('Automatically Printed Website Order Statuses', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		);
	} else {
		$web_auto_statuses->setLabel(
			'singular_name',
			__('Automatically Printed Order Statuses', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		);
	}

	$statuses = wc_get_order_statuses();

	$web_auto_statuses
		->attachTo($aop)
		->setArgument('default', ['scalar' => ['pending', 'processing']])
		->setArgument('multiply')
		->setArgument('divider', '<br/>')
		->setType(Input::TYPE_CHECKBOX);

	foreach ($statuses as $status_code => $status) {
		$status_code = str_replace('wc-', '', $status_code);
		$web_auto_statuses->attach([$status_code, $status]);
	}

	$copies = new Input('copies');
	$copies
		->setLabel('singular_name', __('Copies', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($aop)
		->setArgument('default', '1')
		->setType(Input::TYPE_NUMBER);

	$printBox = new Box('');
	$printBox->attachTo($general);

	$printServer = new Input('print server');
	$printServer
		->attachTo($printBox)
		->setArgument('default', 'https://print.bizswoop.app')
		->setType(Input::TYPE_SECRET_INPUT);

	$networkBox = new Box('network settings');
	$networkBox->setLabel(
		'singular_name',
		__('Network Settings', 'Print-Google-Cloud-Print-GCP-WooCommerce')
	)->attachTo($general);

	$enableCustomTimeout = new Input('enable custom timeout');
	$enableCustomTimeout
		->setLabel(
			'singular_name',
			__('Custom Request Timeout', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Override the default WordPress HTTP timeout (5 seconds) for print service requests.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$timeoutValue = new Input('timeout seconds');
	$timeoutValue
		->setLabel(
			'singular_name',
			__('Timeout (seconds)', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('default', '10')
		->setArgument('description', __('Number of seconds to wait for print service response. Recommended: 10-30 seconds.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_NUMBER);

	$enableRetry = new Input('enable network retry');
	$enableRetry
		->setLabel(
			'singular_name',
			__('Enable Network Retry', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Retry failed print requests once on connection timeout or network errors. Note: May cause duplicate prints if the server received the original request.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$disableSslVerify = new Input('disable ssl verify');
	$disableSslVerify
		->setLabel(
			'singular_name',
			__('Disable SSL Verification', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Disable SSL certificate verification for print service requests. Only enable if you have TLS inspection or custom certificate authorities in your network.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Disable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$enableAsyncPrinting = new Input('enable async printing');
	$enableAsyncPrinting
		->setLabel(
			'singular_name',
			__('Background Print Processing', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('Process print jobs in the background instead of during checkout. Checkout completes immediately and print jobs are sent asynchronously. Recommended for sites experiencing print service timeouts.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);

	$enableFallbackQueue = new Input('enable fallback queue');
	$enableFallbackQueue
		->setLabel(
			'singular_name',
			__('Queue Failed Jobs', 'Print-Google-Cloud-Print-GCP-WooCommerce')
		)
		->attachTo($networkBox)
		->setArgument('description', __('When a print job fails during checkout, automatically queue it for background retry. Jobs will retry with increasing delays up to 5 times. Only applies when Background Print Processing is disabled.', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->setType(Input::TYPE_CHECKBOX)
		->attach(['1', __('Enable', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
};
