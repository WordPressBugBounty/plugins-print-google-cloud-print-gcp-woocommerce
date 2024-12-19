<?php

namespace Zprint;

use Exception;
use WC_Order;
use Zprint\Model\Location;
use Zprint\Aspect\InstanceStorage;
use Zprint\Aspect\Page;
use Zprint\Aspect\Box;

class Printer
{
	public const POS_PRINT = 'pos';
	public const WEB_PRINT = 'web';
	public const ORDER_ONLY_PRINT = 'order_only';

	public function __construct()
	{
		if (Printer::isEnabledPrinting(Printer::WEB_PRINT)) {
			add_action('woocommerce_order_status_changed', [
				static::class,
				'processOrderStatusChange',
			]);
		}
	}

	/**
	 * @throws Exception
	 */
	public static function processOrderStatusChange(int $order_id): void
	{
		$order = new WC_Order($order_id);
		$valid_status = $order->has_status(Order::getValidOrderStatusForWebPrinting());

		Log::info(Log::PRINTING, ['check process print', $order->get_id(), $order->get_status()]);

		if (
			$valid_status &&
			$order->get_meta('_pos_by') !== 'pos' &&
			!$order->needs_payment() &&
			!$order->get_meta('_zprint_web_printer')
		) {
			Log::info(Log::PRINTING, ['process print', $order->get_id()]);
			$order->add_meta_data('_zprint_web_printer', true, true);
			$order->save();
			Printer::printOrder($order, [[LocationFilter::WEB_ORDER, true, 'bool']]);
		}
	}

	/**
	 * @param WC_Order|int $order
	 * @param Location[]|int[] $locations
	 *
	 * @throws Exception
	 */
	public static function reprintOrder($order, array $locations): void
	{
		$order_id = $order instanceof WC_Order ? $order->get_id() : $order;
		$order = wc_get_order($order);
		Log::info(Log::PRINTING, ["Order $order_id", 'reprint order']);

		$locations = array_map(function ($location) {
			return $location instanceof Model\Location ? $location->getID() : $location;
		}, $locations);

		Printer::printOrder($order, [[LocationFilter::LOCATION, $locations, 'int_array']]);
	}

	/**
	 * @param WC_Order|int $order
	 *
	 * @throws Exception
	 */
	public static function printOrder($order, array $arguments): void
	{
		do_action('Zprint\printOrder', $order, $arguments);
		static::rawPrintOrder($order, $arguments);
	}

	/**
	 * @param WC_Order|int $order
	 *
	 * @throws Exception
	 */
	public static function rawPrintOrder($order, array $arguments): array
	{
		$order_id = $order instanceof WC_Order ? $order->get_id() : $order;
		Log::info(Log::PRINTING, ["Order $order_id", 'raw print']);

		if (!$order instanceof WC_Order) {
			$order = new WC_Order($order);
		}

		$arguments = apply_filters('Zprint\printOrderArguments', $arguments, $order);

		$filter = array_map(function (array $argument): LocationFilter {
			$value = $argument[1];
			if ($argument[2] === 'bool') {
				$value = (bool) $value;
			} elseif ($argument[2] === 'int') {
				$value = (int) $value;
			} elseif ($argument[2] === 'int_array') {
				$value = array_map('intval', (array) $value);
			}
			return new LocationFilter($argument[0], $value);
		}, $arguments);

		$templates_data = static::getTemplates($filter, $order);

		return static::printTemplates($templates_data, $order);
	}

	/**
	 * @param LocationFilter|LocationFilter[] $filter
	 */
	public static function getTemplates($filter, WC_Order $order): array
	{
		$locations = Location::getAll();
		$all_locations = $locations;

		if ($filter instanceof LocationFilter) {
			$locations = $filter->filter($locations);
		} elseif (is_array($filter)) {
			$locations = array_reduce(
				$filter,
				function (array $locations, LocationFilter $filter): array {
					return $filter->filter($locations);
				},
				$locations
			);
		}

		$locations = apply_filters(
			'Zprint\filterLocations',
			$locations,
			$order,
			$all_locations,
			$filter
		);

		return array_map(function (Location $location): array {
			return $location->getData();
		}, $locations);
	}

	public static function printTemplates(array $templates_data, WC_Order $order): array
	{
		$result = array_map(function (array $template_data) use ($order): array {
			return static::printDocument('Order ' . $order->get_id(), $order, $template_data);
		}, $templates_data);

		$codes = ['status', 'error'];

		$result = array_map(function (string $code) use ($result) {
			$status = array_map(function (array $e) use ($code) {
				return $e[$code];
			}, $result);
			if (count(array_unique($status)) === 1) {
				return current($status);
			} else {
				return $status;
			}
		}, $codes);

		return array_combine($codes, $result);
	}

	public static function printDocument(string $description, WC_Order $order, array $template_data): array
	{
		$printers = $template_data['printers'];

		$multipart = [
			'description' => $description,
			'url' => add_query_arg(
				[
					'zprint_order' => $order->get_id(),
					'zprint_location' => $template_data['id'],
					'zprint_order_user' => $order->get_user_id(),
				],
				home_url()
			),
			'printOption' => Document::getTicket($template_data),
		];

		$printers = array_map(function ($printer) use ($multipart) {
			$printer = [
				'printerId' => $printer,
			];
			$multipart = array_merge($multipart, $printer);
			return Client::postRequest('jobs', $multipart);
		}, $printers);

		$printers_success = array_map(function (object $response): bool {
			if ($response->job) {
				Log::info(Log::PRINTING, [
					$response->job->description,
					'create with ' . $response->job->status,
					'Job ' . $response->job->id,
				]);
			}
			if ( isset( $response->errorCode ) ) {
				Log::warn(Log::PRINTING, [$response->errorCode, $response->message]);
			}
			return isset($response->job);
		}, $printers);

		$printers_success = array_filter($printers_success);
		if (count($printers_success) === count($printers)) {
			return [
				'status' => true,
				'error' => null,
			];
		} else {
			return [
				'status' => false,
				'error' => $printers,
			];
		}
	}

	public static function isEnabledPrinting(string $type): bool
	{
		if (!in_array($type, [static::WEB_PRINT, static::POS_PRINT, static::ORDER_ONLY_PRINT])) {
			return false;
		}
		$allowed_print = (array) InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
			$setting_page = Page::get('printer setting');
			return $setting_page->scope(function () {
				$tab = TabPage::get('general');
				$box = Box::get('automatic order printing');
				$input = Input::get('enable automatic printing');
				return $input->getValue($box, null, $tab);
			});
		});

		return in_array($type, $allowed_print);
	}

	public static function getPrinters(): array
	{
		if (!Client::hasAccess()) {
			return [];
		}
		try {
			$response = Client::getRequest('printers');
			$printer_list = $response->data ?? array();

			$values = array_map(function (object $printer): string {
				return $printer->Station->name .
					' - ' .
					$printer->name .
					' (' .
					$printer->status .
					')';
			}, $printer_list);
			$key = array_map(function (object $printer) {
				return $printer->id;
			}, $printer_list);

			return array_combine($key, $values);
		} catch (Exception $exception) {
			Log::error(Log::BASIC, [$exception->getCode(), $exception->getMessage()]);

			return [];
		}
	}
}
