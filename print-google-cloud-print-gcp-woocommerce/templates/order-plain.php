<?php namespace Zprint;
/* @var $order \WC_Order */
/* @var $location_data */
?>
<?= Document::centerLine(get_appearance_setting('Check Header')); ?>
<?= Document::emptyLine(); ?>
<?= Document::symbolsAlign(__('Order Number', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $order->get_id()); ?>
<?= Document::symbolsAlign(__('Date', 'Print-Google-Cloud-Print-GCP-WooCommerce'), date_i18n(\get_option('date_format', 'm/d/Y'), $order->get_date_created())); ?>
<?= Document::symbolsAlign(__('Time Ordered', 'Print-Google-Cloud-Print-GCP-WooCommerce'), date_i18n(\get_option('time_format', 'H:i'), $order->get_date_created())); ?>
<?php if ($location_data['shipping']['delivery_pickup_type']) { ?>
	<?= Document::centerLine(get_shipping_details($order)); ?>
<?php } ?>
<?php do_action('Zprint\templates\order-plain\afterShippingDetails', $order->get_id()); ?>
<?= Document::emptyLine(); ?>
<?php foreach ($order->get_items() as $item) {
	/* @var $item \WC_Order_item */
	$meta = apply_filters('Zprint\templates\order-plain\orderItemRawMeta', $item->get_formatted_meta_data(), $item, $order);
	$meta = array_filter($meta, function ($meta_item) {
		return !in_array($meta_item->key, Order::getHiddenKeys());
	});
	$meta = apply_filters('Zprint\templates\order-plain\orderItemMeta', $meta);
	?>
	<?= Document::symbolsAlign($item['name'], $item['qty']); ?>
	<?php $meta = array_map(function ($meta_item) {
		return Document::symbolsAlign(' ' . $meta_item->key, $meta_item->value . ' ');
	}, $meta);
	echo implode('', $meta);
	?>
<?php } ?>
<?php foreach ($order->get_fees() as $fee) { ?>
		<?= Document::line($fee->get_name()); ?>
<?php } ?>
<?= Document::emptyLine(); ?>
<?php if ($location_data['shipping']['billing_shipping_details']) { ?>
	<?= Document::centerLine(__('Customer Details', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?= Document::emptyLine(); ?>
	<?php do_action('Zprint\templates\order-plain\beforeCustomerDetails', $order->get_id(), $order); ?>
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
	<?php do_action('Zprint\templates\order-plain\afterBaseCustomerDetails', $order->get_id(), $order); ?>
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
<?php do_action('Zprint\templates\order-plain\afterCustomerDetails', $order->get_id(), $order); ?>
<?php if (!empty($order->get_customer_note())): ?>
	<?= Document::centerLine(__('Order Notes', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>
	<?= Document::line($order->get_customer_note()); ?>
<?php endif; ?>
<?php do_action('Zprint\templates\order-plain\end', $order->get_id(), $order); ?>
