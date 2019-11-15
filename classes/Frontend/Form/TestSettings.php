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
    /** @var Bindable */
    private $generalSettings;

    /**
     * Form constructor.
     * @param \ilProctorioPlugin $plugin
     * @param Base $controller
     * @param object $cmdObject
     * @param Bindable $generalSettings
     */
    public function __construct(
        \ilProctorioPlugin $plugin,
        Base $controller,
        $cmdObject,
        Bindable $generalSettings
    ) {
        $this->plugin = $plugin;
        $this->controller = $controller;
        $this->cmdObject = $cmdObject;
        $this->generalSettings = $generalSettings;
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

        // TODO: Check if there is any existing participant data. If yes, disabled all elements

        $activationStatus = new \ilCheckboxInputGUI(
            $this->plugin->txt('exam_setting_label_status'), 'status'
        );
        $activationStatus->setInfo($this->plugin->txt('exam_setting_label_status_info'));
        $activationStatus->setValue(1);
        $this->addItem($activationStatus);
        
        $examSettingsHeader = new \ilFormSectionHeaderGUI();
        $examSettingsHeader->setTitle($this->plugin->txt('form_header_exam_settings'));
        $this->addItem($examSettingsHeader);

        $examSettings = new ExamSettingsInput(
            $this->plugin,
            '',
            'settings'
        );
        $this->addItem($examSettings);

        $this->controller->lng->toJSMap($examSettings->getClientLanguageMapping());
        $this->controller->pageTemplate->addOnLoadCode($examSettings->getOnloadCode());

        $this->setValuesByArray([]); // TODO: Fill form
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

        // TODO: Check if there is any existing participant data. If yes, respond with an error message and don't save
        //exam_settings_err_existing_records

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