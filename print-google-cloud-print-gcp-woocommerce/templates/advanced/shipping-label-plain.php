<?php

namespace Zprint;

use WC_Order;

require_once 'functions.php';

/* @var WC_Order $order */
/* @var array $location_data */

do_action('Zprint\templates\advanced\beforeAll', $order, $location_data);
do_action('Zprint\templates\advanced\beforeDetails', $order, $location_data);

do_action('Zprint\templates\advanced\beforeShopInfo', $order, $location_data);
echo Document::line(esc_html(preg_replace('#^https?://#', '', get_option('siteurl'))));
echo Document::line(esc_html(get_option('woocommerce_store_address')));
if ($store_address_2 = get_option('woocommerce_store_address_2')) {
	echo Document::line(esc_html($store_address_2));
}
echo Document::line(esc_html(get_tpl_formatted_state(
	get_option('woocommerce_store_city'),
	get_option('woocommerce_default_country'),
	get_option('woocommerce_store_postcode')
)));
echo Document::line(esc_html(get_option('admin_email')));
do_action('Zprint\templates\advanced\afterShopInfo', $order, $location_data);

echo Document::emptyLine();

do_action('Zprint\templates\advanced\beforeOrderInfo', $order, $location_data);
echo Document::symbolsAlign(esc_html__('Order No.', 'Print-Google-Cloud-Print-GCP-WooCommerce'), esc_html($order->get_id()));
echo Document::symbolsAlign(esc_html__('Order date', 'Print-Google-Cloud-Print-GCP-WooCommerce'), esc_html(date_i18n(get_option('date_format', 'm/d/Y'), $order->get_date_created())));
if ($customer_note = $order->get_customer_note()) {
	echo Document::symbolsAlign(esc_html__('Customer note', 'Print-Google-Cloud-Print-GCP-WooCommerce'), esc_html($customer_note));
}
do_action('Zprint\templates\advanced\afterOrderInfo', $order, $location_data);

echo Document::emptyLine();

echo Document::line(esc_html__('To', 'Print-Google-Cloud-Print-GCP-WooCommerce') . ':');
do_action('Zprint\templates\advanced\beforeCustomerDetails', $order, $location_data);
if (0 < $order->get_customer_id()) {
	echo Document::line(esc_html($order->get_formatted_shipping_full_name()));
	echo Document::line(esc_html($order->get_shipping_address_1()));
	if ($shipping_address = $order->get_shipping_address_2()) {
		echo Document::line(esc_html($shipping_address));
	}
	echo Document::line(esc_html(get_tpl_formatted_state(
		$order->get_shipping_city(),
		$order->get_shipping_country(),
		$order->get_shipping_postcode()
	)));
	echo Document::line(esc_html__('Tel', 'Print-Google-Cloud-Print-GCP-WooCommerce') . ': ' . esc_html($order->get_shipping_phone()));
	echo Document::line(esc_html__('Email', 'Print-Google-Cloud-Print-GCP-WooCommerce') . ': ' . esc_html($order->get_billing_email()));
} else {
	echo Document::line(esc_html__('Guest', 'Print-Google-Cloud-Print-GCP-WooCommerce'));
}
do_action('Zprint\templates\advanced\afterCustomerDetails', $order, $location_data);

do_action('Zprint\templates\advanced\afterDetails', $order, $location_data);
do_action('Zprint\templates\advanced\afterAll', $order, $location_data);
