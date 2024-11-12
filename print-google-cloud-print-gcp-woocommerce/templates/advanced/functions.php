<?php

namespace Zprint;

use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

function get_tpl_formatted_state(
	string $city,
	string $country,
	string $postcode,
	bool $full_state_name = false
): string {
	$country = explode(':', $country);
	$state = $country[1] ?? '';

	if ($full_state_name) {
		if ($state) {
			$states = WC()->countries->get_states($country[0]);
			$state = isset($states[$state]) ?
				($states[$state] . ', ') :
				($state . ' ');
		}
	} else {
		$state = $state ? ($state . ' ') : '';
	}

	return $city . ', ' . $state . $postcode;
}

function get_tpl_formatted_country(string $country): string {
	$code = explode(':', $country)[0];
	$countries = WC()->countries->get_countries();

	return $countries[$code] ?? $code;
}

function get_tpl_product_price(WC_Order_Item_Product $item, WC_Order $order): string {
	return wc_price($item['total'] / $item['quantity'], array('currency' => $order->get_currency()));
}

function get_tpl_product_total(WC_Order_Item_Product $item, WC_Order $order, array $location_data): string {
	return apply_filters(
		'Zprint\templates\itemTotal',
		wc_price($item['total'], array('currency' => $order->get_currency())),
		$item,
		$location_data,
		$order->get_currency()
	);
}

function get_tpl_product_meta(WC_Order_Item_Product $item, WC_Order $order): array {
	$meta = apply_filters(
		'Zprint\templates\advanced\orderItemRawMeta',
		$item->get_formatted_meta_data(),
		$item,
		$order
	);
	$meta = array_filter($meta, function (object $meta_item): bool {
		return !in_array($meta_item->key, Order::getHiddenKeys());
	});

	return apply_filters('Zprint\templates\advanced\orderItemMeta', $meta);
}

function get_tpl_product_img(WC_Product $product): string {
	return $product->get_image('woocommerce_thumbnail', ['loading' => 'eager']);
}

function get_tpl_product_weight(WC_Product $product): string {
	$weight = $product->get_weight();

	if (empty($weight)) {
		return __('n/a', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	}

	return $weight . ' ' . get_option('woocommerce_weight_unit');
}

function get_tpl_address_label_lines(array $location_data, WC_Order $order): array {
	if (empty($location_data['shipping']['address_label_type'])) {
		return [];
	}

	switch ($location_data['shipping']['address_label_type']) {
		default:
		case 'shipping':
			return 0 < $order->get_customer_id() ? [
				$order->get_formatted_shipping_full_name(),
				$order->get_shipping_address_1(),
				$order->get_shipping_address_2(),
				get_tpl_formatted_state(
					$order->get_shipping_city(),
					$order->get_shipping_country(),
					$order->get_shipping_postcode(),
					true
				),
				get_tpl_formatted_country($order->get_shipping_country()),
			] : [__('Guest', 'Print-Google-Cloud-Print-GCP-WooCommerce')];
		case 'billing':
			return 0 < $order->get_customer_id() ? [
				$order->get_formatted_billing_full_name(),
				$order->get_billing_address_1(),
				$order->get_billing_address_2(),
				get_tpl_formatted_state(
					$order->get_billing_city(),
					$order->get_billing_country(),
					$order->get_billing_postcode(),
					true
				),
				get_tpl_formatted_country($order->get_billing_country()),
			] : [__('Guest', 'Print-Google-Cloud-Print-GCP-WooCommerce')];
		case 'shop':
			return [
				preg_replace('#^https?://#', '', get_option('siteurl')),
				get_option('woocommerce_store_address'),
				get_option('woocommerce_store_address_2'),
				get_tpl_formatted_state(
					get_option('woocommerce_store_city'),
					get_option('woocommerce_default_country'),
					get_option('woocommerce_store_postcode'),
					true
				),
				get_tpl_formatted_country(get_option('woocommerce_default_country')),
			];
		case 'return':
			return isset($location_data['shipping']['return_address']) ?
				[
					$location_data['shipping']['return_address']['address_1'] ?? '',
					$location_data['shipping']['return_address']['address_2'] ?? '',
					get_tpl_formatted_state(
						$location_data['shipping']['return_address']['city'] ?? '',
						$location_data['shipping']['return_address']['country'] ?? '',
						$location_data['shipping']['return_address']['postcode'] ?? '',
						true
					),
					get_tpl_formatted_country($location_data['shipping']['return_address']['country'] ?? ''),
				] :
				[];
	}
}
