<?php

namespace Zprint\Template;

use const Zprint\PLUGIN_ROOT;

abstract class Advanced extends Basic implements Index
{
	public function getPath($format): string
	{
		$templateName = [$this->getSlug(), $format];
		$templateName = array_filter($templateName);
		$templateName = implode('-', $templateName);

		return PLUGIN_ROOT . '/templates/advanced/' . $templateName . '.php';
	}
}
