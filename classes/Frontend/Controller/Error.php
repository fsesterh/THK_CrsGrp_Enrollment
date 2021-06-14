<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

/**
 * Class Error
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller
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
        $this->pageTemplate->loadStandardTemplate();

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
