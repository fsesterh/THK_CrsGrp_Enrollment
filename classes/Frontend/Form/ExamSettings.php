<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Form;

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
    /** @var object */
    private $cmdObject;
    /** @var Bindable */
    private $generalSettings;

    /**
     * Form constructor.
     * @param \ilProctorioPlugin $plugin
     * @param object $cmdObject
     * @param Bindable $generalSettings
     */
    public function __construct(
        \ilProctorioPlugin $plugin,
        $cmdObject,
        Bindable $generalSettings
    ) {
        $this->plugin = $plugin;
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
        $accordion->addItem(
            $this->plugin->txt('acc_header_recording_options'),
            $this->renderSettings('recording')
        );
        $accordion->addItem(
            $this->plugin->txt('acc_header_lock_down_options'),
            $this->renderSettings('lock_down')
        );
        $accordion->addItem(
            $this->plugin->txt('acc_header_verification_options'),
            $this->renderSettings('verification')
        );
        $accordion->addItem(
            $this->plugin->txt('acc_header_in_quiz_options'),
            $this->renderSettings('in_quiz')
        );

        $examSettings = new \ilNonEditableValueGUI('', '', true);
        $examSettings->setValue($accordion->getHTML());
        $this->addItem($examSettings);

        $this->setValuesByArray([]);
    }

    /**
     * @param string $group
     * @return string
     */
    private function renderSettings(string $group) : string 
    {
        $settings = [
            "recording" => [
                "recordvideo",
                "recordaudio",
                "recordscreen",
                "recordwebtraffic",
                "recordroomstart",
            ],

            "lock_down" => [
                "fullscreenlenient", // or fullscreenmoderate or fullscreensevere
                "onescreen",
                "notabs", // or linksonly
                "closetabs",
                "print",
                "clipboard",
                "downloads",
                "cache",
                "rightclick",
                "noreentry", // or agentreentry
            ],
            
            "verification" => [
                "verifyvideo",
                "verifyaudio",
                "verifydesktop",
                "verifyidauto", // or verifyidlive
                "verifysignature",
            ],
            
            "in_quiz" => [
                "calculatorbasic", // or "calculatorsci
                "whiteboard",
            ],
        ];

        $deckCardRowTemplate = $this->plugin->getTemplate('tpl.settings_deck_row.html', true, true);
        $deckCardTemplate = $this->plugin->getTemplate('tpl.settings_deck_card.html', true, true);

        $deckCardRowTemplate->setVariable(
            'DESCRIPTION',
            $this->plugin->txt('acc_header_' . $group . '_options_info')
        );

        $size = 2;
        $smallSize = 6;
        
        global $DIC;

        $cardsPerRow = 12 / $size;
        $i = 1;
        foreach ($settings[$group] as $setting) {
            $deckCardTemplate->setCurrentBlock('card');

            $cardTemplate = $this->plugin->getTemplate('tpl.settings_card.html', true, true);
            $cardTemplate->setVariable('TITLE', $this->plugin->txt('setting_' . $setting));
            $cardTemplate->setVariable('IMAGE', $DIC->ui()->renderer()->render([
                $DIC->ui()->factory()->image()->standard(
                    'https://cdn.proctorio.net/assets/exam-settings/'. $setting . '.svg',
                    ''
                )
            ]));
            
            $deckCardTemplate->setVariable('CARD', $cardTemplate->get());
            $deckCardTemplate->setVariable('SIZE', $size);
            $deckCardTemplate->setVariable('SMALL_SIZE', $smallSize);
            $deckCardTemplate->parseCurrentBlock();

            if (($i % $cardsPerRow) == 0) {
                $deckCardRowTemplate->setCurrentBlock('row');
                $deckCardRowTemplate->setVariable('CARDS', $deckCardTemplate->get());
                $deckCardRowTemplate->parseCurrentBlock();
                $deckCardTemplate = $this->plugin->getTemplate('tpl.settings_deck_card.html', true, true);
                $i=0;
            }
            $i++;
        }
        $deckCardRowTemplate->setCurrentBlock('row');
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