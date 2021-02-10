<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollement\Frontend\Controller;

/**
 * Class Error
 * @package ILIAS\Plugin\CrsGrpEnrollement\Frontend\Controller
 * @author Timo Müller <timomueller@databay.de>
 */
class Error extends Base
{
    /**
     * @inheritdoc
     */
    public function getDefaultCommand() : string
    {
        return 'showCmd';
    }

    /**
     * @inheritDoc
     */
    public function init() : void
    {
        if (version_compare(ILIAS_VERSION_NUMERIC, '6.0', '>=')) {
            $this->pageTemplate->loadStandardTemplate();
        } else {
            $this->pageTemplate->getStandardTemplate();
        }

        parent::init();
    }

    /**
     * @return string
     */
    public function showCmd() : string
    {
        return $this->uiRenderer->render([
            $this->uiFactory->messageBox()->failure(
                $this->getCoreController()->getPluginObject()->txt('controller_not_found')
            )
        ]);
    }
}
