<?php

namespace Zprint\Support;

defined('ABSPATH') or die('No script kiddies please!');

/*
 * Blocks direct web access to a folder under uploads on the servers that
 * honour per-folder rules (Apache 2.2/2.4, IIS) and stops directory listings
 * elsewhere. Nginx ignores these files, so callers must also use file names
 * that cannot be guessed.
 */
class DirectoryGuard
{
	private const FILES = array(
		'.htaccess'  => "# Apache 2.4\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n# Apache 2.2\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n",
		'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n",
		'index.html' => '',
		/* For servers configured with "index index.php" and autoindex on. */
		'index.php'  => "<?php\n// Silence is golden.\n",
	);

	public static function fileNames(): array
	{
		return array_keys(self::FILES);
	}

	public static function protect(string $dir): void
	{
		foreach (self::FILES as $name => $contents) {
			$path = rtrim($dir, '/\\') . '/' . $name;
			if (!file_exists($path)) {
				@file_put_contents($path, $contents);
			}
		}
	}
}
