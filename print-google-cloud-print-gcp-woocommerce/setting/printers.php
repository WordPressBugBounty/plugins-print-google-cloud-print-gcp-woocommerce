<?php

namespace Zprint;

use Zprint\Aspect\Page;
use Zprint\Aspect\Box;
use Zprint\Exception\DB as DBException;
use Zprint\Model\Location;
use Zprint\Template\Index;
use Zprint\Template\Options;

return function (Page $setting_page) {
	$locations = new TabPage('locations');
	$locations
		->setLabel('singular_name', __('Printers', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($setting_page)
		->setArgument('hideForm', true)
		->setArgument('contentPage', function ($page) use ($setting_page) {
			try {
				if (isset($_REQUEST['id'])) {
					$id = $_REQUEST['id'];
					try {
						$location = ($id === "new") ? new Location() : new Location($id);

						if ($_SERVER['REQUEST_METHOD'] === "GET") {
							$single = include __DIR__.'/locations/single.php';
							$single($location, $page, $setting_page);
						} else {
							$process = include __DIR__.'/locations/process.php';
							$process($location, $page, $setting_page);
						}
					} catch (DBException $exception) {
						wp_die($exception->getMessage());
					}
				} else {
					$index = include __DIR__.'/locations/index.php';
					$index($page, $setting_page);
				}
			} catch (\Exception $exception) { ?>
				<div class="notice notice-error is-dismissible">
					<p><?= $exception->getMessage(); ?></p>
				</div>
			<?php }
		});

	/* Tab-bar action buttons. Both are wrapped in a single span so flex/float
	   quirks can't separate them — the wrapper aligns right, the buttons
	   sit immediately next to each other inside it. "Add new" ends on the
	   far right; "Print a File" has a distinct primary style.
	   Deferred to admin_init priority 20 so that User::sync_quick_print_cap()
	   (priority 10) has already run. */
	add_action('admin_init', function () use ($setting_page, $locations) {
		$inner = '';
		if ( \Zprint\User::current_user_can_quick_print() ) {
			$quick_print_url = add_query_arg(
				'page',
				'zprint-quick-print',
				admin_url('admin.php')
			);
			$inner .= '<a href="' . esc_url($quick_print_url) . '" class="page-title-action zprint-print-file-btn"><span class="dashicons dashicons-printer" aria-hidden="true"></span>' . esc_html__('Print a File', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</a>';
		}
		$inner .= '<a href="' . add_query_arg('id', 'new', $setting_page->getUrl($locations)) . '" class="page-title-action">' . __('Add new', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</a>';

		$setting_page->setArgument('new_link', '<span class="zprint-tab-actions">' . $inner . '</span>');
	}, 20);

	$on_printers_tab = ( isset( $_GET['tab'] ) && Page::getName( $setting_page, $locations ) === $_GET['tab'] )
		|| $setting_page->isRequested( $locations );

	if ($on_printers_tab) {
		if (!session_id()) {
			session_start(['read_and_close' => true]);
		}
		$status = isset($_SESSION[Page::getName($setting_page, $locations)]) ? $_SESSION[Page::getName($setting_page, $locations)] : null;
		$_SESSION[Page::getName($setting_page, $locations)] = null;

		if ($status) {
			add_action('admin_notices', function () use ($status) {
				?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						switch ($status) {
							case 'deleted':
								{
									_e('Location deleted', 'Print-Google-Cloud-Print-GCP-WooCommerce');
									break;
								}

							case 'saved':
								{
									_e('Location saved', 'Print-Google-Cloud-Print-GCP-WooCommerce');
									break;
								}
							case
							'printed':
								{
									_e('Test print initiated, view log for details', 'Print-Google-Cloud-Print-GCP-WooCommerce');
									break;
								}
						}
						?>
					</p>
				</div>
			<?php });
		}
	};
};

