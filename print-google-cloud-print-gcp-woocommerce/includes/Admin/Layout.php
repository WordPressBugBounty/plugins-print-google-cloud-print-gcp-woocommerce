<?php

namespace Zprint\Admin;

use Zprint\Plugin;
use Zprint\TabPage;
use Zprint\User;
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
		/* Use $_GET['page'] check as primary — Page::isRequested() can fail
		   on multisite subdirectory installs due to home_url() path doubling. */
		$allowed_slugs = apply_filters('zprint_print_manager_page_slugs', array('zp_bce0c'));
		$current_slug  = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
		$on_plugin_page = in_array($current_slug, $allowed_slugs, true);
		if ( ! $on_plugin_page && ! self::$page->isRequested() ) {
			return;
		} ?>
				<div class="zprint-layout-wrapper">
						<div class="zprint-layout">
								<div class="zprint-base">
										<a href="http://bizswoop.com/print">
												<img
													class="zprint-logo"
													src="<?= Plugin::getUrl('assets/logo-mark.svg') . '?v=3'; ?>"
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
								<?php /* Quick-Print-only users cannot open these pages. */ if (current_user_can(User::PRINT_MANAGEMENT_CAP_KEY)) : ?>
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
								<?php endif; ?>
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
