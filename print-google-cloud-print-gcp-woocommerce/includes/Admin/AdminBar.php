<?php

namespace Zprint\Admin;

use Zprint\User;

class AdminBar
{
	const PAGE_SLUG = 'zprint-quick-print';

	public function __construct()
	{
		add_action('admin_bar_menu', array($this, 'registerNode'), 80);
	}

	public function registerNode($wp_admin_bar)
	{
		if (!is_admin_bar_showing()) {
			return;
		}

		if (!User::current_user_can_quick_print()) {
			return;
		}

		$wp_admin_bar->add_node(array(
			'id'    => 'bizprint-quickprint',
			'title' => '<span class="ab-icon dashicons dashicons-printer" style="margin-top:2px;"></span><span class="ab-label">' . esc_html__('Print a File', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</span>',
			'href'  => add_query_arg('page', self::PAGE_SLUG, admin_url('admin.php')),
			'meta'  => array(
				'title' => __('Open the BizPrint drag-and-drop Print a File page', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
			),
		));
	}
}
