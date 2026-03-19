<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;
use Zprint\Model\Location;

class Admin
{
    public function __construct()
    {
        if (!isset($_SESSION) && !headers_sent() && is_admin()) {
            session_start(['read_and_close' => true]);
        }

        add_action('init', [$this, 'showTemplatePreview']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);

        $this->renderQuickStartNotice();
        static::checkReprintNotice();

        new Admin\Layout();
        new Admin\Order();
        new Admin\OrderTable();
        new Admin\PrintDialog();
    }

    public function enqueueAssets()
    {
        wp_enqueue_style(
            'zprint-admin',
            plugins_url('assets/admin.css', PLUGIN_ROOT_FILE),
            [],
            PLUGIN_VERSION
        );
    }

    public static function checkReprintNotice()
    {
        if (is_admin() && isset($_SESSION['zprint_reprint'])) {
            session_start();
            $status = $_SESSION['zprint_reprint'];
            unset($_SESSION['zprint_reprint']);
            session_write_close();
            add_action('admin_notices', function () use ($status) {
                if ($status) { ?>
						<div class="notice notice-success">
							<p>
								<?php echo esc_html__(
            'Order(s) is printing',
            'Print-Google-Cloud-Print-GCP-WooCommerce'
        ); ?>
							</p>
						</div>
				<?php } else { ?>
						<div class="notice notice-error">
							<p>
								<?php echo esc_html__(
            'Print error',
            'Print-Google-Cloud-Print-GCP-WooCommerce'
        ); ?>
								</p>
						</div>
				<?php }
            });
        }
    }

	public function showTemplatePreview()
	{
		if (
			current_user_can( Admin\PrintDialog::get_required_capability() ) &&
			isset($_GET['zprint_location'], $_GET['zprint_order'])
		) {
			$allowed_query_args = array('zprint_location', 'zprint_order', 'zprint_order_user');
			$disallowed_query_args = array_filter(
				array_keys($_GET),
				function (string $query_arg) use ($allowed_query_args) {
					return !in_array($query_arg, $allowed_query_args, true);
				}
			);
			if ($disallowed_query_args) {
				wp_safe_redirect( esc_url_raw( remove_query_arg( $disallowed_query_args ) ), 307 );
			}

			$order = $_GET['zprint_order'];
			$order = wc_get_order($order);
			try {
				if (
					$id = filter_var(
						$_GET['zprint_location'],
						FILTER_VALIDATE_INT
					)
				) {
					$location = new Location($id);
				} else {
					throw new \Exception('Wrong Argument');
				}
			} catch (\Exception $exception) {
				die('Error:' . $exception->getMessage());
			}

			if (!$order instanceof \WC_Order) {
				die('Error: Order not found');
			}

			if (
		  	empty($_GET['zprint_pos']) ||
				!filter_var($_GET['zprint_pos'], FILTER_VALIDATE_BOOLEAN)
			) {
				header(
					'Content-Type: ' .
					Document::formatToContentType($location->format)
				);
			}

			if (
				isset($_GET['zprint_pos']) &&
				filter_var($_GET['zprint_pos'], FILTER_VALIDATE_BOOLEAN) &&
				$location->format === 'plain'
			) {
				echo '<pre>';
			}
			echo Document::generatePrint($order, $location->getData());
			if (
		  	isset($_GET['zprint_pos']) &&
				filter_var($_GET['zprint_pos'], FILTER_VALIDATE_BOOLEAN) &&
				$location->format === 'plain'
			) {
				echo '</pre>';
			}
			exit();
		}
	}

    private function renderQuickStartNotice()
    {
        global $pagenow;

        if ( is_multisite() && is_network_admin() ) {
            return;
        }

        if (
            isset($_GET['page']) &&
            'zp_bce0c' !== $_GET['page'] &&
            'index.php' !== $pagenow &&
            'plugins.php' !== $pagenow
        ) {
            return;
        }

        $api_settings = InstanceStorage::getGlobalStorage()->asCurrentStorage(
            function () {
                return Page::get('printer setting')->scope(function () {
                    $setting = TabPage::get('application');
                    $box = Box::get('api keys');

                    return [
                        'public' => Input::get('public key')->getValue(
                            $box,
                            null,
                            $setting
                        ),
                        'secret' => Input::get('secret key')->getValue(
                            $box,
                            null,
                            $setting
                        ),
                    ];
                });
            }
        );

        if ($api_settings['public'] && $api_settings['secret']) {
            return;
        }

        add_action('admin_notices', function () {
            $settings_url = admin_url('admin.php?page=zp_bce0c&tab=bce0c_3676d');
            ?>
			<div class="zprint-qnotice notice is-dismissible">
				<div class="zprint-qnotice__header">
					<span class="zprint-qnotice__header-icon fal fa-print"></span>
					<h4>
						<?php echo esc_html__(
							'You\'re almost there. Set up BizPrint to start printing automatically',
							'Print-Google-Cloud-Print-GCP-WooCommerce'
						); ?>
					</h4>
				</div>
				<div class="zprint-qnotice__body">
					<div class="zprint-qnotice__img">
						<img
							src="<?php echo Plugin::getUrl('assets/quick-start-notice.png'); ?>"
							alt="<?php echo esc_html__('BizPrint setup illustration', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>"
						>
					</div>
					<div class="zprint-qnotice__content">
						<h5>
							<?php echo esc_html__(
								'Finish Setup to Start Printing Automatically',
								'Print-Google-Cloud-Print-GCP-WooCommerce'
							); ?>
						</h5>
						<p>
							<?php echo esc_html__(
								'Save time and reduce complexity with a reliable, scalable print solution. Use your existing printer — works on all major brands.',
								'Print-Google-Cloud-Print-GCP-WooCommerce'
							); ?>
						</p>
						<p class="zprint-qnotice__checklist">
							<span class="zprint-qnotice__check fal fa-check-circle"></span>
							<?php echo esc_html__(
								'You\'re almost ready to print. Follow our Quick Start Guide to connect your printer and start printing receipts and orders.',
								'Print-Google-Cloud-Print-GCP-WooCommerce'
							); ?>
						</p>
						<div class="zprint-qnotice__actions">
							<a class="zprint-qnotice__btn" href="https://getbizprint.com/quick-start-guide/" target="_blank">
								<?php echo esc_html__(
									'Launch Quick Start Guide',
									'Print-Google-Cloud-Print-GCP-WooCommerce'
								); ?>
							</a>
							<a class="zprint-qnotice__link" href="<?php echo esc_url( $settings_url ); ?>">
								<?php echo esc_html__('Go to print settings', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
								<span class="fas fa-arrow-right"></span>
							</a>
							<a class="zprint-qnotice__link" href="https://bizswoop.atlassian.net/servicedesk/customer/portal/4/group/4/create/10039" target="_blank">
								<?php echo esc_html__('Get Support', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
								<span class="fas fa-life-ring"></span>
							</a>
						</div>
					</div>
				</div>
			</div>
			<?php
        });
    }
}
