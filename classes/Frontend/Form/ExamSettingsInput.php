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
    private $validExamSettings = [
        "recording" => [
            "recordvideo" => ['type' => 'binary'],
            "recordaudio" => ['type' => 'binary'],
            "recordscreen" => ['type' => 'binary'],
            "recordwebtraffic" => ['type' => 'binary'],
            "recordroomstart" => [
                'type' => 'binary',
                'depends_on' => [
                    'recordvideo',
                    'verifyvideo',
                ]
            ],
        ],

        "lock_down" => [
            "fullscreenlenient" => [
                'type' => 'modes',
                'modes' => [
                    'fullscreenlenient' => [
                        'blocks' => [
                            'linksonly',
                        ],
                    ],
                    'fullscreenmoderate' => [
                        'blocks' => [
                            'linksonly',
                        ],
                    ],
                    'fullscreensevere' => [
                        'blocks' => [
                            'linksonly',
                        ],
                    ],
                ],
                'depends_on' => [
                    'onescreen',
                    'notabs',
                    'closetabs',
                ]
            ],
            "onescreen" => ['type' => 'binary'],
            "notabs" => [
                'type' => 'modes',
                'modes' => [
                    'notabs' => [],
                    'linksonly' => [
                        'blocks' => [
                            'fullscreenlenient',
                            'fullscreenmoderate',
                            'fullscreensevere',
                        ],
                    ],
                ],
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
            "verifyvideo" => [
                'type' => 'binary',
                'depends_on' => [
                    'recordvideo',
                ]
            ],
            "verifyaudio" => [
                'type' => 'binary',
                'depends_on' => [
                    'recordaudio',
                ],
            ],
            "verifydesktop" => [
                'type' => 'binary',
                'depends_on' => [
                    'recordscreen',
                ],
            ],
            // "verifyroom", // (not supported by API)
            "verifyidauto" => ['type' => 'binary'], // or verifyidlive (no image available, not supported by API)
            "verifysignature" => ['type' => 'binary'],
        ],

        "in_quiz" => [
            "calculatorbasic" => [
                'type' => 'modes',
                'modes' => [
                    'calculatorbasic' => [],
                    'calculatorsci' => [],
                ]
            ],
            "whiteboard" => ['type' => 'binary'],
        ],
    ];
    /** @var array[] */
    private $validExamSettingsKeysBySetting = [];
    /** @var string[] */
    private $dependenciesOfSetting = [];
    /** @var string[] */
    private $blocksBySetting = [];
    /** @var bool */
    private $wasValidationError = false;
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
        foreach ($this->validExamSettings as $section => $settings) {
            foreach ($settings as $setting => $definition) {
                if ('binary' === $definition['type']) {
                    if (isset($definition['depends_on']) && is_array($definition['depends_on'])) {
                        $this->dependenciesOfSetting[$setting] = $definition['depends_on'];
                    }
                    
                    if (isset($definition['blocks']) && is_array($definition['blocks'])) {
                        foreach ($definition['blocks'] as $blockingSetting) {
                            $this->blocksBySetting[$blockingSetting][] = $settings;
                        }
                    }

                    $this->validExamSettingsKeysBySetting[$setting] = [
                        '',
                        $setting
                    ];

                    $this->onLoadCodeConfiguration['images'][] = self::IMAGE_CDN_BASE_URL . $setting . '.svg';
                    $this->clientLanguageMapping['setting_' . $setting] = $this->plugin->txt('setting_' . $setting);
                    $this->clientLanguageMapping['setting_' . $setting . '_info'] = $this->plugin->txt('setting_' . $setting . '_info');
                } else {
                    $this->validExamSettingsKeysBySetting[$setting] = [''];
                    foreach ($definition['modes'] as $mode => $modeDefinition) {
                        if (isset($definition['depends_on']) && is_array($definition['depends_on'])) {
                            $this->dependenciesOfSetting[$mode] = $definition['depends_on'];
                        }

                        if (isset($modeDefinition['blocks']) && is_array($modeDefinition['blocks'])) {
                            foreach ($modeDefinition['blocks'] as $blockingSetting) {
                                $this->blocksBySetting[$blockingSetting][] = $mode;
                            }
                        }

                        $this->validExamSettingsKeysBySetting[$setting][] = $mode;

                        $this->onLoadCodeConfiguration['images'][] = self::IMAGE_CDN_BASE_URL . $mode . '.svg';
                        $this->clientLanguageMapping['setting_' . $mode] = $this->plugin->txt('setting_' . $mode);
                        $this->clientLanguageMapping['setting_' . $mode . '_info'] = $this->plugin->txt('setting_' . $mode . '_info');
                    }

                    $this->onLoadCodeConfiguration['modeValues'][$setting] = array_keys($definition['modes']);
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

        foreach ($_POST[$this->getPostVar()] as $value) {
            if (!is_string($value)) {
                $this->setAlert($this->plugin->txt('err_wrong_format_for_selection'));
                $this->wasValidationError = true;
                return false; 
            }
        }

        $_POST[$this->getPostVar()] = array_map(function($value) {
            return trim((string) \ilUtil::stripSlashesRecursive($value));
        }, $_POST[$this->getPostVar()]);

        if ($this->getRequired() && 0 === strlen(implode('', $_POST[$this->getPostVar()]))) {
            $this->setAlert($this->lng->txt('msg_input_is_required'));
            $this->wasValidationError = true;
            return false;
        }

        $validExamSettingKeys = array_keys($this->validExamSettingsKeysBySetting);
        $submittedExamSettingKeys = array_keys($_POST[$this->getPostVar()]);
        $invalidRequestedExamSettings = array_diff($submittedExamSettingKeys, $validExamSettingKeys);
        if (count($invalidRequestedExamSettings) > 0) {
            $this->setAlert(sprintf($this->plugin->txt('err_invalid_selection'), implode(', ', $invalidRequestedExamSettings)));
            $this->wasValidationError = true;
            return false;
        }
        
        $invalidSelections = [];
        
        foreach ($_POST[$this->getPostVar()] as $setting => $value) {
            if (!in_array($value, $this->validExamSettingsKeysBySetting[$setting])) {
                $invalidSelections[] = $setting;
            }
        }
        
        if (count($invalidSelections) > 0) {
            $this->setAlert(sprintf(
                $this->plugin->txt('err_invalid_selection'),
                implode(', ', $invalidRequestedExamSettings)
            ));
            $this->wasValidationError = true;
            return false;
        }
        
        $missingDependencies = [];
        $blockingSettings = [];
        foreach ($_POST[$this->getPostVar()] as $setting => $value) {
            $dependenciesOfSetting = $this->dependenciesOfSetting[$value] ?? [];
            $blockingSettingsOfSetting = $this->blocksBySetting[$value] ?? [];
            $missingDependenciesOfSetting = [];
            $givenBlockingSettingsBySetting = [];
            
            foreach ($dependenciesOfSetting as $dependentSetting) {
                if (!in_array($dependentSetting, $_POST[$this->getPostVar()])) {
                    $missingDependenciesOfSetting[] = $this->plugin->txt('setting_' . $dependentSetting);
                }
            }

            foreach ($blockingSettingsOfSetting as $blockingSetting) {
                if (in_array($blockingSetting, $_POST[$this->getPostVar()])) {
                    $givenBlockingSettingsBySetting[] = $this->plugin->txt('setting_' . $blockingSetting);
                }
            }

            if (count($missingDependenciesOfSetting) > 0) {
                $missingDependencies[] = sprintf(
                    $this->plugin->txt('err_dependency_not_fulfilled_' . (count($missingDependenciesOfSetting) === 1 ? 's' : 'p')),
                    $this->plugin->txt('setting_' . $value),
                    implode(', ', $missingDependenciesOfSetting)
                );
            }

            if (count($givenBlockingSettingsBySetting) > 0) {
                $blockingSettings[] = sprintf(
                    $this->plugin->txt('err_blocking_setting_' . (count($givenBlockingSettingsBySetting) === 1 ? 's' : 'p')),
                    $this->plugin->txt('setting_' . $value),
                    implode(', ', $givenBlockingSettingsBySetting)
                );
            }
        }

        if (count($missingDependencies) > 0) {
            $this->setAlert(implode(' / ', $missingDependencies));
            $this->wasValidationError = true;
            return false;
        }

        if (count($blockingSettings) > 0) {
            $this->setAlert(implode(' / ', $blockingSettings));
            $this->wasValidationError = true;
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
        $accordion->setBehaviour(
            $this->wasValidationError ? \ilAccordionGUI::FORCE_ALL_OPEN : \ilAccordionGUI::FIRST_OPEN
        );

        $sections = array_keys($this->validExamSettings);
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
        foreach ($this->validExamSettings[$section] as $setting => $definition) {
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
            $intersection = array_intersect(array_keys($definition['modes']), $this->getValue());
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
            foreach (array_keys($definition['modes']) as $mode) {
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