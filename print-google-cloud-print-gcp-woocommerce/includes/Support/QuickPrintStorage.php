<?php

namespace Zprint\Support;

/*
 * On-disk storage for Print a File uploads.
 *
 * BizPrint never downloads the file itself: it hands the job url to the
 * print client, which fetches it whenever it handles the job. That can be
 * more than once (Electron sends HEAD then GET; a reconnecting station
 * resends pending jobs; Reprint resends), and much later than the upload
 * when a printer is offline. So files are kept for a retention window and
 * removed by an age-based sweep, never right after dispatch.
 *
 * Nothing but the folder itself is needed to find or clean up a file: the
 * name on disk is an HMAC of the public id, so a leaked job url does not
 * reveal a direct path, and cleanup works even if scheduled events or
 * caches are lost.
 */
class QuickPrintStorage
{
	public const QUERY_ARG = 'zprint_quickprint_file';
	public const SUBDIR    = 'bizprint-quickprint';

	private const ID_PATTERN = '/^[a-z0-9]{32}$/';

	/**
	 * @param array $upload_dir Result of wp_upload_dir().
	 */
	public static function baseDir(array $upload_dir): string
	{
		$default = rtrim((string) ($upload_dir['basedir'] ?? ''), '/\\') . '/' . self::SUBDIR;

		/* Lets a host keep uploads outside the web root. */
		$dir = (string) apply_filters('zprint_quick_print_storage_dir', $default);

		return rtrim($dir, '/\\');
	}

	public static function newId(): string
	{
		return strtolower(wp_generate_password(32, false, false));
	}

	public static function isValidId(string $id): bool
	{
		return (bool) preg_match(self::ID_PATTERN, $id);
	}

	/**
	 * @param callable $move Moves $source to the target path, returning bool (move_uploaded_file in production).
	 * @return string|null The stored path, or null on failure.
	 */
	public static function store(string $dir, string $id, string $source, string $ext, callable $move): ?string
	{
		if (!self::isValidId($id) || !SupportedFormats::isExtensionAllowed($ext) || !wp_mkdir_p($dir)) {
			return null;
		}

		self::harden($dir);

		$target = trailingslashit($dir) . self::diskName($id) . '.' . strtolower($ext);
		if (!@$move($source, $target)) {
			return null;
		}

		@chmod($target, 0640);
		@touch($target);

		return $target;
	}

	/**
	 * @param int|null $max_age When given, a file older than this many seconds counts as gone,
	 *                          so retention holds even if a scheduled sweep never ran.
	 * @return array{path: string, ext: string}|null
	 */
	public static function find(string $dir, string $id, ?int $max_age = null, ?int $now = null): ?array
	{
		if (!self::isValidId($id)) {
			return null;
		}

		$base = trailingslashit($dir) . self::diskName($id) . '.';
		foreach (SupportedFormats::getExtensions() as $ext) {
			if (is_file($base . $ext)) {
				if ($max_age !== null) {
					$mtime = @filemtime($base . $ext);
					if ($mtime === false || $mtime + $max_age <= ($now ?? time())) {
						return null;
					}
				}
				return array('path' => $base . $ext, 'ext' => $ext);
			}
		}

		return null;
	}

	public static function delete(string $dir, string $id): void
	{
		$found = self::find($dir, $id);
		if ($found) {
			@unlink($found['path']);
		}
	}

	/**
	 * Deletes stored files older than $max_age seconds.
	 *
	 * @return int Number of files deleted.
	 */
	public static function sweep(string $dir, int $max_age, int $now): int
	{
		$deleted = 0;
		foreach (self::storedFiles($dir) as $path) {
			$mtime = @filemtime($path);
			if ($mtime !== false && $mtime + $max_age <= $now && @unlink($path)) {
				$deleted++;
			}
		}
		return $deleted;
	}

	/**
	 * @return int|null When the oldest remaining file expires, or null when nothing is stored.
	 */
	public static function nextExpiry(string $dir, int $max_age): ?int
	{
		$oldest = null;
		foreach (self::storedFiles($dir) as $path) {
			$mtime = @filemtime($path);
			if ($mtime !== false && ($oldest === null || $mtime < $oldest)) {
				$oldest = $mtime;
			}
		}
		return $oldest === null ? null : $oldest + $max_age;
	}

	private static function storedFiles(string $dir): array
	{
		if (!is_dir($dir)) {
			return array();
		}

		$files = array();
		foreach ((array) scandir($dir) as $name) {
			if ($name === '.' || $name === '..' || in_array($name, DirectoryGuard::fileNames(), true)) {
				continue;
			}
			$path = trailingslashit($dir) . $name;
			if (is_file($path) && preg_match('/^[a-f0-9]{64}\.[a-z0-9]+$/', $name)) {
				$files[] = $path;
			}
		}
		return $files;
	}

	private static function diskName(string $id): string
	{
		return hash_hmac('sha256', $id, wp_salt('auth'));
	}

	private static function harden(string $dir): void
	{
		DirectoryGuard::protect($dir);
	}
}
