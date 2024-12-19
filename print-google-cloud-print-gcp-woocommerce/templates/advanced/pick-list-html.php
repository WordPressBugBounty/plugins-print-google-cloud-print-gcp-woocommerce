<?php

namespace Zprint;

use WC_Order;
use WC_Order_Item_Product;

require_once 'functions.php';

/* @var WC_Order $order */
/* @var array $location_data */

$check_header = get_appearance_setting('Check Header');
$logo = get_appearance_setting('logo');
?>
<html>
<head>
	<style><?php require_once 'style.php'; ?></style>
</head>
<body>
<?php do_action('Zprint\templates\advanced\beforeAll', $order, $location_data); ?>
<?php if ($check_header && $logo) { ?>
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
			<?php } elseif ($check_header) { ?>
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
				<li><h4><?php echo esc_html__('From Address', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</h4></li>
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
						get_option('woocommerce_store_postcode'),
						true
					));
					?>
				</li>
				<li><?php echo esc_html(get_option('admin_email')); ?></li>
				<?php do_action('Zprint\templates\advanced\afterShopInfo', $order, $location_data); ?>
			</ul>
		</td>
	</tr>
	<tr>
		<td colspan="3">
			<ul>
				<?php do_action('Zprint\templates\advanced\beforeOrderInfo', $order, $location_data); ?>
				<li>
					<?php echo esc_html__('Orders', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>: <?php echo esc_html(apply_filters( 'Zprint\templates\general\orderIdLabel', $order->get_id(), $order )); ?>
				</li>
				<li>
					<?php echo esc_html__('Printed on', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>: <?php echo esc_html(date(get_option('date_format', 'm/d/Y') . ' ' . get_option('time_format', 'g:i A'))); ?>
				</li>
				<?php do_action('Zprint\templates\advanced\afterOrderInfo', $order, $location_data); ?>
			</ul>
		</td>
	</tr>
	</tbody>
</table>
<?php do_action('Zprint\templates\advanced\afterDetails', $order, $location_data); ?>
<table class="products products-solid">
	<thead>
	<tr>
		<th><?php echo esc_html__('Image', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('SKU', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Product', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Quantity', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
		<th><?php echo esc_html__('Total weight', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	foreach ($order->get_items() as $item) {
		if (!$item instanceof WC_Order_Item_Product) {
			continue;
		}
		$product = $item->get_product();
		?>
		<tr>
			<td>
				<?php echo wp_kses_post(get_tpl_product_img($product)); ?>
			</td>
			<td>
				<?php echo esc_html($product->get_sku()); ?>
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
			<td><?php echo esc_html(get_tpl_product_weight($product)); ?></td>
		</tr>
		<?php
	}
	?>
	</tbody>
</table>
<?php do_action('Zprint\templates\advanced\afterProducts', $order, $location_data); ?>
<footer>
	<?php echo esc_html__('Terms and conditions apply', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
</footer>
<?php do_action('Zprint\templates\advanced\afterAll', $order, $location_data); ?>
</body>
</html>
