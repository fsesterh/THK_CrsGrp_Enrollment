<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

/**
 * Class ExamSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamSettings extends RepositoryObject
{
    /**
     * @inheritdoc
     */
    public function getDefaultCommand() : string
    {
        return 'showForm';
    }

    /**
     * @inheritdoc
     */
    public function getObjectGuiClass() : string
    {
        return \ilObjTestGUI::class;
    }

    /**
     * @inheritdoc
     */
    protected function init() : void
    {
        parent::init();

        if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('write', '', $this->getRefId())) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $this->drawHeader();
    }


    /**
     * 
     */
    public function showForm() : string
    {
        return 'Hello World';
    }
}