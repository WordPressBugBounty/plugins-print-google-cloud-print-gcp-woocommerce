<?php
/**
 * Plugin Name: BizPrint Print Manager for WooCommerce
 * Plugin URI: http://www.getbizprint.com
 * Description: Easily Add Support for Printing WooCommerce Orders with BizPrint Cloud Print Service and Print to Anywhere in the World!
 * Version: 4.6.4
 * Text Domain: Print-Google-Cloud-Print-GCP-WooCommerce
 * Domain Path: /lang
 * WC requires at least: 2.4.0
 * WC tested up to: 9.6.1
 * Author: BizSwoop a CPF Concepts, LLC Brand
 * Author URI: http://www.bizswoop.com
 */

namespace Zprint;

use Exception;

const KEY              = 'zprint';
const ACTIVE           = true;
const PLUGIN_ROOT      = __DIR__;
const PLUGIN_ROOT_FILE = __FILE__;
const ROOT_FILE        = __FILE__;
const PLUGIN_VERSION   = '4.6.4';
const ASPECT_PREFIX    = 'zp';
defined('ABSPATH') or die('No script kiddies please!');
require_once __DIR__ . '/includes/functions.php';

$autoload = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoload)) {
	throw new Exception('Autoloader not exists');
}

require_once $autoload;

new Setup();
