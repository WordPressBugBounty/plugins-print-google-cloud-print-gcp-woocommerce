<?php

namespace Zprint\Support;

/*
 * Content check for Print a File uploads.
 *
 * wp_check_filetype_and_ext() always finishes with get_allowed_mime_types(),
 * which drops HTML for anyone without unfiltered_html (Shop Manager, most
 * multisite admins), and rejects HTML that fileinfo reports as text/plain.
 * These files are only ever handed to a printer, never published, so the
 * check here is scoped to SupportedFormats and does not depend on that cap.
 * The callback serves HTML sandboxed (see QuickPrint::serveFile).
 */
class UploadValidator
{
	private const SNIFF_BYTES = 8192;

	/* Fileinfo results accepted for each expected type. */
	private const ACCEPTED_REAL_TYPES = array(
		'application/pdf' => array('application/pdf', 'application/octet-stream'), /* octet-stream: header after leading bytes; the %PDF- check above still applies. */
		'text/html'       => array('text/html', 'text/plain', 'text/xml', 'application/xml', 'application/xhtml+xml'),
	);

	/**
	 * @return array{ext: string, type: string}|null Null when the file is not an allowed, genuine format.
	 */
	public static function validate(string $path, string $filename): ?array
	{
		$ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
		if ($ext === '' || !is_file($path) || filesize($path) === 0) {
			return null;
		}

		$type = null;
		foreach (SupportedFormats::get() as $entry) {
			if (strtolower($entry['ext']) === $ext) {
				$type = strtolower($entry['mime']);
				break;
			}
		}
		if ($type === null) {
			return null;
		}

		$head = (string) file_get_contents($path, false, null, 0, self::SNIFF_BYTES);

		if ($type === 'application/pdf') {
			/* The PDF header may follow up to 1 KB of leading bytes. */
			if (strpos(substr($head, 0, 1024), '%PDF-') === false) {
				return null;
			}
		} elseif ($type === 'text/html') {
			if (strpos($head, "\0") !== false) {
				return null;
			}
		}

		$real = self::realType($path);
		$accepted = self::ACCEPTED_REAL_TYPES[$type] ?? array($type);
		if ($real !== null && !in_array($real, $accepted, true)) {
			return null;
		}
		if ($real === null && !isset(self::ACCEPTED_REAL_TYPES[$type])) {
			/* Formats added through the filter need fileinfo to vouch for them. */
			return null;
		}

		return array('ext' => $ext, 'type' => $type);
	}

	private static function realType(string $path): ?string
	{
		if (!class_exists('finfo')) {
			return null;
		}
		$real = (new \finfo(FILEINFO_MIME_TYPE))->file($path);

		return is_string($real) && $real !== '' ? strtolower($real) : null;
	}
}
