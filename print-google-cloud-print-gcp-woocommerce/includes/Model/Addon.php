<?php

namespace Zprint\Model;

class Addon extends ServiceBox
{
	private $plugin_name;
	private $hasActiveLink;

	public function __construct($header, $description, $plugin_name, $link, $linkText, $hasActiveLink)
	{
		parent::__construct($header, $description, '', $link, $linkText);

		$this->plugin_name = $plugin_name;
		$this->hasActiveLink = $hasActiveLink;
	}

	public function getPluginName()
	{
		return $this->plugin_name;
	}

	public function hasActiveLink()
	{
		return $this->hasActiveLink;
	}
}
