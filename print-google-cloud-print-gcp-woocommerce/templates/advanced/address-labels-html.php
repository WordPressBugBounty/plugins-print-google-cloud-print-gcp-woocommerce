<?php

namespace Zprint;

use WC_Order;

require_once 'functions.php';

/* @var WC_Order $order */
/* @var array $location_data */
/* @var string $hook_suffix Shop|Shipping|Billing|Return */

$hook_suffix = ucfirst($location_data['shipping']['address_label_type']);
?>
<html>
<head>
	<style><?php require_once 'style.php'; ?></style>
</head>
<body>
<?php do_action('Zprint\templates\advanced\beforeAll', $order, $location_data); ?>
<?php do_action('Zprint\templates\advanced\beforeDetails', $order, $location_data); ?>
<div class="label-solid">
	<ul>
		<?php do_action('Zprint\templates\advanced\before' . $hook_suffix . 'Info', $order, $location_data); ?>
		<?php
		foreach (get_tpl_address_label_lines($location_data, $order) as $line) {
			if (empty($line)) {
				continue;
			}
			?>
			<li><?php echo esc_html($line); ?></li>
		<?php } ?>
		<?php do_action('Zprint\templates\advanced\after' . $hook_suffix . 'Info', $order, $location_data); ?>
	</ul>
</div>
<?php do_action('Zprint\templates\advanced\afterDetails', $order, $location_data); ?>
<?php do_action('Zprint\templates\advanced\afterAll', $order, $location_data); ?>
</body>
</html>
