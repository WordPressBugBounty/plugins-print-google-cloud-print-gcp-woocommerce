<?php

namespace Zprint;

class Setup
{
	public function __construct()
	{
		new Plugin();
		new Translate();
		new Activate();
		new Support\Setup();

	  add_action( 'before_woocommerce_init', [ $this, 'add_order_storage_support' ] );
		do_action('zprint_loaded_base');
		add_action('plugins_loaded', [$this, 'init']);

		if (file_exists( PLUGIN_ROOT . '/dev.php')) {
			require_once PLUGIN_ROOT . '/dev.php';
		}
	}

	public function add_order_storage_support() {
		if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', PLUGIN_ROOT_FILE, true);
		}
	}

	public function init()
	{
		new DB();
		if (!DB::is_tables_exists()) {
			$blog_id = is_multisite() ? get_current_blog_id() : null;
			$this->show_notice( '<b>Print Manager for WooCommerce:</b> ' . __( 'Due to an unexpected error the database table was not created.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) . ' <b><a href="' . get_admin_url($blog_id, 'index.php' ) . '?zprint_database_tables=create">' . __( 'Retry', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) . '</a></b>.' );
		}

		if (!class_exists('WooCommerce')) {
			$this->show_notice( __( 'Print Manager require WooCommerce', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) );
			return;
		}

		new User();
		require_once PLUGIN_ROOT . '/setting/index.php';
		Client::handleUpdateVersion();

		new Admin();
		new Printer();
		new POS();
		new Templates();
		new Document();
		new Debug\Core();
		new API();

		do_action('zprint_loaded');
	}

	private function show_notice( $message ) {
		add_action( 'admin_notices', function () use ( $message ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $message; ?></p>
			</div>
			<?php
		} );
	}

	public static function getPluginName()
	{
		$path = basename(PLUGIN_ROOT);
		$file = basename(PLUGIN_ROOT_FILE);
		return $path . DIRECTORY_SEPARATOR . $file;
	}
}
