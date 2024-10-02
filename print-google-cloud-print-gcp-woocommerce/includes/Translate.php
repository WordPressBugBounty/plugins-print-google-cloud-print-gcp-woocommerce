<?php

namespace Zprint;

use Zprint\Model\Location;

class Translate {

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'lang' ) );
		add_filter( 'locale', array( $this, 'change_location_locale' ) );
	}

	public function lang() {
		load_plugin_textdomain('Print-Google-Cloud-Print-GCP-WooCommerce', false, dirname(plugin_basename(PLUGIN_ROOT_FILE)) . '/lang/');
	}

	public function change_location_locale( string $locale ): string {
		if (
			! empty( $_GET['zprint_order'] ) ||
			! empty( $_POST['test_print'] ) ||
			( ! empty( $_GET['action'] ) && 'zprint_reprint' === $_GET['action'] )
		) {
			$location_id = (int) ( $_GET['location'][0] ?? ( $_GET['zprint_location'] ?? 0 ) );

			if ( ! empty( $location_id ) ) {
				$location = new Location( $location_id );

				if ( isset( $location->language ) ) {
					switch ( $location->language ) {
						case 'global':
							return $locale;

						case 'user':
							$user_id = (int) ( $_GET['zprint_order_user'] ?? 0 );

							if ( 0 === $user_id ) {
								return $locale;
							}

							return get_user_locale( $user_id );

						case 'custom':
							return $location->language_locale;
					}
				}
			}
		}

		return $locale;
	}

	public static function get_available_languages(): array {
		$lang_names = array(
			'de_AT' => 'German (Austria)',
			'de_CH' => 'German (Switzerland)',
			'de_DE' => 'German (Germany)',
			'es_AR' => 'Spanish (Argentina)',
			'es_CL' => 'Spanish (Chile)',
			'es_CR' => 'Spanish (Costa Rica)',
			'es_CO' => 'Spanish (Colombia)',
			'es_DO' => 'Spanish (Dominican Republic)',
			'es_EC' => 'Spanish (Ecuador)',
			'es_ES' => 'Spanish (Spain)',
			'es_GT' => 'Spanish (Guatemala)',
			'es_HN' => 'Spanish (Honduras)',
			'es_MX' => 'Spanish (Mexico)',
			'es_PE' => 'Spanish (Peru)',
			'es_PR' => 'Spanish (Puerto Rico)',
			'es_UY' => 'Spanish (Uruguay)',
			'es_VE' => 'Spanish (Venezuela)',
			'fr_BE' => 'French (Belgium)',
			'fr_CA' => 'French (Canada)',
			'fr_FR' => 'French (France)',
			'el_GR' => 'Greek (Greece)',
			'he' => 'Hebrew',
			'hu_HU' => 'Hungarian',
			'it_IT' => 'Italian (Italy)',
			'nb' => 'Norwegian Bokmål',
			'nn' => 'Norwegian Nynorsk',
			'nl_NL' => 'Dutch (Netherlands)',
			'pt_BR' => 'Portuguese (Brazil)',
			'pt_PT' => 'Portuguese (Portugal)',
			'ru_RU' => 'Russian (Russia)',
			'sv_AX' => 'Swedish (Åland Islands)',
			'sv_FI' => 'Swedish (Finland)',
			'sv_SE' => 'Swedish (Sweden)',
			'ta' => 'Tamil',
			'tr' => 'Turkish',
			'uk' => 'Ukrainian',
			'zh_CN' => 'Simplied Chinese',
			'zh_TW' => 'Traditional Chinese',
		);

		return array_merge(
			array(
				array(
					'code' => 'en',
					'name' => 'English (United States)',
				)
			),
			array_map( function ( $locale ) use ( $lang_names ) {
				$code = str_replace( 'Print-Google-Cloud-Print-GCP-WooCommerce-', '', $locale );

				return array(
					'code' => $code,
					'name' => $lang_names[ $code ] ?? $code,
				);
			}, get_available_languages( plugin_dir_path( PLUGIN_ROOT_FILE ) . 'lang' ) )
		);
	}
}
