<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Form;

/**
 * Class ExamSettingsInput
 * @package ILIAS\Plugin\Proctorio\Frontend\Form
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamSettingsInput extends \ilSubEnabledFormPropertyGUI
{
    const IMAGE_CDN_BASE_URL = 'https://cdn.proctorio.net/assets/exam-settings/';

    /** @var \ilProctorioPlugin */
    private $plugin;
    /** @var array */
    private $clientLanguageMapping = [];
    /** @var array */
    private $onLoadCodeConfiguration = [
        'imgHttpBasePath' => self::IMAGE_CDN_BASE_URL,
        'modeValues' => [],
        'images' => [],
    ];
    /** @var array[] */
    private $validTestSettings = [
        "recording" => [
            "recordvideo" => ['type' => 'binary'],
            "recordaudio" => ['type' => 'binary'],
            "recordscreen" => ['type' => 'binary'],
            "recordwebtraffic" => ['type' => 'binary'],
            "recordroomstart" => ['type' => 'binary'],
        ],

        "lock_down" => [
            "fullscreenlenient" => [
                'type' => 'modes',
                'modes' => [
                    'fullscreenlenient',
                    'fullscreenmoderate',
                    'fullscreensevere',
                ]
            ],
            "onescreen" => ['type' => 'binary'],
            "notabs" => [
                'type' => 'modes',
                'modes' => [
                    'notabs',
                    'linksonly',
                ]
            ],
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
            "calculatorbasic" => [
                'type' => 'modes',
                'modes' => [
                    'calculatorbasic',
                    'calculatorsci',
                ]
            ],
            "whiteboard" => ['type' => 'binary'],
        ],
    ];
    /** @var array */
    private $value = [];

    /**
     * ExamSettingsInput constructor.
     * @param string $a_title
     * @param string $a_postvar
     */
    public function __construct(\ilProctorioPlugin $plugin, $a_title = '', $a_postvar = '')
    {
        parent::__construct($a_title, $a_postvar);
        $this->plugin = $plugin;
        $this->init();
    }

    /**
     * 
     */
    protected function init() : void
    {
        $this->onLoadCodeConfiguration['postVar'] = $this->getPostVar();
        foreach ($this->validTestSettings as $section => $settings) {
            foreach ($settings as $setting => $definition) {
                if ('binary' === $definition['type']) {
                    $this->onLoadCodeConfiguration['images'][] = self::IMAGE_CDN_BASE_URL . $setting . '.svg';
                    $this->clientLanguageMapping['setting_' . $setting] = $this->plugin->txt('setting_' . $setting);
                    $this->clientLanguageMapping['setting_' . $setting . '_info'] = $this->plugin->txt('setting_' . $setting . '_info');
                } else {
                    foreach ($definition['modes'] as $mode) {
                        $this->onLoadCodeConfiguration['images'][] = self::IMAGE_CDN_BASE_URL . $mode . '.svg';
                        $this->clientLanguageMapping['setting_' . $mode] = $this->plugin->txt('setting_' . $mode);
                        $this->clientLanguageMapping['setting_' . $mode . '_info'] = $this->plugin->txt('setting_' . $mode . '_info');
                    }

                    $this->onLoadCodeConfiguration['modeValues'][$setting] = $definition['modes'];
                }
            }
        }
    }

    /**
     * @param $values
     */
    public function setValueByArray($values) : void
    {
        $this->setValue($values[$this->getPostVar()] ?? []);
    }

    /**
     * @param $value
     */
    public function setValue($value) : void
    {
        if (is_array($value)) {
            $this->value = $value;
        }
    }

    /**
     * @return array
     */
    public function getValue() : array
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function checkInput()
    {
        if (!isset($_POST[$this->getPostVar()]) || !is_array($_POST[$this->getPostVar()])) {
            $_POST[$this->getPostVar()] = [];
        }

        foreach ($_POST[$this->getPostVar()] as $key => $value) {
            $_POST[$this->getPostVar()][$key] = trim((string) \ilUtil::stripSlashesRecursive($value));
        }

        if ($this->getRequired() && 0 === strlen(implode('', $_POST[$this->getPostVar()]))) {
            $this->setAlert($this->lng->txt('msg_input_is_required'));
            return false;
        }

        return $this->checkSubItemsInput();
    }

    /**
     * @return array
     */
    public function getClientLanguageMapping() : array
    {
        return $this->clientLanguageMapping;
    }

    /**
     * @return string
     */
    public function getOnloadCode() : string
    {
        $this->onLoadCodeConfiguration['disabled'] = (bool) $this->getDisabled();
        return  'il.proctorioSettings.init(' . json_encode($this->onLoadCodeConfiguration) . ');';
    }

    /**
     * @throws \ilTemplateException
     */
    private function render() : string
    {
        $accordion = new \ilAccordionGUI();
        $accordion->setBehaviour(\ilAccordionGUI::FIRST_OPEN);

        $sections = array_keys($this->validTestSettings);
        foreach ($sections as $section) {
            $accordion->addItem(
                $this->plugin->txt('acc_header_' . $section . '_options'),
                $this->renderSettings($section)
            );
        }

        return $accordion->getHTML();
    }

    /**
     * @param string $section
     * @return string
     * @throws \ilTemplateException
     */
    private function renderSettings(string $section) : string {
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
        foreach ($this->validTestSettings[$section] as $setting => $definition) {
            $deckCardTemplate->setCurrentBlock('card');

            $cardTemplate = $this->renderCard($setting, $definition);

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
     * @inheritDoc
     */
    public function insert(\ilTemplate $template)
    {
        $html = $this->render();

        $template->setCurrentBlock('prop_generic');
        $template->setVariable('PROP_GENERIC', $html);
        $template->parseCurrentBlock();
    }

    /**
     * @param string $setting
     * @param array $definition
     * @return \ilTemplate
     */
    private function renderCard(string $setting, array $definition) : \ilTemplate
    {
        global $DIC;

        $isActive = false;
        $activeSetting = '';

        $cardTemplate = $this->plugin->getTemplate('tpl.settings_card.html', true, true);

        $cardTemplate->touchBlock('role_' . $definition['type']);
        $cardTemplate->touchBlock($definition['type']);
        
        if ($this->getDisabled()) {
            $cardTemplate->touchBlock('disabled');
        }

        $cardTemplate->setVariable('KEY', $setting);
        if ('binary' === $definition['type']) {
            if (in_array($setting, $this->getValue())) {
                $isActive = true;
                $activeSetting = $setting;
                $cardTemplate->setVariable('VALUE', $setting);
                $cardTemplate->touchBlock('active');
            } else {
                $cardTemplate->setVariable('VALUE', '');
            }
        } else {
            $intersection = array_intersect($definition['modes'], $this->getValue());
            $isActive = (1 === count($intersection));

            if ($isActive) {
                $activeSetting = array_values($intersection)[0];
                $cardTemplate->setVariable('VALUE', $activeSetting);
                $cardTemplate->touchBlock('active');
            } else {
                $cardTemplate->setVariable('VALUE', '');
            }
        }
        
        $presentedSetting = $setting;
        if ($isActive) {
            $presentedSetting = $activeSetting;
        }
        $cardTemplate->setVariable('TITLE', $this->plugin->txt('setting_' . $presentedSetting));
        $cardTemplate->setVariable('IMAGE', $DIC->ui()->renderer()->render([
            $DIC->ui()->factory()->image()->standard(
                'https://cdn.proctorio.net/assets/exam-settings/' . $presentedSetting . '.svg',
                $this->plugin->txt('setting_' . $presentedSetting)
            )
        ]));

        if ('binary' === $definition['type']) {
            if (in_array($setting, $this->getValue())) {
                $cardTemplate->touchBlock('checkbox_checked');
            }

            $cardTemplate->setCurrentBlock('type_checkbox');
            $cardTemplate->setVariable('TYPE_CHECKBOX_NAME', $this->getPostVar());
            $cardTemplate->setVariable('TYPE_CHECKBOX_KEY', $setting);
            $cardTemplate->parseCurrentBlock();
        } else {
            foreach ($definition['modes'] as $mode) {
                if (in_array($mode, $this->getValue())) {
                    $cardTemplate->touchBlock('radio_checked');
                }

                $cardTemplate->setCurrentBlock('type_radio');
                $cardTemplate->setVariable('TYPE_RADIO_NAME', $this->getPostVar());
                $cardTemplate->setVariable('TYPE_RADIO_KEY', $setting);
                $cardTemplate->setVariable('TYPE_RADIO_VALUE', $mode);
                $cardTemplate->parseCurrentBlock();
            }
        }

        return $cardTemplate;
    }
}