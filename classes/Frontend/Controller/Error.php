<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

/**
 * Class Error
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
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
        $this->pageTemplate->getStandardTemplate();
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
