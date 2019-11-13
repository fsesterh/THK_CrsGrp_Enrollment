<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\ViewModifier;

/**
 * Class ExamLaunch
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamLaunch extends Base
{
    /**
     * @return bool
     */
    private function isPreviewContext() : bool
    {
        return (
            $this->isCommandClass('ilobjcoursegui') &&
            strtolower($this->ctrl->getCmd()) === strtolower('showItemIntro')
        );
    }

    /**
     * @return bool
     */
    private function isInfoScreenContext() : bool
    {
        $isBaseClassInfoScreenRequest = (
            $this->isBaseClass('ilObjTestGUI') &&
            in_array(
                strtolower($this->ctrl->getCmd()),
                [
                    strtolower('infoScreen'),
                    strtolower('showSummary'),
                ]
            )
        );

        $isCmdClassInfoScreenRequest = (
            $this->isCommandClass('ilInfoScreenGUI') &&
            in_array(
                strtolower($this->ctrl->getCmd()),
                [
                    strtolower('infoScreen'),
                ]
            )
        );

        $isGotoRequest = (
            preg_match('/^tst_\d+$/', (string) $this->httpRequest->getQueryParams()['target'] ?? '')
        );

        return $isBaseClassInfoScreenRequest || $isCmdClassInfoScreenRequest || $isGotoRequest;
    }

    /**
     * @return int
     */
    private function getTestRefId() : int
    {
        $refId = $this->getRefId();
        if ($refId <= 0) {
            $refId = $this->getPreviewRefId();
        }

        if ($refId <= 0) {
            $refId = $this->getTargetRefId();
        }
        
        return $refId;
    }
    
    /**
     * @inheritDoc
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters) : bool
    {
        if (!$this->isInfoScreenContext() && !$this->isPreviewContext()) {
            return false;
        }

        if ('template_get' !== $part) {
            return false;
        }
        
        if ($this->isPreviewContext()) {
            if ('Modules/Course/Intro/tpl.intro_layout.html' !== $parameters['tpl_id']) {
                return false;
            }

            if (!$this->isPreviewObjectOfType('tst')) {
                return false;
            }
        } else {
            if ('Services/UIComponent/Toolbar/tpl.toolbar.html' !== $parameters['tpl_id']) {
                return false;
            }

            if (!$this->isObjectOfType('tst') && !$this->isTargetObjectOfType('tst')) {
                return false;
            }
        }

        if (!$this->coreAccessHandler->checkAccess('read', '', $this->getTestRefId())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyHtml(string $component, string $part, array $parameters) : array
    {
        $html = $parameters['html'];

        $unmodified = ['mode' => \ilUIHookPluginGUI::KEEP, 'html' => ''];

        // TODO: Check if proctorio is enabled, return $unmodified if not

        $doc = new \DOMDocument("1.0", "utf-8");
        if (!@$doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $html . '</body></html>')) {
            return $unmodified;
        }
        $doc->encoding = 'UTF-8';

        $xpath = new \DomXPath($doc);
        $startPlayerCommandButton = $xpath->query("//input[contains(@name, 'startPlayer')]");
        $resumePlayerCommandButton = $xpath->query("//input[contains(@name, 'resumePlayer')]");

        // TODO: Maybe add Proctorio review button here

        if (1 === $startPlayerCommandButton->length xor 1 === $resumePlayerCommandButton->length) {
            if (1 === $startPlayerCommandButton->length) {
                return $this->manipulateLaunchElement($doc, $startPlayerCommandButton->item(0), $unmodified);
            } elseif (1 === $resumePlayerCommandButton->length) {
                return $this->manipulateLaunchElement($doc, $resumePlayerCommandButton->item(0), $unmodified);
            }
        }

        return $unmodified;
    }

    /**
     * @param \DOMDocument $doc
     * @param \DOMElement $elm
     * @param array $unmodified
     * @return array
     */
    private function manipulateLaunchElement(\DOMDocument $doc, \DOMElement $elm, array $unmodified) : array
    {
        $this->ctrl->setParameterByClass(
            get_class($this->getCoreController()),
            'ref_id',
            $this->getTestRefId()
        );
        $url = $this->ctrl->getLinkTargetByClass(
            ['ilUIPluginRouterGUI', get_class($this->getCoreController())],
            'ExamLaunch.launch',
            '',
            false,
            false
        );
        $elm->setAttribute('onclick', 'window.location.href = "' . $url . '"; return false;');

        $processedHtml = $doc->saveHTML($doc->getElementsByTagName('body')->item(0));
        if (strlen($processedHtml) === 0) {
            return $unmodified;
        }

        return ['mode' => \ilUIHookPluginGUI::REPLACE, 'html' => $processedHtml];
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyGUI(string $component, string $part, array $parameters) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function modifyGUI(string $component, string $part, array $parameters) : void
    {
    }
}