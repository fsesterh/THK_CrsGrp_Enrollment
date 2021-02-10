<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

use ilObjCourseGUI;
use ilObjGroupGUI;

/**
 * Class CourseGroupEnrollment
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller
 * @author Timo Müller <timomueller@databay.de>
 */
class CourseGroupEnrollment extends RepositoryObject
{
    /**
     * @inheritdoc
     */
    public function getDefaultCommand() : string
    {
        return 'showSettingsCmd';
    }

    /**
     * @inheritdoc
     */
    public function getObjectGuiClass() : string
    {
        if ($this->isObjectOfType('crs')) {
            return ilObjCourseGUI::class;
        }

        return ilObjGroupGUI::class;
    }

    /**
     * @inheritdoc
     */
    protected function init() : void
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        if (version_compare(ILIAS_VERSION_NUMERIC, '6.0', '>=')) {
            $this->pageTemplate->loadStandardTemplate();
        } else {
            $this->pageTemplate->getStandardTemplate();
        }

        parent::init();

        // TODO: Check if we are really in a course or group
        if(
            $ctrl->getCurrentClassPath() != "ilobjcoursegui" &&
            $ctrl->getCurrentClassPath() != "ilobjgroupgui"
        ) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }
        $this->drawHeader();

        if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('write', '', $this->getRefId())) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        // TODO: Add back link tab ilTabsGUI::setBackTarget ($DIC->tabs()->setBackTarget(...));

    }

    /**
     * @return string
     */
    public function showImportFormCmd() : string
    {
        return "Hello World";
    }
}
