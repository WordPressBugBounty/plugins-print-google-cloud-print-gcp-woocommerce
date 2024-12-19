<?php
namespace Zprint;

use WC_Order;
use WC_Order_Item;

/* @var WC_Order $order */
/* @var array $location_data */
?>
<?= Document::centerLine(get_appearance_setting('Company Name')); ?>
<?= Document::centerLine(__('Order Receipt', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
<?= Document::centerLine(
	__('Order Number', 'Print-Google-Cloud-Print-GCP-WooCommerce') .
	': ' . apply_filters( 'Zprint\templates\general\orderIdLabel', $order->get_id(), $order )
); ?>
<?= Document::centerLine(__('Date', 'Print-Google-Cloud-Print-GCP-WooCommerce') . ': ' . date_i18n(\get_option('date_format', 'm/d/Y'), $order->get_date_created())); ?>
<?php if ($location_data['shipping']['delivery_pickup_type']) { ?>
	<?= Document::centerLine(get_shipping_details($order)); ?>
<?php } ?>
<?php do_action('Zprint\templates\details-plain\afterShippingDetails', $order->get_id(), $order); ?>
<?= Document::emptyLine(); ?>
<?php foreach ($order->get_items() as $item) {
	/* @var $item WC_Order_Item */
	$meta = apply_filters('Zprint\templates\details-plain\orderItemRawMeta', $item->get_formatted_meta_data(), $item, $order);
	$meta = array_filter($meta, function ($meta_item) {
		return !in_array($meta_item->key, Order::getHiddenKeys());
	});
	$meta = apply_filters('Zprint\templates\details-plain\orderItemMeta', $meta);
	?>
	<?= Document::symbolsAlign($item['name'] . ' &times; ' . $item['qty'], apply_filters(
		'Zprint\templates\itemTotal',
		wc_price($item->get_data()['total'], array('currency' => $order->get_currency())),
		$item,
		$location_data,
		$order->get_currency()
	)); ?>
	<?php $meta = array_map(function ($meta_item) {
		return Document::symbolsAlign(' ' . $meta_item->key, $meta_item->value . ' ');
	}, $meta);
	echo implode('', $meta);
	?>
<?php } ?>
<?php foreach ($order->get_fees() as $fee) { ?>
		<?= Document::symbolsAlign($fee->get_name(), wc_price($fee->get_total(), array('currency' => $order->get_currency()))); ?>
<?php } ?>
<?= Document::emptyLine(); ?>
<?php if ($location_data['total']['cost']) { ?>
	<?= Document::symbolsAlign(__('Subtotal', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $order->get_subtotal_to_display()); ?>
<?php } ?>
<?php if ($location_data['shipping']['cost']) { ?>
	<?= Document::symbolsAlign(__('Shipping', 'Print-Google-Cloud-Print-GCP-WooCommerce'), wc_price($order->get_shipping_total(), array('currency' => $order->get_currency()))); ?>
<?php } ?>
<?php if ($location_data['total']['cost']) { ?>
	<?= Document::symbolsAlign(__('Tax', 'Print-Google-Cloud-Print-GCP-WooCommerce'), wc_price($order->get_total_tax(), array('currency' => $order->get_currency()))); ?>
	<?= Document::symbolsAlign(__('Payment Method', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $order->get_payment_method_title()); ?>
	<?= Document::symbolsAlign(__('Total', 'Print-Google-Cloud-Print-GCP-WooCommerce'), wc_price($order->get_total(), array('currency' => $order->get_currency()))); ?>
<?php } ?>
<?= Document::emptyLine(); ?>
<?php if ($location_data['shipping']['billing_shipping_details']) { ?>
	<?= Document::centerLine(__('Customer Details', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?= Document::emptyLine(); ?>
	<?php do_action('Zprint\templates\details-plain\beforeCustomerDetails', $order->get_id(), $order); ?>
	<?= Document::centerLine(__('Billing address', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?php if ($address = $order->get_formatted_billing_address()) {
		$address = explode('<br/>', $address);
		foreach ($address as $line) echo Document::line($line);
	} else {
		echo Document::line(__('N/A', 'Print-Google-Cloud-Print-GCP-WooCommerce'));
	} ?>
	<?php if ($order->get_billing_phone()) : ?>
		<?= Document::line(esc_html($order->get_billing_phone())); ?>
	<?php endif; ?>
	<?php if ($order->get_billing_email()) : ?>
		<?= Document::line(esc_html($order->get_billing_email())); ?>
	<?php endif; ?>
	<?php do_action('Zprint\templates\details-plain\afterBaseCustomerDetails', $order->get_id(), $order); ?>
<?php } ?>
<?php if ($location_data['shipping']['method'] && $shipping_method = $order->get_shipping_method()) { ?>
	<?= Document::centerLine(__('Shipping method', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?= Document::line($shipping_method); ?>
<?php } ?>
<?php if ($location_data['shipping']['billing_shipping_details'] && !wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ($shipping = $order->get_formatted_shipping_address())) : ?>
	<?= Document::centerLine(__('Shipping address', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?php
	$shipping = explode('<br/>', $shipping);
	foreach ($shipping as $line) echo Document::line($line);
	echo Document::emptyLine();
	?>
<?php endif; ?>
<?php do_action('Zprint\templates\details-plain\afterCustomerDetails', $order->get_id(), $order); ?>
<?php if (!empty($order->get_customer_note())): ?>
	<?= Document::centerLine(__('Order Notes', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?= Document::line($order->get_customer_note()); ?>
<?php endif; ?>
<?php do_action('Zprint\templates\details-plain\beforeFooter', $order->get_id(), $order); ?>
<?= Document::centerLine(get_appearance_setting('Footer Information #1')); ?>
<?= Document::centerLine(get_appearance_setting('Footer Information #2')); ?>
