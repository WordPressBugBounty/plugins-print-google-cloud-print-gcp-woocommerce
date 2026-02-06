<?php

namespace Zprint;

use Exception;

class BackgroundPrintProcessor
{
	const HOOK_PROCESS_QUEUE = 'zprint_process_job_queue';
	const BATCH_SIZE = 5;

	/**
	 * Initialize the background processor.
	 */
	public function __construct()
	{
		// Register the Action Scheduler hook for delayed retries
		add_action(self::HOOK_PROCESS_QUEUE, [$this, 'processQueue']);

		// Schedule recurring cleanup of old completed jobs
		add_action('init', [$this, 'scheduleCleanup']);
		add_action('zprint_cleanup_completed_jobs', [$this, 'cleanupCompletedJobs']);
	}

	/**
	 * Process pending jobs from the queue.
	 */
	public function processQueue()
	{
		$jobs = JobQueue::getPendingJobs(self::BATCH_SIZE);

		if (empty($jobs)) {
			return;
		}

		Log::info(Log::BASIC, [
			'Background Processor',
			'Processing batch',
			'Jobs: ' . count($jobs),
		]);

		foreach ($jobs as $job) {
			$this->processJob($job);
		}

		// Check if there are more pending jobs
		$remaining = JobQueue::getPendingJobs(1);
		if (!empty($remaining)) {
			// Schedule another batch
			JobQueue::scheduleProcessing(5);
		}
	}

	/**
	 * Process a single job.
	 *
	 * @param object $job
	 */
	private function processJob($job)
	{
		$jobId = (int) $job->id;
		$orderId = (int) $job->order_id;
		$printerId = $job->printer_id;
		$jobData = JobQueue::getJobData($job);

		Log::info(Log::BASIC, [
			'Background Processor',
			'Processing job',
			'Job ID: ' . $jobId,
			'Order ID: ' . $orderId,
			'Attempt: ' . ((int) $job->attempts + 1) . '/' . $job->max_attempts,
		]);

		// Mark as processing
		JobQueue::markProcessing($jobId);

		try {
			// Prepare the request data
			$requestData = array_merge($jobData, ['printerId' => $printerId]);

			// Send to print service via Client
			$response = Client::postRequest('jobs', $requestData);

			if (isset($response->job)) {
				// Success
				Log::info(Log::PRINTING, [
					$response->job->description,
					'create with ' . $response->job->status,
					'Job ' . $response->job->id,
					'(queued)',
				]);

				JobQueue::markCompleted($jobId);
			} elseif (isset($response->errorCode)) {
				// API error response
				$errorMessage = $response->errorCode . ': ' . ($response->message ?? 'Unknown error');
				Log::warn(Log::PRINTING, [$response->errorCode, $response->message, '(queued)']);
				JobQueue::markFailed($jobId, $errorMessage);
			} else {
				// Unexpected response
				JobQueue::markFailed($jobId, 'Unexpected response from print service');
			}
		} catch (Exception $e) {
			// Network or other exception
			$errorMessage = $e->getMessage();

			Log::error(Log::BASIC, [
				'Background Processor',
				'Job failed',
				'Job ID: ' . $jobId,
				'Error: ' . $errorMessage,
			]);

			JobQueue::markFailed($jobId, $errorMessage);
		}
	}

	/**
	 * Schedule weekly cleanup of old completed jobs.
	 */
	public function scheduleCleanup()
	{
		if (!function_exists('as_next_scheduled_action')) {
			return;
		}

		$hook = 'zprint_cleanup_completed_jobs';

		if (!as_next_scheduled_action($hook)) {
			as_schedule_recurring_action(
				strtotime('tomorrow 3:00am'),
				DAY_IN_SECONDS,
				$hook
			);
		}
	}

	/**
	 * Clean up old jobs (completed and stale pending).
	 */
	public function cleanupCompletedJobs()
	{
		$deletedCompleted = JobQueue::clearOldJobs(JobQueue::STATUS_COMPLETED, 7);
		$deletedPending = JobQueue::clearOldJobs(JobQueue::STATUS_PENDING, 7);
		$deletedFailed = JobQueue::clearOldJobs(JobQueue::STATUS_FAILED, 30);

		$total = $deletedCompleted + $deletedPending + $deletedFailed;

		if ($total > 0) {
			Log::info(Log::BASIC, [
				'Background Processor',
				'Cleaned up old jobs',
				'Completed: ' . $deletedCompleted,
				'Pending: ' . $deletedPending,
				'Failed: ' . $deletedFailed,
			]);
		}
	}

	/**
	 * Manually trigger processing of the queue.
	 * Processes all pending jobs immediately.
	 */
	public static function triggerProcessing()
	{
		$jobs = JobQueue::getPendingJobs(50);

		if (empty($jobs)) {
			return 0;
		}

		$processed = 0;
		foreach ($jobs as $job) {
			JobQueue::processJobNow((int) $job->id);
			$processed++;
		}

		Log::info(Log::BASIC, [
			'Background Processor',
			'Manual processing triggered',
			'Jobs processed: ' . $processed,
		]);

		return $processed;
	}
}
