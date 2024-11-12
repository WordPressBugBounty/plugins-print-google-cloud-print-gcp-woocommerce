<?php

namespace Zprint;

use WC_Order;
use WC_Order_Item_Product;

require_once 'functions.php';

/* @var WC_Order $order */
/* @var array $location_data */

$check_header = get_appearance_setting('Check Header') ?:
	__('Delivery note', 'Print-Google-Cloud-Print-GCP-WooCommerce');
?>
<html>
<head>
	<style><?php require_once 'style.php'; ?></style>
</head>
<body>
<?php do_action('Zprint\templates\advanced\beforeAll', $order, $location_data); ?>
<?php if ($logo = get_appearance_setting('logo')) { ?>
	<header>
		<h1><?php echo esc_html($check_header); ?></h1>
	</header>
<?php } ?>
<?php do_action('Zprint\templates\advanced\beforeDetails', $order, $location_data); ?>
<table class="details">
	<tbody>
	<tr>
		<td class="w-70 company" colspan="2">
			<?php if ($logo) { ?>
				<img
					src="<?php echo esc_attr($logo); ?>"
					class="logo"
					alt="<?php echo esc_attr__( 'Logo', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>"
				>
			<?php } else { ?>
				<h1><?php echo esc_html($check_header); ?></h1>
			<?php } ?>
			<?php do_action('Zprint\templates\advanced\beforeCompanyInfo', $order, $location_data); ?>
			<?php if ($company_name = get_appearance_setting('Company Name')) { ?>
				<h3><?php echo esc_html($company_name); ?></h3>
			<?php } ?>
			<?php if ($company_info = get_appearance_setting('Company Info')) { ?>
				<h4><?php echo esc_html($company_info); ?></h4>
			<?php } ?>
			<?php do_action('Zprint\templates\advanced\afterCompanyInfo', $order, $location_data); ?>
		</td>
		<td>
			<ul>
				<?php do_action('Zprint\templates\advanced\beforeOrderInfo', $order, $location_data); ?>
				<li>
					<b><?php echo esc_html__('Order No.', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($order->get_id()); ?>
				</li>
				<li>
					<b><?php echo esc_html__('Order Date', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html(date_i18n(get_option('date_format', 'm/d/Y'), $order->get_date_created())); ?>
				</li>
				<?php if ($location_data['shipping']['method'] && $shipping_method = $order->get_shipping_method()) { ?>
					<li>
						<b><?php echo esc_html__('Shipping Method', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($shipping_method); ?>
					</li>
				<?php } ?>
				<?php if ($location_data['shipping']['delivery_pickup_type']) { ?>
					<li>
						<b><?php echo esc_html__('Shipping Details', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html(get_shipping_details($order)); ?>
					</li>
				<?php } ?>
				<?php do_action('Zprint\templates\advanced\afterOrderInfo', $order, $location_data); ?>
			</ul>
		</td>
	</tr>
	<tr>
		<td>
			<ul>
				<li><h4><?php echo esc_html__('From', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></h4></li>
				<?php do_action('Zprint\templates\advanced\beforeShopInfo', $order, $location_data); ?>
				<li><?php echo esc_html(preg_replace('#^https?://#', '', get_option('siteurl'))); ?></li>
				<li><?php echo esc_html(get_option('woocommerce_store_address')); ?></li>
				<?php if ($store_address_2 = get_option('woocommerce_store_address_2')) { ?>
					<li><?php echo esc_html($store_address_2); ?></li>
				<?php } ?>
				<li>
					<?php
					echo esc_html(get_tpl_formatted_state(
						get_option('woocommerce_store_city'),
						get_option('woocommerce_default_country'),
						get_option('woocommerce_store_postcode')
					));
					?>
				</li>
				<li><?php echo esc_html(get_option('admin_email')); ?></li>
				<?php do_action('Zprint\templates\advanced\afterShopInfo', $order, $location_data); ?>
			</ul>
		</td>
		<td>
			<ul>
				<li><h4><?php echo esc_html__('Bill to', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></h4></li>
				<?php do_action('Zprint\templates\advanced\beforeBillingInfo', $order, $location_data); ?>
				<?php if (0 < $order->get_customer_id()) { ?>
					<li><?php echo esc_html($order->get_formatted_billing_full_name()); ?></li>
					<li><?php echo esc_html($order->get_billing_address_1()); ?></li>
					<?php
					if ($billing_address = $order->get_billing_address_2()) {
						?>
						<li><?php echo esc_html($billing_address); ?></li>
					<?php } ?>
					<li>
						<?php
						echo esc_html(get_tpl_formatted_state(
							$order->get_billing_city(),
							$order->get_billing_country(),
							$order->get_billing_postcode()
						));
						?>
					</li>
					<li><b><?php echo esc_html__('Email', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($order->get_billing_email()); ?></li>
					<li><b><?php echo esc_html__('Phone', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($order->get_billing_phone()); ?></li>
				<?php } else { ?>
					<li><?php echo esc_html__('Guest', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></li>
				<?php } ?>
				<?php do_action('Zprint\templates\advanced\afterBillingInfo', $order, $location_data); ?>
			</ul>
		</td>
		<td>
			<ul>
				<li><h4><?php echo esc_html__('Ship to', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></h4></li>
				<?php do_action('Zprint\templates\advanced\beforeShippingInfo', $order, $location_data); ?>
				<?php if (0 < $order->get_customer_id()) { ?>
					<li><?php echo esc_html($order->get_formatted_shipping_full_name()); ?></li>
					<li><?php echo esc_html($order->get_shipping_address_1()); ?></li>
					<?php
					if ($shipping_address = $order->get_shipping_address_2()) {
						?>
						<li><?php echo esc_html($shipping_address); ?></li>
					<?php } ?>
					<li>
						<?php
						echo esc_html(get_tpl_formatted_state(
							$order->get_shipping_city(),
							$order->get_shipping_country(),
							$order->get_shipping_postcode()
						));
						?>
					</li>
					<li><?php echo esc_html($order->get_shipping_phone()); ?></li>
				<?php } else { ?>
					<li><?php echo esc_html__('Guest', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></li>
				<?php } ?>
				<?php do_action('Zprint\templates\advanced\afterShippingInfo', $order, $location_data); ?>
			</ul>
		</td>
	</tr>
	</tbody>
</table>
<?php do_action('Zprint\templates\advanced\afterDetails', $order, $location_data); ?>
<table class="products">
	<thead>
	<tr>
		<th><?php echo esc_html__('S.No', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Image', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('SKU', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Product', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Quantity', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Unit price', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Total price', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$i = 1;
	foreach ($order->get_items() as $item) {
		if (!$item instanceof WC_Order_Item_Product) {
			continue;
		}
		$product = $item->get_product();
		?>
		<tr>
			<td><?php echo esc_html($i); ?></td>
			<td>
				<?php echo wp_kses_post(get_tpl_product_img($product)); ?>
			</td>
			<td>
				<?php echo esc_html($product->get_sku()); ?>
			</td>
			<td>
				<?php echo esc_html($item['name']); ?>
				<?php if ($item_meta = get_tpl_product_meta($item, $order)) { ?>
					<ul>
						<?php foreach ($item_meta as $meta) { ?>
							<li><?php echo esc_html($meta->display_key); ?>: <?php echo esc_html($meta->display_value); ?></li>
						<?php } ?>
					</ul>
				<?php } ?>
			</td>
			<td><?php echo esc_html($item['quantity']); ?></td>
			<td><?php echo wp_kses_post(get_tpl_product_price($item, $order)); ?></td>
			<td><?php echo wp_kses_post(get_tpl_product_total($item, $order, $location_data)); ?></td>
		</tr>
		<?php
		$i++;
	}
	?>
	</tbody>
</table>
<?php do_action('Zprint\templates\advanced\afterProducts', $order, $location_data); ?>
<?php do_action('Zprint\templates\advanced\afterAll', $order, $location_data); ?>
</body>
</html>
