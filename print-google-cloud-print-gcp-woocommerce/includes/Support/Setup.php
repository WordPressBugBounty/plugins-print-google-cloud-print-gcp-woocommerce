<?php
namespace Zprint\Support;

defined( 'ABSPATH' ) || exit;

class Setup {
	public const KEY = 'bizswoop_support';

	public function __construct() {
		if ( defined( 'BIZSWOOP_SUPPORT' ) && BIZSWOOP_SUPPORT ) {
			return;
		}

		define( 'BIZSWOOP_SUPPORT', true );
		add_action( 'plugins_loaded', array( $this, 'init' ) );

		new Notice();
		new DevAssist();
	}

	public function init(): void {
		new Support();
	}

	public static function reset(): void {
		Notice::reset();
		Support::reset();
	}
}
