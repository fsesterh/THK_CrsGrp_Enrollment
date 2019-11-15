<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

use ILIAS\Plugin\Proctorio\Frontend\Form\TestSettings as TestSettingsForm;

/**
 * Class TestSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class TestSettings extends RepositoryObject
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

        $this->pageTemplate->addCss(
            $this->getCoreController()->getPluginObject()->getDirectory() . '/assets/css/styles.css'
        );
        $this->pageTemplate->addJavaScript(
            $this->getCoreController()->getPluginObject()->getDirectory() . '/assets/js/main.js'
        );

        $this->drawHeader();
    }

    /**
     * @return TestSettingsForm
     */
    private function buildForm() : TestSettingsForm
    {
        $form  = new TestSettingsForm(
            $this->getCoreController()->getPluginObject(),
            $this,
            $this->getCoreController(),
            $this->test,
            $this->globalProctorioSettings
        );

        $this->ctrl->setParameter($this->getCoreController(), 'ref_id', $this->getRefId());
        $form->setFormAction(
            $this->ctrl->getFormAction($this->getCoreController(), $this->getControllerName() . '.saveSettings')
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
    public function saveSettingsCmd() : string
    {
        $form = $this->buildForm();
        if ($form->saveObject()) {
            \ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
            $this->ctrl->redirect($this->getCoreController(), $this->getControllerName() . '.showSettings');
        }

        return $form->getHtml();
    }
}