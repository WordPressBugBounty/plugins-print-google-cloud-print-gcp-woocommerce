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
			}
		});
	}

	public static function is_reset_data()
	{
		return in_array( '1', (array) get_option( 'zp_bce0c_a24bc_86266', array() ) );
	}
}
