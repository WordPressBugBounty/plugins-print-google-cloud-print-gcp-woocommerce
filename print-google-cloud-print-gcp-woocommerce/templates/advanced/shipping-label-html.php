<?php

namespace Zprint;

use WC_Order;

require_once 'functions.php';

/* @var WC_Order $order */
/* @var array $location_data */
?>
<html>
<head>
	<style><?php require_once 'style.php'; ?></style>
</head>
<body>
<?php do_action('Zprint\templates\advanced\beforeAll', $order, $location_data); ?>
<?php do_action('Zprint\templates\advanced\beforeDetails', $order, $location_data); ?>
<table class="details">
	<tbody>
	<tr>
		<td class="w-70">
			<ul>
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
				<?php do_action('Zprint\templates\advanced\beforeOrderInfo', $order, $location_data); ?>
				<li>
					<b><?php echo esc_html__('Order No.', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($order->get_id()); ?>
				</li>
				<li>
					<b><?php echo esc_html__('Order date', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html(date_i18n(get_option('date_format', 'm/d/Y'), $order->get_date_created())); ?>
				</li>
				<?php if ($customer_note = $order->get_customer_note()) { ?>
					<li>
						<b><?php echo esc_html__('Customer note', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($customer_note); ?>
					</li>
				<?php } ?>
				<?php do_action('Zprint\templates\advanced\afterOrderInfo', $order, $location_data); ?>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="display-content-center" colspan="2">
			<ul class="display-center">
				<li><?php echo esc_html__('To', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</li>
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
					<li><b><?php echo esc_html__('Tel', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($order->get_shipping_phone()); ?></li>
					<li><b><?php echo esc_html__('Email', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>:</b> <?php echo esc_html($order->get_billing_email()); ?></li>
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
<?php do_action('Zprint\templates\advanced\afterAll', $order, $location_data); ?>
</body>
</html>
