<?php
namespace Zprint\Admin;

use Zprint\Model\Location;

class Order {
	public function __construct() {
		add_action( 'woocommerce_order_actions_start', array( $this, 'add_actions' ) );
	}

	public function add_actions( int $order_id ): void {
		$locations = Location::getAll();

		if ( empty( $locations ) ) {
			return;
		}
		?>
		<li class="wide">
			<button
				type="button"
				class="zprint-open-print-dialog button"
				style="width: 100%;"
				data-zprint-order-ids="[<?php echo esc_attr( $order_id ); ?>]"
			>
				<?php echo esc_html__( 'Print', 'Print-Google-Cloud-Print-GCP-WooCommerce' ); ?>
			</button>
		</li>
		<?php
	}
}
