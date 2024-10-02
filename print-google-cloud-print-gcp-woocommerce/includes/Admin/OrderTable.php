<?php

namespace Zprint\Admin;

use Zprint\Model\Location;
use const Zprint\PLUGIN_ROOT_FILE;
use const Zprint\PLUGIN_VERSION;

class OrderTable
{
    private const COLUMN_ACTIONS_KEY = 'zprint_actions';
    private const BULK_PRINT_KEY = 'zprint_print';

    public function __construct()
    {
	    add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

	    if (
		    class_exists('Automattic\WooCommerce\Utilities\OrderUtil') &&
			\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
	    ) {
		    add_filter(
			    'manage_woocommerce_page_wc-orders_columns',
			    [$this, 'add_columns'],
			    99
		    );
		    add_action(
			    'manage_woocommerce_page_wc-orders_custom_column',
			    [$this, 'render_print_column'],
			    99,
			    2
		    );
			add_filter(
				'bulk_actions-woocommerce_page_wc-orders',
				[$this, 'add_bulk_actions'],
				1
			);

	    } else {
		    add_filter(
			    'manage_shop_order_posts_columns',
			    [$this, 'add_columns'],
			    99
		    );
		    add_action(
			    'manage_shop_order_posts_custom_column',
			    [$this, 'render_print_column'],
			    99,
			    2
		    );
		    add_filter(
			    'bulk_actions-edit-shop_order',
			    [$this, 'add_bulk_actions'],
			    1
		    );
	    }
    }

    public function add_columns(array $columns): array
    {
        $columns[static::COLUMN_ACTIONS_KEY] = __(
            'Print',
            'Print-Google-Cloud-Print-GCP-WooCommerce'
        );

        return $columns;
    }

	public function render_print_column(string $column, /* int|\WC_Order */ $order_id)
	{
		if (static::COLUMN_ACTIONS_KEY !== $column) {
			return;
		}

		if ($order_id instanceof \WC_Order) {
			$order_id = $order_id->get_id();
		}

		$locations = Location::getAllFormatted();

		if (empty($locations)) {
			return;
		}

		$btn_label = __('Print', 'Print-Google-Cloud-Print-GCP-WooCommerce');
		?>
		<p>
			<a
				class="zprint-open-print-dialog button wc-action-button help_tip far fa-print"
				aria-label="<?php echo esc_attr( $btn_label ); ?>"
				data-tip="<?php echo esc_attr( $btn_label ); ?>"
				data-zprint-order-ids="[<?php echo esc_attr( $order_id ); ?>]"
			></a>
		</p>
		<?php
	}

    public function add_bulk_actions(array $actions): array
    {
        $actions[static::BULK_PRINT_KEY] = __(
            'Print',
            'Print-Google-Cloud-Print-GCP-WooCommerce'
        );

        return $actions;
    }

    public function enqueue_assets()
    {
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

        wp_enqueue_style(
            'zprint-orders-table-fa',
            plugins_url('assets/fa/app.css', PLUGIN_ROOT_FILE),
            [],
            PLUGIN_VERSION
        );
    }
}
