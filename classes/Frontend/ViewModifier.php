<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend;

/**
 * Interface ViewModifier
 * @package ILIAS\Plugin\Proctorio\Frontend
 */
interface ViewModifier
{
	/**
	 * @param string $component
	 * @param string $part
	 * @param array  $parameters
	 * @return bool
	 */
	public function shouldModifyHtml(string $component, string $part, array $parameters) : bool;

	/**
	 * @param string $component
	 * @param string $part
	 * @param array  $parameters
	 * @return array A `\ilUIHookPluginGUI::getHtml()` compatible array
	 */
	public function modifyHtml(string $component, string $part, array $parameters) : array;
}