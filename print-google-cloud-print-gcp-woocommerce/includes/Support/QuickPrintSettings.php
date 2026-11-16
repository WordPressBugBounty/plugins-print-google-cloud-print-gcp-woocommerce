<?php

namespace Zprint\Support;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;
use Zprint\Input;
use Zprint\TabPage;

class QuickPrintSettings
{
	const DEFAULT_MAX_MB = 2;
	const HARD_MAX_MB    = 50;

	const DEFAULT_RETENTION_HOURS = 24;
	const MAX_RETENTION_HOURS     = 720;

	public static function getMaxFileSizeBytes(): int
	{
		$mb = self::getMaxFileSizeMB();
		return $mb * 1024 * 1024;
	}

	public static function getDefaultPrinterId(): string
	{
		$value = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$tab   = TabPage::get('setting');
				$box   = Box::get('quick print limits');
				$input = Input::get('quick print default printer');
				return $input->getValue($box, null, $tab);
			});
		});

		return (string) apply_filters('zprint_quick_print_default_printer', (string) $value);
	}

	public static function getMaxFileSizeMB(): int
	{
		$value = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$tab   = TabPage::get('setting');
				$box   = Box::get('quick print limits');
				$input = Input::get('quick print max file size');
				return $input->getValue($box, null, $tab);
			});
		});

		$mb = (int) $value;
		if ($mb < 1) {
			$mb = self::DEFAULT_MAX_MB;
		}
		if ($mb > self::HARD_MAX_MB) {
			$mb = self::HARD_MAX_MB;
		}

		$mb = (int) apply_filters('zprint_quick_print_max_file_size', $mb);

		if ($mb < 1) {
			$mb = self::DEFAULT_MAX_MB;
		}
		if ($mb > self::HARD_MAX_MB) {
			$mb = self::HARD_MAX_MB;
		}

		return $mb;
	}

	/* How long an uploaded file stays fetchable. Print clients fetch when they
	   handle the job, which for an offline printer can be long after upload,
	   and again on Reprint. */
	public static function getRetentionHours(): int
	{
		$value = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function () {
				$tab   = TabPage::get('setting');
				$box   = Box::get('quick print limits');
				$input = Input::get('quick print retention hours');
				return $input->getValue($box, null, $tab);
			});
		});

		$hours = (int) apply_filters('zprint_quick_print_retention_hours', (int) $value);
		if ($hours < 1) {
			$hours = self::DEFAULT_RETENTION_HOURS;
		}

		return min($hours, self::MAX_RETENTION_HOURS);
	}
}
