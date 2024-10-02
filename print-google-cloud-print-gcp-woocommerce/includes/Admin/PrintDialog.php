<?php
namespace Zprint\Admin;

use Zprint\Plugin;
use Zprint\Model\Location;
use Zprint\Printer;

class PrintDialog
{
	public function __construct()
	{
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_zprint_render_print_dialog_window', array( $this, 'render_window' ) );
		add_action( 'wp_ajax_zprint_print_manually', array( $this, 'print' ) );
	}

    public function enqueue_assets(): void {
		global $pagenow;

		$is_custom_orders = class_exists('Automattic\WooCommerce\Utilities\OrderUtil') &&
			\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() &&
	  		(
				\Automattic\WooCommerce\Utilities\OrderUtil::is_order_list_table_screen() ||
				\Automattic\WooCommerce\Utilities\OrderUtil::is_order_edit_screen()
			);
		$is_legacy_orders = (
			'post.php' === $pagenow  &&
			isset( $_GET['post'] ) &&
			wc_get_order( intval( wp_unslash( $_GET['post'] ) ) )
		) || (
			'edit.php' === $pagenow &&
			isset( $_GET['post_type'] ) &&
			'shop_order' === sanitize_text_field( wp_unslash( $_GET['post_type'] ) )
		);

		if ( ! $is_custom_orders && ! $is_legacy_orders ) {
			return;
		}

        Plugin\Asset::enqueue_script(
			'print-dialog',
			array(),
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
        );
	    Plugin\Asset::enqueue_style( 'print-dialog' );
    }

	public function render_window(): void {
        $order_ids = isset( $_GET['order_ids'] ) ? array_map( 'intval', json_decode( $_GET['order_ids'] ) ) : array();

        if ( empty( $order_ids ) ) {
            die();
        }

		$order_id_count = count( $order_ids );
		$is_bulk_action = 1 < $order_id_count;
		$order_ids_size = 5 < $order_id_count ? ( 10 < $order_id_count ? '.6em' : '.8em' ) : '1em';
		$title          = $is_bulk_action ?
			__( 'Print Orders', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) :
			__( 'Print Order', 'Print-Google-Cloud-Print-GCP-WooCommerce');
		$title         .= $is_bulk_action ?
			( '<span class="zprint-dialog__title-order-ids" style="font-size: ' . esc_attr( $order_ids_size ) . ';">' ) :
			' ';
		$title         .= $this->get_formatted_order_ids( $order_ids );
		$title         .= $is_bulk_action ? '</span>' : '';
        $locations      = Location::getAll();
		?>
		<div class="zprint-dialog__window">
			<button
				class="zprint-dialog__hide fat fa-times"
				aria-label="<?php echo esc_attr__( 'Close', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>"
			></button>
			<div class="zprint-dialog__logo">
				<img
					class="zprint-dialog__logo-emblem"
					src="<?php echo esc_url( Plugin\Fs::get_url( 'assets/logo.png', true ) ); ?>"
					alt="<?php echo esc_attr__( 'Print Manager', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>"
				>
				<div class="zprint-dialog__logo-title">
					<?echo esc_html__( 'Print Manager', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
				</div>
			</div>
			<h3 class="zprint-dialog__title">
				<?php echo wp_kses( $title, array( 'span' => array( 'class' => array(), 'style' => array() ) ) ); ?>
			</h3>
			<div class="zprint-dialog__form" data-zprint-order-ids="<?php echo json_encode( $order_ids ); ?>">
				<label class="zprint-dialog__form-title">
					<?php echo esc_html__( 'Select Location(s)', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
				</label>
				<div class="zprint-dialog__locations">
					<?php
					$i = 0;
					foreach ( $locations as $location ) {
						?>
						<label>
							<input
								class="zprint-dialog__location-checkbox"
								type="checkbox"
								value="<?php echo esc_attr( $location->getID() ); ?>"
								<?php checked( 0, $i ); ?>
							>
							<span class="zprint-dialog__location">
								<?php do_action( 'zprint_print_dialog_location_before_title', $location ); ?>
								<span class="zprint-dialog__location-title">
									<?php echo esc_html( $location->title ); ?>
								</span>
							</span>
						</label>
						<?php
						$i++;
					}
					?>
				</div>
				<div class="zprint-dialog__btn-row">
					<button class="zprint-dialog__submit button button-primary" disabled>
						<?php echo esc_html__( 'Print', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
					</button>
				</div>
			</div>
		</div>
        <?php
        die();
	}

	public function print(): void {
		$order_ids    = isset( $_GET['order_ids'] ) ? array_map( 'intval', json_decode( wp_unslash( $_GET['order_ids'] ) ) ) : array();
		$location_ids = isset( $_GET['location_ids'] ) ? array_map( 'intval', json_decode( wp_unslash( $_GET['location_ids'] ) ) ) : array();
		$redirect_to  = apply_filters(
			'zprint_print_dialog_redirect_to',
			isset( $_GET['redirect_to'] ) ?
				sanitize_url( wp_unslash( $_GET['redirect_to'] ) ) :
				'',
			$order_ids,
			$location_ids
		);
		$location_ids = apply_filters( 'zprint_print_dialog_allowed_web_printer_locations', $location_ids );

		if ( $order_ids && $location_ids ) {
			foreach ( $order_ids as $order_id ) {
				Printer::reprintOrder( $order_id, $location_ids );
			}
		}

		$message  = '<b>' . __( 'Print Manager', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) . ':</b> ';
		$message .= 1 < count( $order_ids ) ?
			__( 'the print job for orders %s were sent successfully', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) :
			__( 'the print job for order %s was sent successfully', 'Print-Google-Cloud-Print-GCP-WooCommerce');
		$message  = sprintf( $message . '.', $this->get_formatted_order_ids( $order_ids ) );

		Plugin\Notice::add_transient( $message, 'success' );
		wp_send_json_success( $redirect_to );
		die();
	}

	protected function get_formatted_order_ids( array $order_ids ): string {
		if ( 1 < count( $order_ids ) ) {
			return array_reduce(
				$order_ids,
				function ( string $result, int $order_id ) {
					return $result .
						   ( empty( $result ) ? ' #' . $order_id : ', #' . $order_id);
				},
				''
			);
		}

		return '#' . $order_ids[0];
	}
}
