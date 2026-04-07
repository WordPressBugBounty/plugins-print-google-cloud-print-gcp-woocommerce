<?php

namespace Zprint;

class Activate
{
	public function __construct()
	{
		register_activation_hook(PLUGIN_ROOT_FILE, function ($network_wide) {
			DB::db_activate($network_wide);
		});

		add_action('wpmu_new_blog', function ($blog_id) {
			if (is_plugin_active_for_network(Setup::getPluginName())) {
				switch_to_blog($blog_id);
				DB::setup();
				restore_current_blog();
			}
		});

		register_deactivation_hook(PLUGIN_ROOT_FILE, function ($network_wide) {
			if (static::is_reset_data()) {
				DB::drop($network_wide);
				Support\Setup::reset();
				static::cleanupWebhookTransients($network_wide);
			}
		});
	}

	public static function is_reset_data()
	{
		return in_array( '1', (array) get_option( 'zp_bce0c_a24bc_86266', array() ) );
	}

	public static function cleanupWebhookTransients($network_wide = false)
	{
		global $wpdb;

		if (is_multisite() && $network_wide) {
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");

			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				static::deleteWebhookTransientsForCurrentBlog();
				restore_current_blog();
			}
		} else {
			static::deleteWebhookTransientsForCurrentBlog();
		}
	}

	private static function deleteWebhookTransientsForCurrentBlog()
	{
		global $wpdb;

		$result = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '\_transient\_zprint\_webhook\_request\_%'
			 OR option_name LIKE '\_transient\_timeout\_zprint\_webhook\_request\_%'"
		);

		if ($result === false) {
			Log::error(Log::BASIC, [
				'Deactivation Cleanup',
				'Failed to delete webhook transients',
				'Error: ' . $wpdb->last_error,
			]);
		}
	}
}
