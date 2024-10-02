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
};
