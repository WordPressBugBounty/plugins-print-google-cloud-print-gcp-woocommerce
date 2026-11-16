<?php

namespace Zprint\Support;

use Zprint\User;

defined('ABSPATH') or die('No script kiddies please!');

/*
 * Gate for admin AJAX handlers: wp_ajax_* runs for every logged-in user,
 * including Customers and Subscribers, so each handler must check both who
 * is asking (capability) and that the request came from our page (nonce).
 */
class AdminRequest
{
	/**
	 * Ends the request with a 403 unless the current user has $capability
	 * and the request carries a valid nonce for $nonce_action.
	 */
	public static function authorize(string $nonce_action, string $nonce_field = 'nonce', string $capability = User::PRINT_MANAGEMENT_CAP_KEY): void
	{
		if (!current_user_can($capability) || !check_ajax_referer($nonce_action, $nonce_field, false)) {
			wp_send_json_error(null, 403);
		}
	}
}
