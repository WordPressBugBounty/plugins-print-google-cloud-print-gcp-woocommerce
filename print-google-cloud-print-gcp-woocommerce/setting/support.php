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

	// Job Queue section
	$jobQueue = new Box('job queue');
	$jobQueue
		->setLabel('singular_name', __('Print Job Queue', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
		->attachTo($support)
		->scope(function ($jobQueue) use ($support, $setting_page) {
			$stats = JobQueue::getStats();

			$statsInfo = new Input('stats');
			$statsInfo
				->setLabel('singular_name', __('Queue Status', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setType(Input::TYPE_INFO)
				->setArgument('content', function () use ($stats) {
					$html = '<div class="zprint-queue-stats">';
					$html .= sprintf('<span class="stat"><strong>%s:</strong> %d</span> | ', __('Pending', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $stats['pending']);
					$html .= sprintf('<span class="stat"><strong>%s:</strong> %d</span> | ', __('Processing', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $stats['processing']);
					$html .= sprintf('<span class="stat"><strong>%s:</strong> %d</span> | ', __('Completed', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $stats['completed']);
					$html .= sprintf('<span class="stat" style="color: %s;"><strong>%s:</strong> %d</span>', $stats['failed'] > 0 ? '#dc3232' : 'inherit', __('Failed', 'Print-Google-Cloud-Print-GCP-WooCommerce'), $stats['failed']);
					$html .= '</div>';
					return $html;
				})
				->attachTo($jobQueue);

			// Show failed jobs if any
			if ($stats['failed'] > 0) {
				$failedJobs = JobQueue::getJobs(['status' => JobQueue::STATUS_FAILED, 'limit' => 10]);
				$failedInfo = new Input('failed jobs');
				$failedInfo
					->setLabel('singular_name', __('Failed Jobs', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
					->setType(Input::TYPE_INFO)
					->setArgument('content', function () use ($failedJobs) {
						$html = '<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">';
						$html .= '<thead><tr>';
						$html .= '<th style="width: 50px;">' . __('ID', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</th>';
						$html .= '<th style="width: 80px;">' . __('Order', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</th>';
						$html .= '<th style="width: 80px;">' . __('Attempts', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</th>';
						$html .= '<th>' . __('Last Error', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</th>';
						$html .= '<th style="width: 100px;">' . __('Actions', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</th>';
						$html .= '</tr></thead><tbody>';

						foreach ($failedJobs as $job) {
							$html .= '<tr>';
							$html .= '<td>' . esc_html($job->id) . '</td>';
							$html .= '<td><a href="' . esc_url(admin_url('post.php?post=' . $job->order_id . '&action=edit')) . '">#' . esc_html($job->order_id) . '</a></td>';
							$html .= '<td>' . esc_html($job->attempts) . '/' . esc_html($job->max_attempts) . '</td>';
							$html .= '<td style="font-size: 12px;">' . esc_html(substr($job->last_error, 0, 100)) . (strlen($job->last_error) > 100 ? '...' : '') . '</td>';
							$html .= '<td>';
							$html .= '<button type="button" class="button button-small zprint-retry-job" data-job-id="' . esc_attr($job->id) . '">' . __('Retry', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</button> ';
							$html .= '<button type="button" class="button button-small zprint-delete-job" data-job-id="' . esc_attr($job->id) . '">' . __('Delete', 'Print-Google-Cloud-Print-GCP-WooCommerce') . '</button>';
							$html .= '</td>';
							$html .= '</tr>';
						}

						$html .= '</tbody></table>';
						return $html;
					})
					->attachTo($jobQueue);
			}

			// Process queue button
			$processQueue = new Input('process queue');
			$processQueue
				->setLabel('singular_name', __('Manual Processing', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setLabel('button_name', __('Process Queue Now', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setArgument('disabled', $stats['pending'] === 0)
				->attachTo($jobQueue)
				->setType(Input::TYPE_SMART_BUTTON);

			add_filter('\Zprint\Aspect\Input\saveBefore', function ($data, $object, $key_name) use ($processQueue) {
				if ($object === $processQueue && $data) {
					BackgroundPrintProcessor::triggerProcessing();
				}
				return $data;
			}, 10, 3);

			// Clear completed jobs button
			$clearCompleted = new Input('clear completed');
			$clearCompleted
				->setLabel('singular_name', __('Cleanup Completed', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setLabel('button_name', __('Clear Completed Jobs', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setArgument('disabled', $stats['completed'] === 0)
				->attachTo($jobQueue)
				->setType(Input::TYPE_SMART_BUTTON);

			add_filter('\Zprint\Aspect\Input\saveBefore', function ($data, $object, $key_name) use ($clearCompleted) {
				if ($object === $clearCompleted && $data) {
					JobQueue::clearOldJobs(JobQueue::STATUS_COMPLETED, 0);
				}
				return $data;
			}, 10, 3);

			// Clear all jobs button
			$clearAll = new Input('clear all');
			$clearAll
				->setLabel('singular_name', __('Cleanup All', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setLabel('button_name', __('Clear All Jobs', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->setArgument('disabled', $stats['total'] === 0)
				->setArgument('description', __('Removes all jobs from queue (completed, pending, and failed).', 'Print-Google-Cloud-Print-GCP-WooCommerce'))
				->attachTo($jobQueue)
				->setType(Input::TYPE_SMART_BUTTON);

			add_filter('\Zprint\Aspect\Input\saveBefore', function ($data, $object, $key_name) use ($clearAll) {
				if ($object === $clearAll && $data) {
					JobQueue::clearOldJobs(JobQueue::STATUS_COMPLETED, 0);
					JobQueue::clearOldJobs(JobQueue::STATUS_PENDING, 0);
					JobQueue::clearOldJobs(JobQueue::STATUS_FAILED, 0);
					JobQueue::clearOldJobs(JobQueue::STATUS_PROCESSING, 0);
				}
				return $data;
			}, 10, 3);
		});

	// AJAX handlers for job queue actions
	add_action('wp_ajax_zprint_retry_job', function () {
		check_ajax_referer('zprint_queue_action', 'nonce');

		$jobId = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;

		if ($jobId && JobQueue::retryJob($jobId)) {
			wp_send_json_success(['message' => __('Job queued for retry', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
		} else {
			wp_send_json_error(['message' => __('Failed to retry job', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
		}
	});

	add_action('wp_ajax_zprint_delete_job', function () {
		check_ajax_referer('zprint_queue_action', 'nonce');

		$jobId = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;

		if ($jobId && JobQueue::deleteJob($jobId)) {
			wp_send_json_success(['message' => __('Job deleted', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
		} else {
			wp_send_json_error(['message' => __('Failed to delete job', 'Print-Google-Cloud-Print-GCP-WooCommerce')]);
		}
	});

	// Add inline script for job queue actions
	if ($setting_page->isRequested($support)) {
		add_action('admin_footer', function () {
			$nonce = wp_create_nonce('zprint_queue_action');
			?>
			<script>
			jQuery(function($) {
				$('.zprint-retry-job').on('click', function() {
					var $btn = $(this);
					var jobId = $btn.data('job-id');
					$btn.prop('disabled', true).text('<?php echo esc_js(__('Retrying...', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>');

					$.post(ajaxurl, {
						action: 'zprint_retry_job',
						job_id: jobId,
						nonce: '<?php echo $nonce; ?>'
					}, function(response) {
						if (response.success) {
							$btn.closest('tr').fadeOut(function() { $(this).remove(); });
						} else {
							alert(response.data.message);
							$btn.prop('disabled', false).text('<?php echo esc_js(__('Retry', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>');
						}
					});
				});

				$('.zprint-delete-job').on('click', function() {
					if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this job?', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>')) {
						return;
					}

					var $btn = $(this);
					var jobId = $btn.data('job-id');
					$btn.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>');

					$.post(ajaxurl, {
						action: 'zprint_delete_job',
						job_id: jobId,
						nonce: '<?php echo $nonce; ?>'
					}, function(response) {
						if (response.success) {
							$btn.closest('tr').fadeOut(function() { $(this).remove(); });
						} else {
							alert(response.data.message);
							$btn.prop('disabled', false).text('<?php echo esc_js(__('Delete', 'Print-Google-Cloud-Print-GCP-WooCommerce')); ?>');
						}
					});
				});
			});
			</script>
			<?php
		});
	}
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
