<?php
/*
 * Drag-and-drop "Print a File" page.
 *
 * Registered with add_submenu_page directly (bypassing the Aspect Page
 * framework) so it can declare its own capability — `zprint_quick_print` —
 * instead of inheriting `zprint_management` from the parent Print Manager
 * page. Entry point is the button rendered into the Print Manager
 * nav-tab-wrapper by setting/printers.php, plus the admin bar shortcut.
 *
 * Registered as a hidden page (empty parent) rather than a Print Manager
 * submenu that is removed afterwards: Print Manager is a WooCommerce submenu
 * for some users and a top-level menu for others (no edit_others_shop_orders),
 * and a removed submenu under a top-level parent resolves to a different page
 * hook at request time, so the page would not load for those users.
 */

namespace Zprint;

use Zprint\Aspect\Page as AspectPage;
use Zprint\Plugin\Asset;
use Zprint\Support\QuickPrintSettings;
use Zprint\Support\SupportedFormats;

const QUICK_PRINT_PAGE_SLUG = 'zprint-quick-print';

add_action('admin_menu', function () {
	add_submenu_page(
		'',
		__('Print a File', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
		__('Print a File', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
		User::PRINT_QUICK_PRINT_CAP_KEY,
		QUICK_PRINT_PAGE_SLUG,
		__NAMESPACE__ . '\\render_quick_print_page'
	);
}, 11);

/*
 * WooCommerce redirects users without edit_posts / manage_woocommerce /
 * view_admin_dashboard (Customer, Subscriber) away from wp-admin. Quick Print
 * access can be granted to any role, so let those users through to this one
 * page only; the rest of wp-admin stays blocked for them.
 */
add_filter('woocommerce_prevent_admin_access', function ($prevent) {
	if (!$prevent) {
		return $prevent;
	}

	global $pagenow;
	$page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
	if ('admin.php' === $pagenow && QUICK_PRINT_PAGE_SLUG === $page && User::current_user_can_quick_print()) {
		return false;
	}

	return $prevent;
});

/* Hidden pages get no title from core's menu lookup; set it before admin-header.php prints <title>. */
add_action('load-admin_page_' . QUICK_PRINT_PAGE_SLUG, function () {
	global $title;
	$title = __('Print a File', 'Print-Google-Cloud-Print-GCP-WooCommerce');
});

/*
 * WooCommerce force-hides the toolbar for users without edit_posts /
 * manage_woocommerce, which removes the Print a File shortcut for Customer or
 * Subscriber roles granted Quick Print. Lift only WooCommerce's override, on
 * the front end, for those users; WordPress's own per-user "Show Toolbar" preference still applies.
 */
add_filter('woocommerce_disable_admin_bar', function ($disable) {
	/* WooCommerce's prevent_admin_access() reads this filter too; leave wp-admin
	   to the narrow woocommerce_prevent_admin_access exemption above. */
	if (is_admin()) {
		return $disable;
	}
	return $disable && !User::current_user_can_quick_print();
});

/* Let Admin\Layout render its top-navbar on this page too. */
add_filter('zprint_print_manager_page_slugs', function (array $slugs) {
	$slugs[] = QUICK_PRINT_PAGE_SLUG;
	return $slugs;
});

add_action('admin_enqueue_scripts', function () {
	$current = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
	$is_main_page  = ($current === 'zp_bce0c');
	$is_quick_page = ($current === QUICK_PRINT_PAGE_SLUG);

	if (!$is_main_page && !$is_quick_page) {
		return;
	}

	/* On the quick-print page, setting/index.php's enqueue doesn't fire
	   (it's gated on the zp_bce0c slug), so we bring the chrome CSS here. */
	if ($is_quick_page) {
		wp_enqueue_style('zprint_top_navbar_layout-style', plugins_url('assets/top-navbar-layout.css', PLUGIN_ROOT_FILE), array(), PLUGIN_VERSION);
		wp_enqueue_style('zprint-fa', plugins_url('assets/fa/app.css', PLUGIN_ROOT_FILE), array(), PLUGIN_VERSION);
		wp_enqueue_style('zprint_setting', plugins_url('assets/setting.css', PLUGIN_ROOT_FILE), array(), PLUGIN_VERSION);
		wp_enqueue_style(
			'zprint-admin-modern',
			plugins_url('assets/css/print-manager-admin.css', PLUGIN_ROOT_FILE),
			array('zprint_top_navbar_layout-style', 'zprint_setting'),
			PLUGIN_VERSION
		);
	}

	/* Button + tab-action styles need to load on ALL Print Manager tabs,
	   because the "Print a File" button is rendered into the nav-tab-wrapper
	   on every tab. The page-specific styles only apply where their classes
	   exist — harmless to ship together. */
	Asset::enqueue_style('quick-print', array('zprint-admin-modern'));

	if (!$is_quick_page) {
		return;
	}

	if (!wp_script_is('zprint-i18n', 'registered')) {
		wp_register_script('zprint-i18next', plugins_url('assets/vendor/i18next.min.js', PLUGIN_ROOT_FILE), array(), PLUGIN_VERSION, true);
		wp_register_script('zprint-i18n', plugins_url('assets/i18n.js', PLUGIN_ROOT_FILE), array('zprint-i18next'), PLUGIN_VERSION, true);
		wp_add_inline_script(
			'zprint-i18n',
			'window.ZPRINT_I18N_CONFIG = ' . wp_json_encode(Translate::get_javascript_config()) . ';',
			'before'
		);
	}

	Asset::enqueue_script(
		'quick-print',
		array('jquery', 'zprint-i18n'),
		array(
			'restUrl'          => esc_url_raw(rest_url('zprint/v1/quick-print')),
			'nonce'            => wp_create_nonce('wp_rest'),
			'formats'          => array_values(SupportedFormats::get()),
			'accept'           => SupportedFormats::getAcceptAttribute(),
			'maxBytes'         => QuickPrintSettings::getMaxFileSizeBytes(),
			'maxMb'            => QuickPrintSettings::getMaxFileSizeMB(),
			'defaultPrinterId' => QuickPrintSettings::getDefaultPrinterId(),
			'formatLabels'     => SupportedFormats::getUniqueLabels(),
		)
	);
});

/*
 * Render the Print Manager nav-tab-wrapper on pages that don't go through
 * Aspect\Page::renderPage(). Iterates the main Page's TabPages, links each,
 * and appends the shared new_link (Add new + Print a File buttons). No tab
 * is marked active since we're on a different page.
 */
function render_print_manager_tab_nav()
{
	/* Quick-Print-only users cannot open the management tabs; don't link them. */
	if (!current_user_can(User::PRINT_MANAGEMENT_CAP_KEY)) {
		return;
	}

	$setting_page = AspectPage::get('printer setting');

	$setting_page->scope(function (AspectPage $page) {
		$tabs = $page->getTabs();
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ($tabs as $tab) : ?>
				<a
					href="<?php echo esc_url($page->getUrl($tab)); ?>"
					class="nav-tab"
				><?php echo esc_html($tab->labels['singular_name']); ?></a>
			<?php endforeach; ?>
			<?php if (!empty($page->args['new_link'])) echo $page->args['new_link']; ?>
		</h2>
		<?php
	});
}

function render_quick_print_page()
{
	if (!User::current_user_can_quick_print()) {
		wp_die(esc_html__('You do not have permission to access this page.', 'Print-Google-Cloud-Print-GCP-WooCommerce'), 403);
	}

	$printers           = Printer::getPrinters();
	$has_keys           = Client::hasAccess();
	$default_printer_id = QuickPrintSettings::getDefaultPrinterId();
	?>
	<div class="wrap print-manager-admin">
		<?php /* Dummy h2 absorbs the `.print-manager-admin > h2:first-child { display: none; }`
		         rule from print-manager-admin.css. Without this, the next h2 (our nav-tab-wrapper)
		         gets hidden. Matches what Aspect\Page::renderPage outputs. */ ?>
		<h2><?php echo esc_html(get_admin_page_title()); ?></h2>

		<?php render_print_manager_tab_nav(); ?>

		<div class="zprint-quick-print">
			<?php if (!$has_keys) : ?>
				<div class="notice notice-warning zprint-quick-print__notice">
					<p>
						<?php
						printf(
							wp_kses(
								/* translators: %s: settings page URL. */
								__('BizPrint API keys are not configured. <a href="%s">Add your keys on the Application tab</a> before printing.', 'Print-Google-Cloud-Print-GCP-WooCommerce'),
								array('a' => array('href' => array()))
							),
							esc_url(admin_url('admin.php?page=zp_bce0c&tab=bce0c_3676d'))
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form class="zprint-quick-print__form" enctype="multipart/form-data">
				<label
					class="zprint-quick-print__dropzone"
					data-zprint-dropzone
				>
					<div class="zprint-quick-print__dropzone-heading">
						<?php echo esc_html__('Print a File', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</div>
					<div class="zprint-quick-print__dropzone-icon" aria-hidden="true">
						<i class="fal fa-cloud-upload"></i>
					</div>
					<div class="zprint-quick-print__dropzone-copy">
						<strong><?php echo esc_html__('Drag a file here', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></strong>
						<span><?php echo esc_html__('or click to browse', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
					</div>
					<div class="zprint-quick-print__dropzone-meta" data-zprint-accept-hint></div>
					<input
						type="file"
						class="zprint-quick-print__file-input"
						data-zprint-file
						accept="<?php echo esc_attr(SupportedFormats::getAcceptAttribute()); ?>"
					/>
				</label>

				<div class="zprint-quick-print__selected" data-zprint-selected hidden>
					<span class="zprint-quick-print__selected-icon" aria-hidden="true"><i class="fal fa-file-alt"></i></span>
					<span class="zprint-quick-print__selected-info">
						<span class="zprint-quick-print__selected-name" data-zprint-filename></span>
						<span class="zprint-quick-print__selected-size" data-zprint-filesize></span>
					</span>
					<button type="button" class="zprint-quick-print__selected-remove" data-zprint-remove aria-label="<?php echo esc_attr__('Remove selected file', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>">
						<i class="fal fa-times" aria-hidden="true"></i>
					</button>
				</div>

				<div class="zprint-quick-print__row">
					<label for="zprint-quick-print-printer">
						<?php echo esc_html__('Printer', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</label>
					<select id="zprint-quick-print-printer" data-zprint-printer <?php disabled(empty($printers)); ?>>
						<option value=""><?php echo esc_html__('Select a printer…', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></option>
						<?php foreach ($printers as $printer_id => $label) : ?>
							<option value="<?php echo esc_attr($printer_id); ?>" <?php selected((string) $printer_id, $default_printer_id); ?>><?php echo esc_html($label); ?></option>
						<?php endforeach; ?>
					</select>
					<?php if (empty($printers)) : ?>
						<p class="description">
							<?php echo esc_html__('No printers found. Add printers in the Printers tab or verify your API keys.', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="zprint-quick-print__row">
					<label for="zprint-quick-print-description">
						<?php echo esc_html__('Description (optional)', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>
					</label>
					<input
						type="text"
						id="zprint-quick-print-description"
						data-zprint-description
						maxlength="120"
						placeholder="<?php echo esc_attr__('e.g. Daily report', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?>"
					/>
				</div>

				<div class="zprint-quick-print__progress" data-zprint-progress hidden>
					<div class="zprint-quick-print__progress-bar"><span data-zprint-progress-fill></span></div>
					<span class="zprint-quick-print__progress-label" data-zprint-progress-label></span>
				</div>

				<div class="zprint-quick-print__status" data-zprint-status hidden>
					<span class="zprint-quick-print__status-icon" aria-hidden="true"></span>
					<span class="zprint-quick-print__status-text" data-zprint-status-text></span>
				</div>

				<div class="zprint-quick-print__actions">
					<button
						type="submit"
						class="button button-primary zprint-quick-print__submit"
						data-zprint-submit
						disabled
					>
						<span class="zprint-quick-print__submit-label"><?php echo esc_html__('Send to Printer', 'Print-Google-Cloud-Print-GCP-WooCommerce'); ?></span>
						<span class="zprint-quick-print__submit-spinner" aria-hidden="true"></span>
					</button>
				</div>
			</form>
		</div>
	</div>
	<?php
}
