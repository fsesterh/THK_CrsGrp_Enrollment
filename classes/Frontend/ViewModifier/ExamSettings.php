<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\ViewModifier;

/**
 * Class ExamSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamSettings extends Base
{
    /**
     * @inheritDoc
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters) : bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyHtml(string $component, string $part, array $parameters) : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyGUI(string $component, string $part, array $parameters) : bool
    {
        if ('sub_tabs' !== $part) {
            return false;
        }

        if (
            !$this->isCommandClass('ilobjtestsettingsgeneralgui') &&
            !$this->isCommandClass('ilmarkschemagui') &&
            !$this->isCommandClass('ilobjtestsettingsscoringresultsgui') &&
            !(
                $this->isCommandClass('ilobjtestgui') &&
                in_array($this->ctrl->getCmd(), ['defaults', 'addDefaults', 'deleteDefaults', 'applyDefaults'])
            ) &&
            !(
                $this->isCommandClass(get_class($this->getCoreController())) &&
                strpos($this->ctrl->getCmd(), $this->getClassName()) !== false
            )
        ) {
            return false;
        }

        if (!$this->isObjectOfType('tst')) {
            return false;
        }

        if (!$this->coreAccessHandler->checkAccess('write', '', $this->getRefId())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyGUI(string $component, string $part, array $parameters) : void
    {
        /** @var \ilTabsGUI $tabs */
        $tabs = $parameters['tabs'];

        $this->ctrl->setParameterByClass(get_class($this->getCoreController()), 'ref_id', $this->getRefId());
        $tabs->addSubTabTarget(
            $this->getCoreController()->getPluginObject()->getPrefix() . '_exam_tab_proctorio',
            $this->ctrl->getLinkTargetByClass(
                ['ilUIPluginRouterGUI', get_class($this->getCoreController())], 
                'ExamSettings.showForm'
            )
        );
    }
}