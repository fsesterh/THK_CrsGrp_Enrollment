<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Form;

use ILIAS\Plugin\Proctorio\Frontend\Controller\Base;
use ILIAS\Plugin\Proctorio\UI\Form\Bindable;

/**
 * Class TestSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Form
 * @author Michael Jansen <mjansen@databay.de>
 */
class TestSettings extends \ilPropertyFormGUI
{
    /** @var \ilProctorioPlugin */
    private $plugin;
    /** @var Base */
    private $controller;
    /** @var object */
    private $cmdObject;
    /** @var \ilObjTest */
    private $test;
    /** @var Bindable */
    private $generalSettings;
    /** @var bool */
    private $disabled = false;

    /**
     * Form constructor.
     * @param \ilProctorioPlugin $plugin
     * @param Base $controller
     * @param object $cmdObject
     * @param \ilObjTest $test
     * @param Bindable $generalSettings
     */
    public function __construct(
        \ilProctorioPlugin $plugin,
        Base $controller,
        $cmdObject,
        \ilObjTest $test,
        Bindable $generalSettings
    ) {
        $this->plugin = $plugin;
        $this->controller = $controller;
        $this->cmdObject = $cmdObject;
        $this->test = $test;
        $this->generalSettings = $generalSettings;
        $this->disabled = $this->test->participantDataExist();
        parent::__construct();

        $this->initForm();
    }

    /**
     *
     */
    protected function initForm() : void
    {
        $this->setTitle($this->plugin->txt('form_header_settings'));
        $this->setDescription($this->plugin->txt('exam_settings_info_test_started'));
        
        if (!$this->disabled) {
            $this->addCommandButton($this->controller->getControllerName() . '.saveSettings', $this->lng->txt('save'));
        }

        $activationStatus = new \ilCheckboxInputGUI(
            $this->plugin->txt('exam_setting_label_status'), 'status'
        );
        $activationStatus->setInfo($this->plugin->txt('exam_setting_label_status_info'));
        $activationStatus->setValue(1);
        $activationStatus->setDisabled($this->disabled);
        $this->addItem($activationStatus);
        
        $examSettingsHeader = new \ilFormSectionHeaderGUI();
        $examSettingsHeader->setTitle($this->plugin->txt('form_header_exam_settings'));
        $this->addItem($examSettingsHeader);

        $examSettings = new ExamSettingsInput(
            $this->plugin,
            '',
            'settings'
        );
        $examSettings->setDisabled($this->disabled);
        $this->addItem($examSettings);

        $this->controller->lng->toJSMap($examSettings->getClientLanguageMapping());
        $this->controller->pageTemplate->addOnLoadCode($examSettings->getOnloadCode());

        // TODO: Fill form
        $this->setValuesByArray([
            'status' => true,
            'settings' => [
                'recordvideo',
                'fullscreensevere',
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function checkInput()
    {
        $bool = parent::checkInput();
        if (!$bool) {
            return $bool;
        }

        if ($this->disabled) {
            \ilUtil::sendFailure('exam_settings_err_existing_records');
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function saveObject() : bool
    {
        if (!$this->fillObject()) {
            $this->setValuesByPost();
            return false;
        }

        try {
            // TODO: Save values
            return true;
        } catch (\ilException $e) {
            \ilUtil::sendFailure($this->plugin->txt($e->getMessage()));
            $this->setValuesByPost();
            return false;
        }
    }

    /**
     * @return bool
     */
    protected function fillObject() : bool
    {
        if (!$this->checkInput()) {
            return false;
        }

        $success = true;

        try {
            // TODO: Fill form
            /*$this->setValuesByArray(
                $this->generalSettings->toArray()
            )*/;
        } catch (\ilException $e) {
            \ilUtil::sendFailure($e->getMessage());
            $success = false;
        }

        return $success;
    }
}