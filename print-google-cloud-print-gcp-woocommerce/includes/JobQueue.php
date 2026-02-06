<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;

class JobQueue
{
	const STATUS_PENDING = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_COMPLETED = 'completed';
	const STATUS_FAILED = 'failed';

	const DEFAULT_MAX_ATTEMPTS = 5;

	/**
	 * Retry delay schedule in seconds for each attempt.
	 */
	const RETRY_DELAYS = [
		1 => 0,       // Immediate
		2 => 30,      // 30 seconds
		3 => 120,     // 2 minutes
		4 => 600,     // 10 minutes
		5 => 3600,    // 1 hour
	];

	/**
	 * Check if async printing is enabled.
	 *
	 * @return bool
	 */
	public static function isAsyncEnabled()
	{
		return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$general = TabPage::get('general');
				$networkBox = Box::get('network settings');

				$asyncInput = Input::get('enable async printing');
				$asyncValue = $asyncInput->getValue($networkBox, null, $general);
				return is_array($asyncValue) && in_array('1', $asyncValue);
			});
		});
	}

	/**
	 * Check if fallback queue is enabled (queue on sync failure).
	 *
	 * @return bool
	 */
	public static function isFallbackEnabled()
	{
		return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$general = TabPage::get('general');
				$networkBox = Box::get('network settings');

				$fallbackInput = Input::get('enable fallback queue');
				$fallbackValue = $fallbackInput->getValue($networkBox, null, $general);
				return is_array($fallbackValue) && in_array('1', $fallbackValue);
			});
		});
	}

	/**
	 * Add a print job to the queue.
	 *
	 * @param int $orderId WooCommerce order ID
	 * @param int $locationId Print location ID
	 * @param string $printerId Printer ID
	 * @param array $jobData Print job data (description, url, printOption)
	 * @param string|null $lastError Optional error message from previous attempt
	 * @return int|false Job ID on success, false on failure
	 */
	public static function addJob($orderId, $locationId, $printerId, array $jobData, $lastError = null)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();
		$now = current_time('mysql');

		$result = $wpdb->insert(
			$table,
			[
				'order_id' => $orderId,
				'location_id' => $locationId,
				'printer_id' => $printerId,
				'job_data' => maybe_serialize($jobData),
				'status' => self::STATUS_PENDING,
				'attempts' => $lastError ? 1 : 0,
				'max_attempts' => self::DEFAULT_MAX_ATTEMPTS,
				'last_error' => $lastError,
				'scheduled_at' => $now,
				'created_at' => $now,
			],
			['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
		);

		if ($result) {
			$jobId = $wpdb->insert_id;
			Log::info(Log::BASIC, [
				'Job Queue',
				'Job added',
				'Job ID: ' . $jobId,
				'Order ID: ' . $orderId,
				'Printer ID: ' . $printerId,
			]);

			return $jobId;
		}

		Log::error(Log::BASIC, [
			'Job Queue',
			'Failed to add job',
			'Order ID: ' . $orderId,
			'Error: ' . $wpdb->last_error,
		]);

		return false;
	}

	/**
	 * Get pending jobs ready for processing.
	 *
	 * @param int $limit Maximum number of jobs to return
	 * @return array Array of job objects
	 */
	public static function getPendingJobs($limit = 10)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();
		$now = current_time('mysql');

		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$table}`
				WHERE status = %s
				AND scheduled_at <= %s
				ORDER BY scheduled_at ASC
				LIMIT %d",
				self::STATUS_PENDING,
				$now,
				$limit
			)
		);

		return $jobs ?: [];
	}

	/**
	 * Get a single job by ID.
	 *
	 * @param int $jobId
	 * @return object|null
	 */
	public static function getJob($jobId)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE id = %d",
				$jobId
			)
		);
	}

	/**
	 * Mark a job as processing.
	 *
	 * @param int $jobId
	 * @return bool
	 */
	public static function markProcessing($jobId)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();

		return (bool) $wpdb->update(
			$table,
			['status' => self::STATUS_PROCESSING],
			['id' => $jobId],
			['%s'],
			['%d']
		);
	}

	/**
	 * Mark a job as completed.
	 *
	 * @param int $jobId
	 * @return bool
	 */
	public static function markCompleted($jobId)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();

		$result = $wpdb->update(
			$table,
			['status' => self::STATUS_COMPLETED],
			['id' => $jobId],
			['%s'],
			['%d']
		);

		if ($result) {
			Log::info(Log::BASIC, [
				'Job Queue',
				'Job completed',
				'Job ID: ' . $jobId,
			]);
		}

		return (bool) $result;
	}

	/**
	 * Mark a job as failed and schedule retry if attempts remain.
	 *
	 * @param int $jobId
	 * @param string $errorMessage
	 * @return bool
	 */
	public static function markFailed($jobId, $errorMessage)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();
		$job = self::getJob($jobId);

		if (!$job) {
			return false;
		}

		$attempts = (int) $job->attempts + 1;
		$maxAttempts = (int) $job->max_attempts;

		if ($attempts >= $maxAttempts) {
			// Max attempts reached, mark as permanently failed
			$result = $wpdb->update(
				$table,
				[
					'status' => self::STATUS_FAILED,
					'attempts' => $attempts,
					'last_error' => $errorMessage,
				],
				['id' => $jobId],
				['%s', '%d', '%s'],
				['%d']
			);

			Log::error(Log::BASIC, [
				'Job Queue',
				'Job permanently failed',
				'Job ID: ' . $jobId,
				'Attempts: ' . $attempts . '/' . $maxAttempts,
				'Error: ' . $errorMessage,
			]);
		} else {
			// Schedule retry with backoff
			$delay = isset(self::RETRY_DELAYS[$attempts + 1])
				? self::RETRY_DELAYS[$attempts + 1]
				: 3600;

			$scheduledAt = date('Y-m-d H:i:s', strtotime(current_time('mysql')) + $delay);

			$result = $wpdb->update(
				$table,
				[
					'status' => self::STATUS_PENDING,
					'attempts' => $attempts,
					'last_error' => $errorMessage,
					'scheduled_at' => $scheduledAt,
				],
				['id' => $jobId],
				['%s', '%d', '%s', '%s'],
				['%d']
			);

			Log::info(Log::BASIC, [
				'Job Queue',
				'Job scheduled for retry',
				'Job ID: ' . $jobId,
				'Attempt: ' . $attempts . '/' . $maxAttempts,
				'Next retry: ' . $scheduledAt,
				'Error: ' . $errorMessage,
			]);

			// Schedule background processing for the retry time
			self::scheduleProcessing($delay);
		}

		return (bool) $result;
	}

	/**
	 * Manually retry a failed job.
	 *
	 * @param int $jobId
	 * @return bool
	 */
	public static function retryJob($jobId)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();
		$job = self::getJob($jobId);

		if (!$job || $job->status !== self::STATUS_FAILED) {
			return false;
		}

		$result = $wpdb->update(
			$table,
			[
				'status' => self::STATUS_PENDING,
				'attempts' => 0,
				'last_error' => null,
				'scheduled_at' => current_time('mysql'),
			],
			['id' => $jobId],
			['%s', '%d', '%s', '%s'],
			['%d']
		);

		if ($result) {
			Log::info(Log::BASIC, [
				'Job Queue',
				'Job manually retried',
				'Job ID: ' . $jobId,
			]);

			self::scheduleProcessing();
		}

		return (bool) $result;
	}

	/**
	 * Delete a job from the queue.
	 *
	 * @param int $jobId
	 * @return bool
	 */
	public static function deleteJob($jobId)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();

		return (bool) $wpdb->delete(
			$table,
			['id' => $jobId],
			['%d']
		);
	}

	/**
	 * Get queue statistics.
	 *
	 * @return array
	 */
	public static function getStats()
	{
		global $wpdb;

		$table = DB::getJobQueueTable();

		$stats = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM `{$table}` GROUP BY status"
		);

		$result = [
			'pending' => 0,
			'processing' => 0,
			'completed' => 0,
			'failed' => 0,
			'total' => 0,
		];

		foreach ($stats as $stat) {
			$result[$stat->status] = (int) $stat->count;
			$result['total'] += (int) $stat->count;
		}

		return $result;
	}

	/**
	 * Get jobs with optional filtering.
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public static function getJobs($args = [])
	{
		global $wpdb;

		$defaults = [
			'status' => null,
			'order_id' => null,
			'limit' => 50,
			'offset' => 0,
			'orderby' => 'created_at',
			'order' => 'DESC',
		];

		$args = wp_parse_args($args, $defaults);
		$table = DB::getJobQueueTable();

		$where = [];
		$params = [];

		if ($args['status']) {
			$where[] = 'status = %s';
			$params[] = $args['status'];
		}

		if ($args['order_id']) {
			$where[] = 'order_id = %d';
			$params[] = $args['order_id'];
		}

		$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
		$orderby = in_array($args['orderby'], ['created_at', 'scheduled_at', 'attempts'])
			? $args['orderby']
			: 'created_at';
		$order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

		$query = "SELECT * FROM `{$table}` {$whereClause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = $args['limit'];
		$params[] = $args['offset'];

		$jobs = $wpdb->get_results($wpdb->prepare($query, $params));

		return $jobs ?: [];
	}

	/**
	 * Clear jobs of a specific status older than specified days.
	 *
	 * @param string $status Job status to clear
	 * @param int $days Age threshold in days
	 * @return int Number of deleted rows
	 */
	public static function clearOldJobs($status, $days = 7)
	{
		global $wpdb;

		$table = DB::getJobQueueTable();
		$cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `{$table}` WHERE status = %s AND created_at < %s",
				$status,
				$cutoff
			)
		);
	}

	/**
	 * Clear completed jobs older than specified days.
	 * @deprecated Use clearOldJobs() instead
	 *
	 * @param int $days
	 * @return int Number of deleted rows
	 */
	public static function clearCompleted($days = 7)
	{
		return self::clearOldJobs(self::STATUS_COMPLETED, $days);
	}

	/**
	 * Schedule background processing via Action Scheduler.
	 * Used for delayed retries only.
	 *
	 * @param int $delay Delay in seconds
	 */
	public static function scheduleProcessing($delay = 0)
	{
		if ($delay === 0) {
			// For immediate processing, don't schedule - caller should process directly
			return;
		}

		// Schedule via Action Scheduler for delayed retries
		if (function_exists('as_schedule_single_action')) {
			$hook = 'zprint_process_job_queue';
			$timestamp = time() + $delay;

			// Check if already scheduled
			$existing = as_next_scheduled_action($hook);
			if (!$existing || $existing > $timestamp + 60) {
				as_schedule_single_action($timestamp, $hook);
			}
		}
	}

	/**
	 * Process a specific job immediately.
	 *
	 * @param int $jobId
	 * @return bool True if successful, false otherwise
	 */
	public static function processJobNow($jobId)
	{
		$job = self::getJob($jobId);
		if (!$job || $job->status !== self::STATUS_PENDING) {
			return false;
		}

		$printerId = $job->printer_id;
		$jobData = self::getJobData($job);

		Log::info(Log::BASIC, [
			'Job Queue',
			'Processing job immediately',
			'Job ID: ' . $jobId,
			'Order ID: ' . $job->order_id,
		]);

		// Mark as processing
		self::markProcessing($jobId);

		try {
			// Prepare request data
			$requestData = array_merge($jobData, ['printerId' => $printerId]);

			// Send to print service
			$response = Client::postRequest('jobs', $requestData);

			if (isset($response->job)) {
				Log::info(Log::PRINTING, [
					$response->job->description,
					'create with ' . $response->job->status,
					'Job ' . $response->job->id,
					'(queued - immediate)',
				]);
				self::markCompleted($jobId);
				return true;
			} elseif (isset($response->errorCode)) {
				$errorMessage = $response->errorCode . ': ' . ($response->message ?? 'Unknown error');
				Log::warn(Log::PRINTING, [$response->errorCode, $response->message, '(queued)']);
				self::markFailed($jobId, $errorMessage);
				return false;
			} else {
				self::markFailed($jobId, 'Unexpected response from print service');
				return false;
			}
		} catch (\Exception $e) {
			$errorMessage = $e->getMessage();
			Log::error(Log::BASIC, [
				'Job Queue',
				'Job failed',
				'Job ID: ' . $jobId,
				'Error: ' . $errorMessage,
			]);
			self::markFailed($jobId, $errorMessage);
			return false;
		}
	}

	/**
	 * Get the unserialized job data.
	 *
	 * @param object $job
	 * @return array
	 */
	public static function getJobData($job)
	{
		return maybe_unserialize($job->job_data);
	}
}
