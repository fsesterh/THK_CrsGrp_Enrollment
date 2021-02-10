<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollement\Frontend\Controller;

/**
 * Class TestSettings
 * @package ILIAS\Plugin\CrsGrpEnrollement\Frontend\Controller
 * @author Timo Müller <timomueller@databay.de>
 */
class TestSettings extends RepositoryObject
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
        return \ilObjCourseGUI::class;
    }

    /**
     * @inheritdoc
     */
    protected function init() : void
    {
        $this->pageTemplate->getStandardTemplate();

        parent::init();

        //if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('write', '', $this->getRefId())) {
        //    $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        //}
    }

    /**
     * @return string
     */
    public function showSettingsCmd() : string
    {
        return "Hello World";
    }
}
