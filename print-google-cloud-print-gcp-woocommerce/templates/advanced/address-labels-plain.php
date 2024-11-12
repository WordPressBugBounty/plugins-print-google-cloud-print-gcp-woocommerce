<?php

namespace Zprint;

use WC_Order;

require_once 'functions.php';

/* @var WC_Order $order */
/* @var array $location_data */
/* @var string $hook_suffix Shop|Shipping|Billing|Return */

$hook_suffix = ucfirst($location_data['shipping']['address_label_type']);

do_action('Zprint\templates\advanced\beforeAll', $order, $location_data);
do_action('Zprint\templates\advanced\beforeDetails', $order, $location_data);

do_action('Zprint\templates\advanced\before' . $hook_suffix . 'Info', $order, $location_data);
foreach (get_tpl_address_label_lines($location_data, $order) as $line) {
	if (empty($line)) {
		continue;
	}
	echo Document::line(esc_html($line));
}
do_action('Zprint\templates\advanced\after' . $hook_suffix . 'Info', $order, $location_data);

do_action('Zprint\templates\advanced\afterDetails', $order, $location_data);
do_action('Zprint\templates\advanced\afterAll', $order, $location_data);
