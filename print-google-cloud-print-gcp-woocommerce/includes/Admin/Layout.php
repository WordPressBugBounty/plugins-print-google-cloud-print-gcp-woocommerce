<?php

namespace Zprint\Admin;

use Zprint\Plugin;
use Zprint\TabPage;
use Zprint\Aspect\Page;

class Layout
{
	private static $page;

	public function __construct()
	{
		add_action('in_admin_header', [$this, 'pageHeader']);

		self::$page = Page::get('printer setting');
	}

	public function pageHeader()
	{
		if (!self::$page->isRequested()) {
			return;
		} ?>
				<div class="zprint-layout-wrapper">
						<div class="zprint-layout">
								<div class="zprint-base">
										<a href="http://bizswoop.com/print">
												<img
													class="zprint-logo"
													src="<?= Plugin::getUrl('assets/logo.png') . '?v=2'; ?>"
													alt="<?= esc_attr__('Print Manager', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>"
												>
										</a>
										<div class="zprint-title">
												<a href="http://bizswoop.com/print">
														<?= __('Print Manager', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
												</a>
										</div>
										<div class="zprint-slogan">
												<span><?= __('Print to anywhere with BizPrint', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
										</div>
								</div>
								<div class="zprint-navigation">
										<ul>
												<li>
														<a
															href="<?= self::getPageURL('general'); ?>"
															class="<?= self::isActiveClass('general'); ?>"
														>
																<div class="zprint-icon">
																		<i class="fal fa-ballot"></i>
																</div>
																<?= __('General', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
														</a>
												</li>
												<li>
														<a
															href="<?= self::getPageURL('locations'); ?>"
															class="<?= self::isActiveClass('locations'); ?>"
														>
																<div class="zprint-icon">
																		<i class="fal fa-print"></i>
																</div>
																<?= __('Printers', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
														</a>
												</li>
												<li>
														<a
															href="<?= self::getPageURL('setting'); ?>"
															class="<?= self::isActiveClass('setting'); ?>"
														>
																<div class="zprint-icon">
																		<i class="fal fa-cog"></i>
																</div>
																<?= __('Settings', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
														</a>
												</li>
											        <li>
														<a
															href="<?= self::getPageURL('addons'); ?>"
															class="<?= self::isActiveClass('addons'); ?>"
														>
																<div class="zprint-icon">
																		<i class="fal fa-bags-shopping"></i>
																</div>
																<?= __('Marketplace', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
														</a>
												</li>
												<li>
														<a href="http://bizswoop.com/">
																<div class="zprint-icon">
																		<img
																			src="<?= Plugin::getUrl('assets/bizswoop.png'); ?>"
																			alt="BizSwoop">
																</div>
																BizSwoop
														</a>
												</li>
										</ul>
								</div>
						</div>
				</div>
				<?php
	}

	private static function getPageURL($tab_name)
	{
		return self::$page->scope(function (Page $page) use ($tab_name) {
			return $page->getUrl(TabPage::get($tab_name));
		});
	}

	private static function isActiveClass($tab_name)
	{
		$is_current_tab = self::$page->scope(function (Page $page) use ($tab_name) {
			return $page->isRequested(TabPage::get($tab_name));
		});

		return $is_current_tab ? 'zprint-active-link' : '';
	}
}
