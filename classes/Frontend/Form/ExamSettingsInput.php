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
     * 
     */
    public function setValueByArray($value)
    {
        
    }

    /**
     *
     */
    public function setValue($value)
    {

    }

    /**
     * @inheritDoc
     */
    public function checkInput()
    {
        $isValid = parent::checkInput();
        if (!$isValid) {
            return false;
        }

        return true;
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
        return  'il.proctorioSettings.init(' . json_encode($this->onLoadCodeConfiguration) . ');';
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

        $cardTemplate = $this->plugin->getTemplate('tpl.settings_card.html', true, true);

        $cardTemplate->touchBlock('role_' . $definition['type']);
        $cardTemplate->touchBlock($definition['type']);
        if (false) {  // TODO: Only if active
            $cardTemplate->touchBlock('active');
        }

        $cardTemplate->setVariable('KEY', $setting);
        if ('binary' === $definition['type']) {
            $cardTemplate->setVariable('VALUE', $setting);
        } else {
            $cardTemplate->setVariable('VALUE', ''); // TODO: Mode
        }

        $cardTemplate->setVariable('TITLE', $this->plugin->txt('setting_' . $setting));
        $cardTemplate->setVariable('IMAGE', $DIC->ui()->renderer()->render([
            $DIC->ui()->factory()->image()->standard(
                'https://cdn.proctorio.net/assets/exam-settings/' . $setting . '.svg',
                $this->plugin->txt('setting_' . $setting)
            )
        ]));

        if ('binary' === $definition['type']) {
            if (false) { // TODO: Only if active
                $cardTemplate->touchBlock('checkbox_checked');
            }

            $cardTemplate->setCurrentBlock('type_checkbox');
            $cardTemplate->setVariable('TYPE_CHECKBOX_KEY', $setting);
            $cardTemplate->parseCurrentBlock();
        } else {
            foreach ($definition['modes'] as $mode) {
                if (false) {  // TODO: Only if active
                    $cardTemplate->touchBlock('radio_checked');
                }

                $cardTemplate->setCurrentBlock('type_radio');
                $cardTemplate->setVariable('TYPE_RADIO_KEY', $setting);
                $cardTemplate->setVariable('TYPE_RADIO_VALUE', $mode);
                $cardTemplate->parseCurrentBlock();
            }
        }

        return $cardTemplate;
    }
}