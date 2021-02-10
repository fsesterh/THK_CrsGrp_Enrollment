<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollement\Frontend\ViewModifier;

/**
 * Class TestSettings
 * @package ILIAS\Plugin\CrsGrpEnrollement\Frontend\Controller
 * @author Timo Müller <timomueller@databay.de>
 */
class TestSettings extends Base
{
    /**
     * @inheritDoc
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function modifyHtml(string $component, string $part, array $parameters) : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyGUI(string $component, string $part, array $parameters) : bool
    {
        if ('tabs' !== $part) {
            return false;
        }

        if (!$this->isCommandClass(\ilObjCourseGUI::class)) {
            return false;
        }

        if (!$this->isObjectOfType('crs')) {
            return false;
        }

        if (!$this->coreAccessHandler->checkAccess('manage_members', '', $this->getRefId())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyGUI(string $component, string $part, array $parameters) : void
    {
        /** @var \ilTabsGUI $tabs */

        $this->ctrl->setParameterByClass(get_class($this->getCoreController()),'object_type');

        $tabs = $parameters['tabs'];

        $tabs->addTab(
            "course_group_import",
            $this->getCoreController()->getPluginObject()->getPrefix() . '_course_group_import',
            $this->ctrl->getLinkTargetByClass(
                [ilUIPluginRouterGUI::class, get_class($this->getCoreController())],
                'TestSettings.showSettingsCmd'
            )
        );
    }
}
