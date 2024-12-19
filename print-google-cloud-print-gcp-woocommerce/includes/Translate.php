<?php

namespace Zprint;

use Zprint\Exception\DB;
use Zprint\Model\Location;

class Translate {
	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'locale', array( $this, 'change_location_locale' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain('Print-Google-Cloud-Print-GCP-WooCommerce', false, dirname(plugin_basename(PLUGIN_ROOT_FILE)) . '/lang/');
	}

	/**
	 * @throws DB
	 */
	public function change_location_locale( string $locale ): string {
		if (
			empty( $_GET['zprint_order'] ) &&
			empty( $_POST['test_print'] ) &&
			( empty( $_GET['action'] ) || 'zprint_reprint' !== sanitize_text_field( wp_unslash( $_GET['action'] ) ) )
		) {
			return $locale;
		}

		$location_id = isset( $_GET['location'][0] ) ? intval( wp_unslash( $_GET['location'][0] ) ) : (
			isset( $_GET['zprint_location'] ) ? intval( wp_unslash( $_GET['zprint_location'] ) ) : 0
		);

		if ( 0 === $location_id ) {
			return $locale;
		}

		$location = new Location( $location_id );

		if ( empty( $location->language ) ) {
			return $locale;
		}

		switch ( $location->language ) {
			default:
			case 'global':
				return $locale;

			case 'user':
				$user_id = isset( $_GET['zprint_order_user'] ) ? intval( wp_unslash( $_GET['zprint_order_user'] ) ) : 0;

				if ( 0 === $user_id ) {
					return $locale;
				}

				return get_user_locale( $user_id );

			case 'custom':
				return $location->language_locale;
		}
	}

	public static function get_available_languages(): array {
		return array_merge(
			array(
				array(
					'code' => 'en',
					'name' => 'English (United States)',
				)
			),
			array_map( function ( string $locale ): array {
				$code = str_replace( 'Print-Google-Cloud-Print-GCP-WooCommerce-', '', $locale );

				return array(
					'code' => $code,
					'name' => static::get_lang_name( $code ),
				);
			}, get_available_languages( plugin_dir_path( PLUGIN_ROOT_FILE ) . 'lang' ) )
		);
	}

	protected static function get_lang_name( string $code ): string {
		switch ( $code ) {
			case  'de_AT':
				return __( 'German (Austria)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'de_CH':
				return __( 'German (Switzerland)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'de_DE':
				return __( 'German (Germany)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_AR':
				return __( 'Spanish (Argentina)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_CL':
				return __( 'Spanish (Chile)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_CR':
				return __( 'Spanish (Costa Rica)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_CO':
				return __( 'Spanish (Colombia)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_DO':
				return __( 'Spanish (Dominican Republic)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_EC':
				return __( 'Spanish (Ecuador)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_ES':
				return __( 'Spanish (Spain)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_GT':
				return __( 'Spanish (Guatemala)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_HN':
				return __( 'Spanish (Honduras)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_MX':
				return __( 'Spanish (Mexico)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_PE':
				return __( 'Spanish (Peru)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_PR':
				return __( 'Spanish (Puerto Rico)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_UY':
				return __( 'Spanish (Uruguay)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'es_VE':
				return __( 'Spanish (Venezuela)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'fr_BE':
				return __( 'French (Belgium)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'fr_CA':
				return __( 'French (Canada)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'fr_FR':
				return __( 'French (France)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'he':
				return __( 'Hebrew', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'hu_HU':
				return __( 'Hungarian', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'it_IT':
				return __( 'Italian (Italy)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'nb':
				return __( 'Norwegian Bokmål', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'nn':
				return __( 'Norwegian Nynorsk', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'nl_NL':
				return __( 'Dutch (Netherlands)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'pt_BR':
				return __( 'Portuguese (Brazil)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'pt_PT':
				return __( 'Portuguese (Portugal)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'ru_RU':
				return __( 'Russian (Russia)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'sv_AX':
				return __( 'Swedish (Åland Islands)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'sv_FI':
				return __( 'Swedish (Finland)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'sv_SE':
				return __( 'Swedish (Sweden)', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'tr':
				return __( 'Turkish', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			case  'uk':
				return __( 'Ukrainian', 'Print-Google-Cloud-Print-GCP-WooCommerce' );
			default:
				return $code;
		}
	}
}
