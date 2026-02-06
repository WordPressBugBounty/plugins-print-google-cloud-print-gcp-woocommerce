<?php

namespace Zprint;

use Exception;
use Zprint\Model\Location;

class LocationFilter
{
	public $type = null;
	public $argument = null;
	const USER = 'user';
	const LOCATION = 'location';
	const WEB_ORDER = 'web_order';
	const POS_ORDER_ONLY = 'pos_order_only';

	private static $allowed_types = [self::WEB_ORDER, self::USER, self::LOCATION, self::POS_ORDER_ONLY];

	/**
	 * @param bool|int|array $argument
	 *
	 * @throws Exception
	 */
	public function __construct(string $type, $argument)
	{
		if (!in_array($type, self::$allowed_types)) {
			throw new Exception($type . ' is not correct ' . __CLASS__ . ' type');
		}

		$this->type = $type;
		$this->argument = $argument;
	}

	/**
	 * @param $locations Location[]
	 *
	 * @return Location[]
	 */
	public function filter(array $locations): array
	{
		return array_filter($locations, function (Location $location): bool {
			switch ($this->type) {
				case self::USER:
				{
					if ($location->autoIncludeAllUsers) {
						return true;
					}
					if (is_array($this->argument)) {
						return count(array_diff($this->argument, $location->users)) < count($this->argument);
					} else {
						return in_array($this->argument, $location->users);
					}
				}
				case
				self::WEB_ORDER:
				{
					return $location->enabledWEB === $this->argument;

				}
				case self::POS_ORDER_ONLY:
				{
					return $location->enabledPOS === $this->argument;

				}
				case self::LOCATION:
				{
					return in_array($location->getID(), $this->argument);
				}
				default:
					return true;
			}
		});
	}
}
