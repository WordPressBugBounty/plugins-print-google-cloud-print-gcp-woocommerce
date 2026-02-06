<?php

namespace Zprint;

use Zprint\Aspect\Page;
use Zprint\Model\Location;
use Zprint\Template\Index;
use Zprint\Template\Options;

return function (Location $location, TabPage $tab, Page $page) {
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( esc_attr( $_POST['_wpnonce'] ), KEY . '_manage_location' ) ) {
		return;
	}

	$redirect_to = $page->getUrl($tab);
	if (isset($_POST['test_print'])) {
		$order = Order::getSampleOrder();
		Printer::reprintOrder($order, [$location->getID()]);
		$_SESSION[Page::getName($page, $tab)] = 'printed';
		$redirect_to = add_query_arg('id', $location->getID(), $redirect_to);
	} elseif (isset($_POST['delete'])) {
		if (count(Admin\Location::getBoxes())) {
			Admin\Location::processBoxes($location, true);
		}
		$location->delete();
		$_SESSION[Page::getName($page, $tab)] = 'deleted';
	} else {
		$location->title = esc_attr( $_POST['zpl_title'] );
		$location->enabledWEB = isset( $_POST['zpl_web_order'] ) && $_POST['zpl_web_order'];
		$location->enabledPOS = isset( $_POST['zpl_pos_order_only'] ) && $_POST['zpl_pos_order_only'];

		$location->autoIncludeAllUsers = isset( $_POST['zpl_auto_include_all_users'] ) && $_POST['zpl_auto_include_all_users'];

		if ($location->autoIncludeAllUsers) {
			$location->users = array();
		} else {
			$users = isset($_POST['zpl_users']) ? array_map( 'strval', $_POST['zpl_users'] ) : array();
			$location->users = array_filter($users, function ($user) {
				return get_user_by('id', $user);
			});
		}

		$printers = isset($_POST['zpl_printers']) ? array_map( 'strval', $_POST['zpl_printers'] ) : array();
		$all_printers = array_keys(Printer::getPrinters());
		$location->printers = array_filter($printers, function ($printer) use ($all_printers) {
			return in_array($printer, $all_printers);
		});

		$location->language = $_POST['zpl_language'] ? esc_attr( $_POST['zpl_language'] ) : '';
		$location->language_locale = $_POST['zpl_language_locale'] ? esc_attr( $_POST['zpl_language_locale'] ) : '';

		$template_slug = $_POST['zpl_template'] ? esc_attr( $_POST['zpl_template'] ) : '';
		if (Location::validateTemplate($template_slug)) {
			$location->template = $template_slug;

			$template = Templates::getTemplate($template_slug);

			if ($template instanceof Options && $template instanceof Index) {
				$location->setTemplateOption($template->processOptions($location->getTemplateOption()));
			}
		}

		$size = $_POST['zpl_size'] ? esc_attr( $_POST['zpl_size'] ) : '';
		if (Location::validateSize($size)) {
			$location->size = $size;
		}

		$format = $_POST['zpl_format'] ? esc_attr( $_POST['zpl_format'] ) : '';
		if (Location::validateFormat($format)) {
			$location->format = $format;
		}
		if ($format === 'plain') {
			$location->symbolsWidth = isset($_POST['zpl_symbolsLength']) ? intval( $_POST['zpl_symbolsLength'] ) : 0;
			$location->printSymbolsDebug = isset($_POST['zpl_printSymbolsDebug']);
		} else {
			$location->font = [
				'basicSize' => isset($_POST['zpl_fontSize']) ? intval( $_POST['zpl_fontSize'] ) : 0,
				'basicWeight' => isset($_POST['zpl_fontWeight']) ? intval( $_POST['zpl_fontWeight'] ) : 0,
				'headerSize' => isset($_POST['zpl_headerSize']) ? intval( $_POST['zpl_headerSize'] ) : 0,
				'headerWeight' => isset($_POST['zpl_headerWeight']) ? intval( $_POST['zpl_headerWeight'] ) : 0,
			];
		}

		$orientation = isset( $_POST['zpl_orientation'] ) ? esc_attr( $_POST['zpl_orientation'] ) : '';
		if (Location::validateOrientation($orientation)) {
			$location->orientation = $orientation;
		}

		if (isset($_POST['zpl_margins_custom'])) {
			$margins = array_slice($_POST['zpl_margins'], 0, 4);
			$margins = array_map('intval', $margins);
			$margins = array_replace(array_fill(0, 4, 0), $margins);
		} else {
			$margins = null;
		}
		$location->margins = $margins;

		$shipping = isset($_POST['zpl_shipping']) ? array_map('boolval', $_POST['zpl_shipping']) : array();

		$address_label_type = isset($_POST['zpl_shipping']['address_label_type']) ?
			sanitize_text_field(wp_unslash($_POST['zpl_shipping']['address_label_type'])) :
			'shipping';
		$return_address = [];
		if (isset($_POST['zpl_shipping']['return_address'])) {
			foreach ($_POST['zpl_shipping']['return_address'] as $key => $value) {
				$return_address[$key] = sanitize_text_field(wp_unslash($value));
			}
		}

		$location->shipping = [
			'cost' => $shipping['cost'] ?? false,
			'billing_shipping_details' => $shipping['billing_shipping_details'] ?? false,
			'customer_details' => $shipping['customer_details'] ?? false,
			'method' => $shipping['method'] ?? false,
			'delivery_pickup_type' => $shipping['delivery_pickup_type'] ?? false,
			'address_label_type' => $address_label_type,
			'return_address' => $return_address,
		];

		$total = $_POST['zpl_total'] ?? array();
		$total['cost'] = isset( $total['cost'] ) && $total['cost'];
		$location->total = apply_filters('Zprint\setting\locations\process\total', [
			'cost' => $total['cost'],
		], $total);

		if ($location->size === 'custom') {
			$location->width = isset($_POST['zpl_width']) ? intval( $_POST['zpl_width'] ) : 0;
			$location->height = isset($_POST['zpl_height']) ? intval( $_POST['zpl_height'] ) : 0;
		} else {
			$location->width = null;
			$location->height = null;
		}

		$logo = ( isset( $_POST['zpl_appearance_logo'] ) && $_POST['zpl_appearance_logo'] ) ? intval( $_POST['zpl_appearance_logo'] ) : null;
		$check_header = isset( $_POST['zpl_appearance_check_header'] ) ? esc_attr( $_POST['zpl_appearance_check_header'] ) : '';
		$company_name = isset( $_POST['zpl_appearance_company_name'] ) ? esc_attr( $_POST['zpl_appearance_company_name'] ) : '';
		$company_info = isset( $_POST['zpl_appearance_company_info'] ) ? esc_attr( $_POST['zpl_appearance_company_info'] ) : '';
		$details_header = isset( $_POST['zpl_appearance_order_details_header'] ) ? esc_attr( $_POST['zpl_appearance_order_details_header'] ) : '';
		$footer_info_1 = isset( $_POST['zpl_appearance_footer_information_1'] ) ? esc_attr( $_POST['zpl_appearance_footer_information_1'] ) : '';
		$footer_info_2 = isset( $_POST['zpl_appearance_footer_information_2'] ) ? esc_attr( $_POST['zpl_appearance_footer_information_2'] ) : '';


		$location->appearance = [
			'logo' => $logo,
			'Check Header' => $check_header,
			'Company Name' => $company_name,
			'Company Info' => $company_info,
			'Order Details Header' => $details_header,
			'Footer Information #1' => $footer_info_1,
			'Footer Information #2' => $footer_info_2,
		];

		if (!$location->getID()) {
			$location->save();
		}

		if (count(Admin\Location::getBoxes())) {
			Admin\Location::processBoxes($location);
		}

		do_action( 'zprint_admin_location_process', $location );

		$location->save();

		$_SESSION[Page::getName($page, $tab)] = 'saved';
		$redirect_to = add_query_arg('id', $location->getID(), $redirect_to);
	}

	header("Location: " . $redirect_to);
	exit;
};
