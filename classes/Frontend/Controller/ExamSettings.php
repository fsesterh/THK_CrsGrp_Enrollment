<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

use ILIAS\Plugin\Proctorio\Frontend\Form\ExamSettings as ExamSettingsForm;

/**
 * Class ExamSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamSettings extends RepositoryObject
{
    /** @var \ilObjTest */
    protected $test;

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

        $this->test = \ilObjectFactory::getInstanceByRefId($this->getRefId());

        $this->drawHeader();
    }

    /**
     * @return ExamSettingsForm
     */
    private function buildForm() : ExamSettingsForm
    {
        $form  = new ExamSettingsForm(
            $this->getCoreController()->getPluginObject(),
            $this->getCoreController(),
            $this->globalProctorioSettings
        );

        return $form;
    }
    
    /**
     * @return string
     */
    public function showSettingsCmd() : string
    {
        $form = $this->buildForm();

        return $form->getHTML();
    }

    /**
     *
     */
    public function saveSettings() : string
    {
        $form = $this->buildForm();
        if ($form->saveObject()) {
            \ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
            $this->ctrl->redirect($this->getCoreController(), $this->getControllerName() . '.showSettings');
        }

        return $form->getHtml();
    }
}