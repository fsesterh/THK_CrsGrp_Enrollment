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

        if ($this->isPreviewContext()) {
            // TODO: Parse/Manipulate buttons
        } else {
            // TODO: Parse/Manipulate buttons
        }

        return ['mode' => \ilUIHookPluginGUI::REPLACE, 'html' => $html];
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