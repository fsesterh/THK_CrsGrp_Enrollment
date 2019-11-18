<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Form;

use ILIAS\Plugin\Proctorio\Frontend\Controller\Base;
use ILIAS\Plugin\Proctorio\Service\Proctorio\Impl as ProctorioService;
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
    /** @var ProctorioService */
    protected $service;
    /** @var \ilObjTest */
    protected $test;
    /** @var bool */
    private $isReadOnly = false;

    /**
     * Form constructor.
     * @param \ilProctorioPlugin $plugin
     * @param Base $controller
     * @param object $cmdObject
     * @param ProctorioService $service
     * @param \ilObjTest $test
     */
    public function __construct(
        \ilProctorioPlugin $plugin,
        Base $controller,
        $cmdObject,
        ProctorioService $service,
        \ilObjTest $test
    ) {
        $this->plugin = $plugin;
        $this->controller = $controller;
        $this->cmdObject = $cmdObject;
        $this->service = $service;
        $this->test = $test;
        parent::__construct();

        $this->isReadOnly = !$this->service->isConfigurationChangeAllowed($this->test);

        $this->initForm();
    }

    /**
     * @inheritDoc
     */
    public function addCommandButton($a_cmd, $a_text, $a_id = "")
    {
        if (!$this->isReadOnly) {
            parent::addCommandButton($a_cmd, $a_text, $a_id);
        }
    }

    /**
     *
     */
    protected function initForm() : void
    {
        $this->setTitle($this->plugin->txt('form_header_settings'));
        $this->setDescription($this->plugin->txt('exam_settings_info_test_started'));
        
        $activationStatus = new \ilCheckboxInputGUI(
            $this->plugin->txt('exam_setting_label_status'), 'status'
        );
        $activationStatus->setInfo($this->plugin->txt('exam_setting_label_status_info'));
        $activationStatus->setValue(1);
        $activationStatus->setDisabled($this->isReadOnly);
        $this->addItem($activationStatus);
        
        $examSettingsHeader = new \ilFormSectionHeaderGUI();
        $examSettingsHeader->setTitle($this->plugin->txt('form_header_exam_settings'));
        $this->addItem($examSettingsHeader);

        $examSettings = new ExamSettingsInput(
            $this->plugin,
            '',
            'exam_settings'
        );
        $examSettings->setDisabled($this->isReadOnly);
        $this->addItem($examSettings);

        $this->controller->lng->toJSMap($examSettings->getClientLanguageMapping());
        $this->controller->pageTemplate->addOnLoadCode($examSettings->getOnloadCode());
    }
}