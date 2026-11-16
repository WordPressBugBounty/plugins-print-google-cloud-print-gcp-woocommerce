<?php

namespace Zprint\Support;

class SupportedFormats
{
	public static function get(): array
	{
		$formats = array(
			array('ext' => 'pdf',  'mime' => 'application/pdf', 'label' => 'PDF'),
			array('ext' => 'html', 'mime' => 'text/html',       'label' => 'HTML'),
			array('ext' => 'htm',  'mime' => 'text/html',       'label' => 'HTML'),
		);

		$formats = apply_filters('zprint_quick_print_supported_formats', $formats);

		return array_values(array_filter((array) $formats, function ($entry) {
			return is_array($entry) && !empty($entry['ext']) && !empty($entry['mime']);
		}));
	}

	public static function getExtensions(): array
	{
		return array_values(array_unique(array_map(function ($entry) {
			return strtolower($entry['ext']);
		}, self::get())));
	}

	public static function getMimeTypes(): array
	{
		return array_values(array_unique(array_map(function ($entry) {
			return strtolower($entry['mime']);
		}, self::get())));
	}

	public static function getAcceptAttribute(): string
	{
		$parts = array();
		foreach (self::get() as $entry) {
			$parts[] = '.' . ltrim($entry['ext'], '.');
			$parts[] = $entry['mime'];
		}
		return implode(',', array_values(array_unique($parts)));
	}

	public static function isExtensionAllowed(string $ext): bool
	{
		return in_array(strtolower(ltrim($ext, '.')), self::getExtensions(), true);
	}

	public static function isMimeAllowed(string $mime): bool
	{
		return in_array(strtolower($mime), self::getMimeTypes(), true);
	}

	public static function getUniqueLabels(): array
	{
		$labels = array_map(function ($entry) {
			return $entry['label'];
		}, self::get());
		return array_values(array_unique($labels));
	}
}
