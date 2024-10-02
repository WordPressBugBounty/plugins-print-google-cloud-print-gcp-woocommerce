<?php

namespace Zprint\Model;

class ServiceBox
{
	private $header;
	private $description;
	private $iconClass;
	private $link;
	private $linkText;

	public function __construct($header, $description, $iconClass, $link, $linkText)
	{
		$this->header = $header;
		$this->description = $description;
		$this->iconClass = $iconClass;
		$this->link = $link;
		$this->linkText = $linkText;
	}

	public function getHeader()
	{
		return $this->header;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function getIconClass()
	{
		return $this->iconClass;
	}

	public function getLink()
	{
		return $this->link;
	}

	public function getLinkText()
	{
		return $this->linkText;
	}
}
