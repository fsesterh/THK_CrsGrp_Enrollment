<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Form;

use ILIAS\Plugin\Proctorio\Frontend\Controller\Base;
use ILIAS\Plugin\Proctorio\UI\Form\Bindable;

/**
 * Class ExamSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Form
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamSettings extends \ilPropertyFormGUI
{
    /** @var \ilProctorioPlugin */
    private $plugin;
    /** @var Base */
    private $controller;
    /** @var object */
    private $cmdObject;
    /** @var Bindable */
    private $generalSettings;
    /** @var array[] */
    private $validExamSettings = [
        "recording" => [
            "recordvideo" => ['type' => 'binary'],
            "recordaudio" => ['type' => 'binary'],
            "recordscreen"=> ['type' => 'binary'],
            "recordwebtraffic" => ['type' => 'binary'],
            "recordroomstart" => ['type' => 'binary'],
        ],

        "lock_down" => [
            "fullscreenlenient" => ['type' => 'modes', 'modes' => [
                'fullscreenlenient',
                'fullscreenmoderate',
                'fullscreensevere',
            ]],
            "onescreen" => ['type' => 'binary'],
            "notabs" => ['type' => 'modes', 'modes' => [
                'notabs',
                'linksonly',
            ]],
            "closetabs" => ['type' => 'binary'],
            "print" => ['type' => 'binary'],
            "clipboard" => ['type' => 'binary'],
            "downloads" => ['type' => 'binary'],
            "cache" => ['type' => 'binary'],
            "rightclick" => ['type' => 'binary'],
            //"noreentry", // (not supported by API, although documented)
        ],

        "verification" => [
            "verifyvideo" => ['type' => 'binary'],
            "verifyaudio" => ['type' => 'binary'],
            "verifydesktop" => ['type' => 'binary'],
            // "verifyroom", // (not supported by API)
            "verifyidauto" => ['type' => 'binary'], // or verifyidlive (no image available, not supported by API)
            "verifysignature" => ['type' => 'binary'],
        ],

        "in_quiz" => [
            "calculatorbasic" => ['type' => 'modes', 'modes' => [
                'calculatorbasic',
                'calculatorsci',
            ]],
            "whiteboard" => ['type' => 'binary'],
        ],
    ];

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

        $accordion = new \ilAccordionGUI();
        $accordion->setBehaviour(\ilAccordionGUI::FIRST_OPEN);
        
        $sections = array_keys($this->validExamSettings);
        $lngMap = [];
        $configuration = [
            'imgHttpBasePath' => 'https://cdn.proctorio.net/assets/exam-settings/',
        ];
        foreach ($sections as $section) {
            $accordion->addItem(
                $this->plugin->txt('acc_header_' . $section . '_options'),
                $this->renderSettings($section, $lngMap, $configuration)
            );
        }

        $examSettings = new \ilNonEditableValueGUI('', '', true);
        $examSettings->setValue($accordion->getHTML());
        $this->addItem($examSettings);

        $this->controller->lng->toJSMap($lngMap);
        $this->controller->tpl->addOnLoadCode(
            'il.proctorioSettings.init(' . json_encode($configuration) . ');'
        );

        $this->setValuesByArray([]); // TODO: Fill form
    }

    /**
     * @param string $section
     * @param array $lngMap
     * @param array $configuration
     * @return string
     * @throws \ilTemplateException
     */
    private function renderSettings(
        string $section,
        array &$lngMap,
        array &$configuration
    ) : string 
    {
        global $DIC;

        $deckCardRowTemplate = $this->plugin->getTemplate('tpl.settings_deck_row.html', true, true);
        $deckCardTemplate = $this->plugin->getTemplate('tpl.settings_deck_card.html', true, true);

        $deckCardRowTemplate->setVariable(
            'DESCRIPTION',
            $this->plugin->txt('acc_header_' . $section . '_options_info')
        );

        $size = 2;
        $smallSize = 6;

        $cardsPerRow = 12 / $size;
        $i = 1;
        foreach ($this->validExamSettings[$section] as $setting => $definition) {
            $deckCardTemplate->setCurrentBlock('card');

            $cardTemplate = $this->plugin->getTemplate('tpl.settings_card.html', true, true);

            $cardTemplate->touchBlock('role_' . $definition['type']);
            $cardTemplate->touchBlock($definition['type']);
            if (rand(0, 1)) {  // TODO: Only if active
                $cardTemplate->touchBlock('active');
            }

            $cardTemplate->setVariable('KEY', $setting);
            if ('binary' === $definition['type']) {
                $cardTemplate->setVariable('VALUE', $setting);
            } else {
                $cardTemplate->setVariable('VALUE', $setting); // TODO: Mode
            }
            
            $cardTemplate->setVariable('TITLE', $this->plugin->txt('setting_' . $setting));
            $cardTemplate->setVariable('IMAGE', $DIC->ui()->renderer()->render([
                $DIC->ui()->factory()->image()->standard(
                    'https://cdn.proctorio.net/assets/exam-settings/'. $setting . '.svg',
                    $this->plugin->txt('setting_' . $setting)
                )
            ]));

            if ('binary' === $definition['type']) {
                $lngMap['setting_' . $setting] = $this->plugin->txt('setting_' . $setting);
                $lngMap['setting_' . $setting . '_info'] = $this->plugin->txt('setting_' . $setting . '_info');
                if (true) { // TODO: Only if active
                    $cardTemplate->touchBlock('checkbox_checked');
                }
                
                $cardTemplate->setCurrentBlock('type_checkbox');
                $cardTemplate->setVariable('TYPE_CHECKBOX_KEY', $setting);
                $cardTemplate->parseCurrentBlock();
            } else {
                foreach ($definition['modes'] as $mode) {
                    $lngMap['setting_' . $mode] = $this->plugin->txt('setting_' . $mode);
                    $lngMap['setting_' . $mode . '_info'] = $this->plugin->txt('setting_' . $mode . '_info');
                    if (true) {  // TODO: Only if active
                        $cardTemplate->touchBlock('radio_checked');
                    }

                    $cardTemplate->setCurrentBlock('type_radio');
                    $cardTemplate->setVariable('TYPE_RADIO_KEY', $setting);
                    $cardTemplate->setVariable('TYPE_RADIO_VALUE', $mode);
                    $cardTemplate->parseCurrentBlock();
                }
            }

            $deckCardTemplate->setVariable('CARD', $cardTemplate->get());
            $deckCardTemplate->setVariable('SIZE', $size);
            $deckCardTemplate->setVariable('SMALL_SIZE', $smallSize);
            $deckCardTemplate->parseCurrentBlock();

            if (($i % $cardsPerRow) == 0) {
                $deckCardRowTemplate->setCurrentBlock('row');
                $deckCardRowTemplate->setVariable('KEY', $section);
                $deckCardRowTemplate->setVariable('CARDS', $deckCardTemplate->get());
                $deckCardRowTemplate->parseCurrentBlock();
                $deckCardTemplate = $this->plugin->getTemplate('tpl.settings_deck_card.html', true, true);
                $i = 0;
            }
            $i++;
        }
        $deckCardRowTemplate->setCurrentBlock('row');
        $deckCardRowTemplate->setVariable('KEY', $section);
        $deckCardRowTemplate->setVariable('CARDS', $deckCardTemplate->get());
        $deckCardRowTemplate->parseCurrentBlock();

        return $deckCardRowTemplate->get();
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