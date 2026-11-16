<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\Page;
use Zprint\Aspect\InstanceStorage;
use Zprint\Support\DirectoryGuard;

class Log
{
	const BASIC = 'plugin';
	const PRINTING = 'print';

	private static function getPath($file, $isPath = true)
	{
		$dir = wp_upload_dir();
		$path = $isPath ? $dir['basedir'] : $dir['baseurl'];
		$path .= DIRECTORY_SEPARATOR . 'zprint';
		if ($file === null) {
			return $path;
		}
		return $path . DIRECTORY_SEPARATOR . $file;
	}

	/*
	 * Logs live under uploads, which the web server may serve directly. The
	 * folder is locked down where the server honours per-folder rules, and the
	 * file names carry a random per-site key so they cannot be guessed where it
	 * does not (Nginx). Managers read logs through Admin\LogAccess instead.
	 */
	const FILE_KEY_OPTION = 'zprint_log_file_key';
	const LOCK_FILE = '.zprint-log.lock';

	/** @var int Re-entrancy depth: a second flock() on a new handle would block this same request. */
	private static $lockDepth = 0;

	/*
	 * One lock for everything that touches log files: choosing the key,
	 * moving older files, and appending. Returns null if the lock cannot be
	 * taken, in which case the caller must not touch the files.
	 */
	private static function withStorageLock(callable $callback, ?float $max_wait = null)
	{
		if (static::$lockDepth > 0) {
			return $callback();
		}

		$dir = static::getPath(null);
		if (!is_dir($dir) && !wp_mkdir_p($dir)) {
			return null;
		}

		$handle = @fopen($dir . DIRECTORY_SEPARATOR . static::LOCK_FILE, 'c');
		if (!$handle) {
			return null;
		}
		if (!static::acquire($handle, $max_wait)) {
			fclose($handle);
			return null;
		}

		static::$lockDepth++;
		try {
			return $callback();
		} finally {
			static::$lockDepth--;
			flock($handle, LOCK_UN);
			fclose($handle);
		}
	}

	/* Blocking when $max_wait is null; otherwise gives up after $max_wait seconds. */
	private static function acquire($handle, ?float $max_wait): bool
	{
		if ($max_wait === null) {
			return flock($handle, LOCK_EX);
		}

		$deadline = microtime(true) + $max_wait;
		do {
			if (flock($handle, LOCK_EX | LOCK_NB)) {
				return true;
			}
			usleep(10000);
		} while (microtime(true) < $deadline);

		return false;
	}

	/**
	 * Gives logs left under the guessable names of older versions (plugin.log,
	 * print.log) a random name straight away. A rename is instant and copies
	 * nothing, so it is safe while printing; secureStorage() merges the renamed
	 * files into the current log later, on an admin page load.
	 */
	private static function quarantineLegacy(string $dir): void
	{
		foreach ([static::BASIC, static::PRINTING] as $type) {
			$legacy = $dir . DIRECTORY_SEPARATOR . $type . '.log';
			if (is_file($legacy)) {
				@rename($legacy, $dir . DIRECTORY_SEPARATOR . $type . '-' . bin2hex(random_bytes(12)) . '.log');
			}
		}
	}

	private static function isValidKey(string $key): bool
	{
		return (bool) preg_match('/^[a-z0-9]{24}$/', $key);
	}

	private static function fileKey(): string
	{
		$key = (string) get_option(static::FILE_KEY_OPTION, '');
		if (static::isValidKey($key)) {
			return $key;
		}

		/* First use (or the option was reset): pick the key once, under the lock, so concurrent requests agree. */
		$create = function (): string {
			wp_cache_delete(static::FILE_KEY_OPTION, 'options');
			wp_cache_delete('notoptions', 'options');
			$key = (string) get_option(static::FILE_KEY_OPTION, '');
			if (!static::isValidKey($key)) {
				$key = bin2hex(random_bytes(12));
				update_option(static::FILE_KEY_OPTION, $key, false);
			}
			return $key;
		};

		return (string) (static::withStorageLock($create) ?? $create());
	}

	private static function fileName(string $type): string
	{
		return $type . '-' . static::fileKey() . '.log';
	}

	public static function getPrintLogFilePath($isPath = true)
	{
		return static::getPath(static::fileName(static::PRINTING), $isPath);
	}

	public static function getBasicLogFilePath($isPath = true)
	{
		return static::getPath(static::fileName(static::BASIC), $isPath);
	}

	/**
	 * Creates and protects the log folder, and folds any other log file of the
	 * same type into the current one: the guessable plugin.log / print.log of
	 * older versions, or a file left behind if the key option was reset.
	 *
	 * Runs on admin page loads and the Support tab, never while printing: a
	 * legacy log can be large, so it is copied by streaming, outside the lock.
	 */
	public static function secureStorage(): void
	{
		$dir = static::getPath(null);
		if (!is_dir($dir) && !wp_mkdir_p($dir)) {
			return;
		}
		DirectoryGuard::protect($dir);

		foreach ([static::BASIC, static::PRINTING] as $type) {
			$current = static::getPath(static::fileName($type));
			foreach (glob($dir . DIRECTORY_SEPARATOR . $type . '*.log') ?: [] as $stray) {
				if ($stray === $current || !preg_match('/^' . $type . '(-[a-z0-9]+)?\.log$/', basename($stray))) {
					continue;
				}
				static::foldInto($stray, $current);
			}
		}
	}

