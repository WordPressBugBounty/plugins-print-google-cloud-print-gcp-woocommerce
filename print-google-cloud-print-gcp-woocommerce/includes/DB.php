<?php

namespace Zprint;

class DB
{
	const Prefix = 'zp_';
	/* Tables */
	const Locations = 'locations';
	const JobQueue = 'job_queue';

	public static function setup()
	{
		self::create_tables();
	}

  public function __construct() {
		add_action( 'admin_init', array( $this, 'create_tables_by_url' ) );
	}

	private static function create_tables()
	{
		global $wpdb;
		$prefix = $wpdb->prefix . static::Prefix;
		$tables = static::get_option('zprint_tables', []);
		$locations = $prefix . static::Locations;

		$collate = '';

		if ($wpdb->has_cap('collation')) {
			$collate = $wpdb->get_charset_collate();
		}

		if (!in_array('base', $tables)) {
			$request = $wpdb->query(
				"CREATE TABLE IF NOT EXISTS `{$locations}` (
					id BIGINT(20) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
					title TEXT NOT NULL DEFAULT '',
					web_order INT(1),
			        pos_order_only INT(1),
			        template INT(5),
			        printers LONGTEXT,
			        users LONGTEXT,
					created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					created_at_gmt DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					updated_at_gmt DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'
				) {$collate};"
			);

			if ( $request ) {
				$tables[] = 'base';
			}
		}

		if (!in_array('options', $tables)) {
			$request = $wpdb->query(
				"ALTER TABLE `{$locations}` ADD options LONGTEXT AFTER template"
			);

			$locations_data = array();
			if ( $request ) {
				$locations_data = $wpdb->get_results("SELECT id, template FROM `{$locations}`");
			}

			foreach ($locations_data as $location_data) {
				$options = call_user_func(function ($template) {
					switch ($template) {
						case 1:
							return [
								'size' => 'custom',
								'width' => 80,
								'height' => 100,
								'template' => 'customer',
							];
						case 2:
							return [
								'size' => 'custom',
								'width' => 80,
								'height' => 100,
								'template' => 'order',
							];
						case 3:
							return [
								'size' => 'letter',
								'template' => 'details',
							];
						case 4:
							return [
								'size' => 'a4',
								'template' => 'details',
							];
						case 5:
							return [
								'size' => 'letter',
								'template' => 'customer',
							];
						case 6:
							return [
								'size' => 'letter',
								'template' => 'order',
							];
						case 7:
							return [
								'size' => 'a4',
								'template' => 'customer',
							];
						case 8:
							return [
								'size' => 'a4',
								'template' => 'order',
							];
						default:
							return [
								'size' => 'custom',
								'width' => 80,
								'height' => 100,
								'template' => 'details',
							];
					}
				}, $location_data->template);

				$wpdb->update(
					$locations,
					['options' => maybe_serialize($options)],
					['id' => $location_data->id],
					['%s'],
					['%d']
				);
			}

			$request = $wpdb->query(
				"ALTER TABLE `{$locations}` DROP COLUMN template"
			);

			if ( $request ) {
				$tables[] = 'options';
			}
		}

		if (!in_array('base_language_columns', $tables)) {
			$request = $wpdb->query(
				"ALTER TABLE `{$locations}`
                ADD language TEXT NOT NULL DEFAULT '' AFTER users,
                ADD language_locale TEXT NOT NULL DEFAULT '' AFTER language"
			);

			if ( $request ) {
				$tables[] = 'base_language_columns';
			}
		}

		if (!in_array('auto_include_all_users', $tables)) {
			$request = $wpdb->query(
				"ALTER TABLE `{$locations}`
                ADD auto_include_all_users INT(1) DEFAULT 0 AFTER users"
			);

			if ( $request ) {
				$tables[] = 'auto_include_all_users';
			}
		}

		$job_queue = $prefix . static::JobQueue;

		if (!in_array('job_queue', $tables)) {
			$request = $wpdb->query(
				"CREATE TABLE IF NOT EXISTS `{$job_queue}` (
					id BIGINT(20) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
					order_id BIGINT(20) UNSIGNED NOT NULL,
					location_id BIGINT(20) UNSIGNED NOT NULL,
					printer_id VARCHAR(255) NOT NULL,
					job_data LONGTEXT NOT NULL,
					status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
					attempts INT(3) DEFAULT 0,
					max_attempts INT(3) DEFAULT 5,
					last_error TEXT NULL,
					scheduled_at DATETIME NULL,
					created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					INDEX idx_status (status),
					INDEX idx_order (order_id),
					INDEX idx_scheduled (status, scheduled_at)
				) {$collate};"
			);

			if ( $request ) {
				$tables[] = 'job_queue';
			}
		}

		static::update_option('zprint_tables', $tables);
	}

	public static function db_activate($network_wide)
	{
		global $wpdb;
		if (is_multisite() && $network_wide) {
			// Get all blogs in the network and activate plugin on each one
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				static::setup();
				restore_current_blog();
			}
		} else {
			static::setup();
		}
	}

	public static function drop($network_wide)
	{
		global $wpdb;

		$prefix = $wpdb->prefix . static::Prefix;
		$locations = $prefix . static::Locations;
		$job_queue = $prefix . static::JobQueue;

		if (is_multisite() && $network_wide) {
			$blog_ids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");

			foreach ($blog_ids as $blog_id) {
				switch_to_blog($blog_id);
				$wpdb->query( "DROP TABLE IF EXISTS $locations;" );
				$wpdb->query( "DROP TABLE IF EXISTS $job_queue;" );
				restore_current_blog();
			}
		} else {
			$wpdb->query( "DROP TABLE IF EXISTS $locations;" );
			$wpdb->query( "DROP TABLE IF EXISTS $job_queue;" );
		}

		static::update_option('zprint_tables', []);
	}

	private static function get_option($name, $default = false)
	{
		if (is_multisite()) {
			return get_blog_option(get_current_blog_id(), $name, $default);
		} else {
			return get_option($name, $default);
		}
	}

	private static function update_option($name, $value)
	{
		if (is_multisite()) {
			return update_blog_option(get_current_blog_id(), $name, $value);
		} else {
			return update_option($name, $value);
		}
	}

	public static function is_tables_exists() {
		$tables = static::get_option('zprint_tables', []);

		return in_array( 'base', $tables, true ) &&
		       in_array( 'options', $tables, true ) &&
		       in_array( 'base_language_columns', $tables, true ) &&
		       in_array( 'auto_include_all_users', $tables, true );
	}

	public static function getJobQueueTable() {
		global $wpdb;
		return $wpdb->prefix . static::Prefix . static::JobQueue;
	}

	public function create_tables_by_url() {
		if ( isset( $_GET[ 'zprint_database_tables' ] ) && 'create' === $_GET[ 'zprint_database_tables' ] ) { // phpcs:ignore

			if ( is_admin() && current_user_can( 'update_plugins' ) ) {
				self::create_tables();
			}

			header( 'Location:' . get_admin_url( is_multisite() ? get_current_blog_id() : null, 'index.php' ) );
		}
	}
}
