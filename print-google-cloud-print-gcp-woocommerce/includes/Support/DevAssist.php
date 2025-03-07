<?php
namespace Zprint\Support;

use Plugin_Upgrader;

defined( 'ABSPATH' ) || exit;

class DevAssist {
	protected const SLUG = 'development-assistant';
	protected const FILE = self::SLUG .  '/' . self::SLUG . '.php';
	protected const PATH = WP_PLUGIN_DIR . '/' . self::FILE;

	public function __construct() {
		if ( static::is_dev_env() ) {
			return;
		}

		add_action( 'after_plugin_row_' . self::FILE, array( $this, 'render_plugins_screen_label' ) );
		add_filter( 'wp_dev_assist_enable_support_user', '__return_false' );
		add_filter( 'wp_dev_assist_assistant_panel_title', array( $this, 'add_title_prefix' ) );
		add_filter( 'wp_dev_assist_settings_page_title', array( $this, 'add_title_prefix' ) );
	}

	public function add_title_prefix( string $title ): string {
		return __( 'BizSwoop', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) . ' ' . $title;
	}

	public function render_plugins_screen_label(): void {
		global $wp_list_table;
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		$is_active = is_plugin_active( static::FILE );
		?>
		<tr
			class="zpos-dev-assistant-plugin-label plugin-update-tr <?php echo $is_active ? 'active' : 'inactive'; ?>"
			data-plugin="<?php echo esc_attr( static::FILE ); ?>"
		>
			<style>
				.wp-list-table.plugins tr:has(+ .zpos-dev-assistant-plugin-label) th,
				.wp-list-table.plugins tr:has(+ .zpos-dev-assistant-plugin-label) td {
					box-shadow: none;
				}
			</style>
			<td colspan="<?php echo esc_attr( $wp_list_table->get_column_count() ); ?>" class="plugin-update colspanchange">
				<div class="notice inline notice-alt">
					<p>
						<?php echo esc_html__( 'This plugin was automatically installed to provide support from BizSwoop. It will be automatically deleted along with the support user.', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
					</p>
				</div>
			</td>
		</tr>
		<?php
	}

	public static function is_dev_env(): bool {
		return 'yes' === get_option( 'wp_dev_assist_force_dev_env', 'no' );
	}

	public static function install(): void {
		if ( file_exists( static::PATH ) ) {
			if ( ! is_plugin_active( static::FILE ) ) {
				activate_plugin( static::FILE );
			}

			return;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( true === (new Plugin_Upgrader( new SilentUpgraderSkin() ))
				->install( 'https://downloads.wordpress.org/plugin/' . static::SLUG . '.zip' ) ) {
			activate_plugin( static::FILE );
		}
	}

	public static function uninstall(): void {
		if ( ! file_exists( static::PATH ) || static::is_dev_env() ) {
			return;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( is_plugin_active( static::FILE ) ) {
			deactivate_plugins( static::FILE );
		}

		delete_plugins( array( static::FILE ) );
	}
}
