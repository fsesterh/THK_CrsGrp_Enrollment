<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend;

/**
 * Interface ViewModifier
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend
 * @author Timo Müller <timomueller@databay.de>
 */
interface ViewModifier
{
    /**
     * @param string $component
     * @param string $part
     * @param array $parameters
     * @return bool
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters) : bool;

    /**
     * @param string $component
     * @param string $part
     * @param array $parameters
     * @return array A `\ilUIHookPluginGUI::getHtml()` compatible array
     */
    public function modifyHtml(string $component, string $part, array $parameters) : array;

    /**
     * @param string $component
     * @param string $part
     * @param array $parameters
     * @return bool
     */
    public function shouldModifyGUI(string $component, string $part, array $parameters) : bool;

    /**
     * @param string $component
     * @param string $part
     * @param array $parameters
     * @return array A `\ilUIHookPluginGUI::modifyGUI()` compatible array
     */
    public function modifyGUI(string $component, string $part, array $parameters) : void;
}
