<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\Page;
use Zprint\Debug\Tab;
use Zprint\Model\Location;

return function (Page $setting_page) {
	$support = new TabPage( 'support' );
	$support
		->setLabel( 'singular_name', __( 'Support', 'Print-Google-Cloud-Print-GCP-WooCommerce' ) )
		->attachTo( $setting_page )
		->setArgument('contentPage', function () {
			$orders_ids = wc_get_orders([
				'limit' => 100,
				'return' => 'ids'
			]);

			Tab::render($orders_ids, Location::getAllFormatted());
		});

	if ($setting_page->isRequested($support)) {
		add_action('admin_enqueue_scripts', function () {
			wp_enqueue_script('zprint-clipboard', plugins_url('assets/clipboard.js', PLUGIN_ROOT_FILE), [], PLUGIN_VERSION);
		});
	}

	$view_log_text = __('View log', 'Print-Google-Cloud-Print-GCP-WooCommerce');
	$copy_log_text = __('Copy log', 'Print-Google-Cloud-Print-GCP-WooCommerce');

	$print = new Box('print');
	$print
		->setLabel('singular_name', __('Print', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($support)
		->scope(function ($print) use ($support, $setting_page, $view_log_text, $copy_log_text) {
			$input = new Input('active');
			$input
				->setLabel('singular_name', __('Active', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setType(Input::TYPE_CHECKBOX)
				->attach([1, __('Save info logs', 'Print-Google-Cloud-Print-GCP-WooCommerce')])
				->attachTo($print)
				->setArgument('default', false);

			$log_exists = file_exists(Log::getPrintLogFilePath()) && filesize(Log::getPrintLogFilePath());
			$link = new Input('link');
			$link
				->setLabel('singular_name', __('Log file content', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setType(Input::TYPE_INFO)
				->setArgument('content', zprint_get_log_file_content_html(
						Log::getPrintLogFilePath(false),
						Log::PRINTING,
						$view_log_text,
						$copy_log_text,
						$log_exists
					)
				)
				->attachTo($print);

			$clear = new Input('clear');
			$clear
				->setLabel('singular_name', __('Log file', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setLabel('button_name', __('Clear log file', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setArgument('disabled', !$log_exists)
				->attachTo($print)
				->setType(Input::TYPE_SMART_BUTTON);

			if ($setting_page->isRequested($support)) {
				$size = human_filesize(file_exists(Log::getPrintLogFilePath()) ? filesize(Log::getPrintLogFilePath()): 0);
				$clear->setLabel('singular_name', sprintf(__('Log file (Size: %s)', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $size));
			}
			add_filter('\Zprint\Aspect\Input\saveBefore', function ($data, $object, $key_name) use ($clear) {
				if ($object === $clear && $data) {
					file_put_contents(Log::getPrintLogFilePath(), '');
				}
				return $data;
			}, 10, 3);
		});

	$plugin = new Box('plugin');
	$plugin
		->attachTo($support)
		->setLabel('singular_name', __('Plugin', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->scope(function ($plugin) use ($support, $setting_page, $view_log_text, $copy_log_text) {
			$log_exists = file_exists(Log::getBasicLogFilePath()) && filesize(Log::getBasicLogFilePath());
			$link = new Input('link');
			$link
				->setLabel('singular_name', __('Log file content', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setType(Input::TYPE_INFO)
				->setArgument('content', zprint_get_log_file_content_html(
						Log::getBasicLogFilePath(false),
						Log::BASIC,
						$view_log_text,
						$copy_log_text,
						$log_exists
					)
				)
				->attachTo($plugin);

			$clear = new Input('clear');
			$clear
				->setLabel('singular_name', __('Log file', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setLabel('button_name', __('Clear log file', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setArgument('disabled', !$log_exists)
				->attachTo($plugin)
				->setType(Input::TYPE_SMART_BUTTON);

			if ($setting_page->isRequested($support)) {
				$size = human_filesize(file_exists(Log::getBasicLogFilePath()) ? filesize(Log::getBasicLogFilePath()) : 0);
				$clear->setLabel('singular_name', sprintf(__('Log file (Size: %s)', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $size));
			}
			add_filter('\Zprint\Aspect\Input\saveBefore', function ($data, $object, $key_name) use ($clear) {
				if ($object === $clear && $data) {
					file_put_contents(Log::getBasicLogFilePath(), '');
				}
				return $data;
			}, 10, 3);

			$input = new Input('reset');
			$input
				->setLabel('singular_name', __('Delete Data and Reset', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setType(Input::TYPE_CHECKBOX)
				->attach([1, __('Yes, Delete data and reset settings when plugin is deactivated and deleted. All settings will be deleted.', 'Print-Google-Cloud-Print-GCP-WooCommerce')])
				->attachTo($plugin)
				->setArgument('default', false);
		});

	add_action('wp_ajax_zprint_copy_log', function () {
		$type = $_POST['type'] ?? '';

		if (Log::BASIC === $type) {
			$path = Log::getBasicLogFilePath();
		} elseif (Log::PRINTING === $type) {
			$path = Log::getPrintLogFilePath();
		} else {
			$path = '';
		}

		echo $path ? file_get_contents($path) : '';

		wp_die();
	});
};

function human_filesize($bytes, $decimals = 2)
{
	$sz = 'BKMGTP';
	$factor = floor((strlen($bytes) - 1) / 3);
	/**
	 * @todo sting offset is bad practice
	 */
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

function zprint_get_log_file_content_html(string $file_url, string $log_type, string $view_log_text, string $copy_log_text, bool $log_exists )
{
	$disabled = $log_exists ? '' : 'disabled';

	$view_log_button = sprintf(
		'<a class="button %s" href="%s" target="_blank" %s>%s</a>',
		$disabled,
		$file_url,
		$log_exists ? '' : 'style="pointer-events: none"',
		$log_exists ? $view_log_text : __('Log is empty', 'Print-Google-Cloud-Print-GCP-WooCommerce')
	);

	$copy_log_button = sprintf(
		'<button type="button" class="button %s zprint-copy-log-button" data-log-type="%s" style="margin-left: 20px; display: none">%s</button>',
		$disabled,
		$log_type,
		$copy_log_text
	);

	return $view_log_button . $copy_log_button;
}
