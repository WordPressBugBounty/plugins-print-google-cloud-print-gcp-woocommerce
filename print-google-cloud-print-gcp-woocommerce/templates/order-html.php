<?php
namespace Zprint;

use WC_Order;
use WC_Order_Item;

/* @var WC_Order $order */
/* @var array $location_data */
?>
<html>
<head>
    <style><?php include 'style.php';?></style>
</head>
<body>

<header>
	<?php if (get_appearance_setting('logo')) { ?>
		<img src="<?= get_appearance_setting('logo'); ?>" class="logo" alt="Logo">
	<?php } ?>
	<?php if (get_appearance_setting('Check Header')) { ?>
		<h2 class="kitchen"><?= get_appearance_setting('Check Header'); ?></h2>
	<?php } ?>
</header>

<table class="info">
    <thead>
		<tr>
			<th><?php _e('Order Number', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
			<th><?php _e('Date', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		</tr>
    </thead>
    <tbody>
		<tr>
			<td><?= apply_filters( 'Zprint\templates\general\orderIdLabel', $order->get_id(), $order ); ?></td>
			<td><?= date_i18n(get_option('date_format', 'm/d/Y'), $order->get_date_created()); ?></td>
		</tr>
    </tbody>
	<tfoot>
		<tr>
			<td colspan="4"><?php _e('Time ordered', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				- <?= date_i18n(get_option('time_format', 'H:i'), $order->get_date_created()); ?></td>
		</tr>
		<?php if ($location_data['shipping']['delivery_pickup_type']) { ?>
			<tr>
				<td colspan="4"><?= get_shipping_details($order); ?></td>
			</tr>
		<?php } ?>
		<?php do_action('Zprint\templates\order-html\afterShippingDetails', $order->get_id(), $order); ?>
	</tfoot>
</table>


<table class="order">
    <thead>
		<tr>
			<th colspan="2"><?php _e('Product', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		</tr>
    </thead>
    <?php foreach ($order->get_items() as $item) {
		/* @var $item WC_Order_Item */
		$meta = apply_filters('Zprint\templates\order-html\orderItemRawMeta', $item->get_formatted_meta_data(), $item, $order);
		$meta = array_filter($meta, function ($meta_item) {
			return !in_array($meta_item->key, Order::getHiddenKeys());
		});
		$meta = apply_filters('Zprint\templates\order-html\orderItemMeta', $meta);
        ?>
        <tbody>
			<tr>
				<td colspan="2"><?= $item['name']; ?> &times; <?= $item['qty']; ?></td>
			</tr>
			<?php $meta = array_map(function ($meta_item) {
				if($meta_item->key === "Additional") return null;
				$value = preg_replace('/\ \(.+\)$/', '', $meta_item->value);
				$result = '<tr>';
				$result .= '<td>' . $meta_item->key . '</td>';
				$result .= '<td>' . $value . '</td>';
				$result .= '</tr>';
				return $result;
			}, $meta);

			echo implode(PHP_EOL, $meta);
			?>
        </tbody>
    <?php } ?>
	<?php foreach ($order->get_fees() as $fee) { ?>
		<tbody>
			<tr>
				<td colspan="2"><?= $fee->get_name() ?></td>
			</tr>
		</tbody>
	<?php } ?>
</table>

<?php if($location_data['shipping']['billing_shipping_details']) { ?>
	<h2 class="caption"><?php _e('Customer Details', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></h2>
<?php } ?>

<table class="customer_details">
	<tbody class="base">
		<?php do_action('Zprint\templates\order-html\beforeCustomerDetails', $order->get_id(), $order); ?>
		<?php if($location_data['shipping']['billing_shipping_details']) { ?>
			<tr>
				<th><?php _e('Billing address', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
			</tr>
			<tr>
				<td>
					<?php echo ($address = $order->get_formatted_billing_address()) ? $address : __('N/A', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					<?php if ($order->get_billing_phone()) : ?>
						<br /><?php echo esc_html($order->get_billing_phone()); ?>
					<?php endif; ?>
					<?php if ($order->get_billing_email()) : ?>
						<p><?php echo esc_html($order->get_billing_email()); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		<?php } ?>
		<?php do_action('Zprint\templates\order-html\afterBaseCustomerDetails', $order->get_id(), $order); ?>
		<?php if ($location_data['shipping']['method'] && $shipping_method = $order->get_shipping_method()) { ?>
			<tr>
				<th><?php _e('Shipping method', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
			</tr>
			<tr>
				<td>
					<?= $shipping_method; ?>
				</td>
			</tr>
		<?php } ?>
		<?php if ($location_data['shipping']['billing_shipping_details'] && !wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ($shipping = $order->get_formatted_shipping_address())) : ?>
			<tr>
				<th><?php _e('Shipping address', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
			</tr>
			<tr>
				<td><?php echo $shipping; ?></td>
			</tr>
		<?php endif; ?>
		<?php do_action('Zprint\templates\order-html\afterCustomerDetails', $order->get_id(), $order); ?>
	</tbody>
	<?php if (!empty($order->get_customer_note())): ?>
		<tbody class="notes">
			<tr>
				<th>
					<?php _e('Order Notes', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
				</th>
			</tr>
			<tr>
				<td>
					<?= $order->get_customer_note(); ?>
				</td>
			</tr>
		</tbody>
	<?php endif; ?>
</table>
<?php do_action('Zprint\templates\order-html\end', $order->get_id(), $order); ?>
</body>
</html>
