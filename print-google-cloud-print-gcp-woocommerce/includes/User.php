<?php

namespace Zprint;

use Zprint\Aspect\Box;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;

class User {

	public const PRINT_MANAGEMENT_CAP_KEY = 'zprint_management';

	private const SETTINGS_TAB_PAGE_KEYS = array(
		'general',
		'locations',
		'application',
		'setting',
		'addons',
		'support',
	);

	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ) );
	}

	public function init() {
		$this->add_management_cap();

		foreach ( $this->get_settings_tab_page_names() as $tab_page_name ) {
			add_filter( 'option_page_capability_' . $tab_page_name, array( $this, 'add_option_page_cap' ) );
		}
	}

	private function add_management_cap() {
		global $wp_roles;

		$management_roles = $this->get_management_roles();

		foreach ( $wp_roles->roles as $role_key => $role ) {
			$role = get_role( $role_key );

			if ( in_array( $role_key, $management_roles, true ) ) {
				$role->add_cap( self::PRINT_MANAGEMENT_CAP_KEY );

			} elseif( $role->has_cap( self::PRINT_MANAGEMENT_CAP_KEY ) ) {
				$role->remove_cap( self::PRINT_MANAGEMENT_CAP_KEY );
			}
		}
	}

	private function get_management_roles(): ?array {
		return InstanceStorage::getGlobalStorage()->asCurrentStorage( function () {
			$page = Page::get( 'printer setting' );

			return $page->scope( function () {
				$tab   = TabPage::get( 'setting' );
				$box   = Box::get( 'access user roles' );
				$input = Input::get( 'access roles' );

				return $input->getValue( $box, null, $tab );
			} );
		} );
	}

	private function get_settings_tab_page_names(): array {
		return InstanceStorage::getGlobalStorage()->asCurrentStorage( function () {
			$page = Page::get( 'printer setting' );

			return $page->scope( function () {

				return array_map( function ( $name ) {
					$tab = TabPage::get( $name );

					return TabPage::getName( $tab );
				}, self::SETTINGS_TAB_PAGE_KEYS );
			} );
		} );
	}

	public function add_option_page_cap(): string {
		return self::PRINT_MANAGEMENT_CAP_KEY;
	}
}