	/**
	 * Empties a log (Support tab "Clear log file"): truncates the current file
	 * and deletes every older copy of that type (legacy, quarantined or under an
	 * old key), all under the storage lock, so no pending merge can bring the
	 * cleared lines back.
	 */
	public static function clear(string $type): void
	{
		if ($type !== static::BASIC && $type !== static::PRINTING) {
			return;
		}

		static::withStorageLock(function () use ($type) {
			$current = $type === static::BASIC ? static::getBasicLogFilePath() : static::getPrintLogFilePath();
			foreach (glob(static::getPath(null) . DIRECTORY_SEPARATOR . $type . '*.log') ?: [] as $file) {
				if ($file === $current || !preg_match('/^' . $type . '(-[a-z0-9]+)?\.log$/', basename($file))) {
					continue;
				}
				if (!@unlink($file)) {
					@file_put_contents($file, '');
				}
			}
			if (file_exists($current)) {
				file_put_contents($current, '');
			}
		});
	}

	/*
	 * Nothing writes to a stray file any more, so its (possibly large) copy
	 * into a temporary file happens without the lock. Under the lock: confirm
	 * the stray is unchanged and not already folded by another request, append
	 * the current log, swap the result in with an atomic rename, then remove
	 * the stray. Any failure leaves both logs as they were.
	 */
	private static function foldInto(string $stray, string $current): void
	{
		clearstatcache(true, $stray);
		$size = @filesize($stray);
		if ($size === false) {
			return;
		}

		$temp = $current . '.' . bin2hex(random_bytes(4)) . '.tmp';
		clearstatcache(true, $temp);
		if (!@copy($stray, $temp) || @filesize($temp) !== $size) {
			@unlink($temp);
			return;
		}

		$folded = static::withStorageLock(function () use ($stray, $current, $temp, $size) {
			clearstatcache();
			if (!is_file($stray) || @filesize($stray) !== $size) {
				return false;
			}

			if (file_exists($current) && !static::appendFile($current, $temp)) {
				return false;
			}

			if (!@rename($temp, $current)) {
				return false;
			}

			if (!@unlink($stray)) {
				/* Its lines are safe in the current log; at least stop it exposing them. */
				@file_put_contents($stray, '');
			}
			return true;
		});

		if (!$folded) {
			@unlink($temp);
		}
	}

	/* Streams $from onto the end of $to; true only if every byte was written. */
	private static function appendFile(string $from, string $to): bool
	{
		$in  = @fopen($from, 'rb');
		$out = $in ? @fopen($to, 'ab') : false;
		if (!$in || !$out) {
			if ($in) {
				fclose($in);
			}
			return false;
		}

		$expected = @filesize($from);
		$copied   = stream_copy_to_stream($in, $out);
		$flushed  = fflush($out);
		fclose($in);
		fclose($out);

		return $expected !== false && $copied === $expected && $flushed;
	}

	public static function isPrintLogsEnabled()
	{
		return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			return Page::get('printer setting')->scope(function ($setting_page) {
				$support = TabPage::get('support');
				return Box::get('print')->scope(function ($print) use ($support) {
					return in_array(
						'1',
						(array) Input::get('active')->getValue($print, null, $support)
					);
				});
			});
		});
	}

	public static function log($status, $type, $messageArgs)
	{
		if (!function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data = get_plugin_data(PLUGIN_ROOT_FILE);
		$version = $plugin_data['Version'];
		$baseArgs = [date('Y/m/d h:i:s'), $status, $version];

		switch ($type) {
			case static::BASIC:
				break;
			case static::PRINTING:
				if (!static::isPrintLogsEnabled()) {
					return;
				}
				break;
			default:
				return;
		}

		$data = array_merge($baseArgs, $messageArgs);
		$data = array_filter($data, function ($e) {
			return $e !== null;
		});
		$line = implode('|', $data) . PHP_EOL;

		/*
		 * Printing writes logs, so this must stay cheap: protect the folder,
		 * rename any guessable legacy log, append. Never merge here, and never
		 * wait long for the lock (a merge may hold it): after 250 ms the line is
		 * dropped rather than delaying the print.
		 */
		static::withStorageLock(function () use ($type, $line) {
			$dir = static::getPath(null);
			DirectoryGuard::protect($dir);
			static::quarantineLegacy($dir);
			$file = $type === static::BASIC ? static::getBasicLogFilePath() : static::getPrintLogFilePath();
			file_put_contents($file, $line, FILE_APPEND);
		}, 0.25);
	}

	public static function info($type, $messageArgs)
	{
		static::log('INFO', $type, $messageArgs);
	}

	public static function warn($type, $messageArgs)
	{
		static::log('WARNING', $type, $messageArgs);
	}

	public static function error($type, $messageArgs)
	{
		static::log('ERROR', $type, $messageArgs);
	}

	public static function debug($type, $messageArgs)
	{
		static::log('DEBUG', $type, $messageArgs);
	}
}
