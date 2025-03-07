<?php
namespace Zprint\Support;

use Exception;

defined( 'ABSPATH' ) || exit;

abstract class Page extends BasePage {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		parent::__construct();
	}

	public static function get_toplevel_title( bool $lowercase = false ): string {
		$title = __( 'DevAssistant', 'Print-Google-Cloud-Print-GCP-WooCommerce' );

		return $lowercase ? mb_strtolower( $title ) : $title;
	}

	abstract public function add_page(): void;

	public function render_page(): void {
		?>
		<div class="da-setting-page wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php $this->render_content(); ?>
		</div>
		<?php
	}

	protected function render_content(): void {
		$option_group = isset( $_GET['page'] ) ? // phpcs:ignore
			sanitize_text_field( wp_unslash( $_GET['page'] ) ) : // phpcs:ignore
			'';
		?>
		<form method="post" action="<?php echo esc_url( get_admin_url( null, 'options.php' ) ); ?>">
			<?php
			settings_fields( $option_group );
			do_settings_sections( $option_group );
			submit_button();
			?>
		</form>
		<?php
	}

	public static function get_page_url(): string {
		return add_query_arg( array( 'page' => static::KEY ), get_admin_url( null, 'admin.php' ) );
	}

	/**
	 * @throws Exception
	 */
	public function enqueue_assets(): void {
		if ( ! static::is_setting_page() ) {
			return;
		}

		Asset::enqueue_style( 'setting' );
	}

	public static function is_current(): bool {
		return static::is_setting_page();
	}
}
