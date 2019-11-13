<?php declare(strict_types=1);
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
        /*if (!$this->test->isRandomTest() && !$this->test->isFixedTest()) {
            return $unmodified;
        }*/

        $doc = new \DOMDocument("1.0", "utf-8");
        if (!@$doc->loadHTML('<?xml encoding="utf-8" ?><html><body>' . $html . '</body></html>')) {
            return $unmodified;
        }
        $doc->encoding = 'UTF-8';

        $this->manipulateLaunchButton($doc);
        $this->addReviewButton($doc);

        $processedHtml = $doc->saveHTML($doc->getElementsByTagName('body')->item(0));
        if (0 === strlen($processedHtml)) {
            return $unmodified;
        }

        return ['mode' => \ilUIHookPluginGUI::REPLACE, 'html' => $processedHtml];
    }

    /**
     * @param \DOMDocument $doc
     */
    private function addReviewButton(\DOMDocument $doc) : void 
    {
        if (
            !$this->coreAccessHandler->checkAccess('write', '', $this->getRefId()) &&
            !$this->coreAccessHandler->checkAccess('tst_results', '', $this->getRefId()) &&
            !$this->coreAccessHandler->checkPositionAccess(\ilOrgUnitOperation::OP_MANAGE_PARTICIPANTS, $this->getRefId()) &&
            !$this->coreAccessHandler->checkPositionAccess(\ilOrgUnitOperation::OP_ACCESS_RESULTS, $this->getRefId())
        ) {
            return;
        }
        
        $xpath = new \DomXPath($doc);
        $toolbarButtons = $xpath->query("(//form[@id='ilToolbar'])[1]//input[last()]");

        if ($toolbarButtons->length > 0) {
            $referenceButton = $toolbarButtons->item(0);

            $this->ctrl->setParameterByClass(
                get_class($this->getCoreController()),
                'ref_id',
                $this->getTestRefId()
            );
            $url = $this->ctrl->getLinkTargetByClass(
                ['ilUIPluginRouterGUI', get_class($this->getCoreController())],
                'ExamLaunchAndReview.review',
                '',
                false,
                false
            );

            $btn = \ilLinkButton::getInstance();
            $btn->setCaption($this->getCoreController()->getPluginObject()->txt('btn_label_proctorio_review'), false);
            $btn->setUrl($url);
            $btn->setPrimary(true);
            
            $btn = $doc->createElement('a');
            $btn->setAttribute('class', 'btn btn-default btn-primary');
            $btn->setAttribute('style', 'margin-left: 5px;');
            $btn->setAttribute('href', $url);

            $btnText = $doc->createTextNode($this->getCoreController()->getPluginObject()->txt('btn_label_proctorio_review'));
            $btn->appendChild($btnText);

            $referenceButton->parentNode->insertBefore($btn, $referenceButton->nextSibling);
        }
    }

    /**
     * @param \DOMDocument $doc
     */
    private function manipulateLaunchButton(\DOMDocument $doc) : void
    {
        $xpath = new \DomXPath($doc);
        $startPlayerCommandButton = $xpath->query("//input[contains(@name, 'startPlayer')]");
        $resumePlayerCommandButton = $xpath->query("//input[contains(@name, 'resumePlayer')]");

        if (1 === $startPlayerCommandButton->length xor 1 === $resumePlayerCommandButton->length) {
            if (1 === $startPlayerCommandButton->length) {
                $this->manipulateLaunchElement($doc, $startPlayerCommandButton->item(0));
            } elseif (1 === $resumePlayerCommandButton->length) {
                $this->manipulateLaunchElement($doc, $resumePlayerCommandButton->item(0));
            }
        }
    }

    /**
     * @param \DOMDocument $doc
     * @param \DOMElement $elm
     */
    private function manipulateLaunchElement(\DOMDocument $doc, \DOMElement $elm) : void
    {
        $this->ctrl->setParameterByClass(
            get_class($this->getCoreController()),
            'ref_id',
            $this->getTestRefId()
        );
        $url = $this->ctrl->getLinkTargetByClass(
            ['ilUIPluginRouterGUI', get_class($this->getCoreController())],
            'ExamLaunchAndReview.launch',
            '',
            false,
            false
        );
        $elm->setAttribute('onclick', 'window.location.href = "' . $url . '"; return false;');
        $elm->removeAttribute('name');
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