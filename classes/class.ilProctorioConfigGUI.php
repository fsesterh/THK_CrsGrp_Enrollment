<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\Proctorio\Administration\Controller;
use ILIAS\Plugin\Proctorio\Administration\GeneralSettings\UI\Form;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Class ilProctorioConfigGUI
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilProctorioConfigGUI extends Controller\Base
{
    /**
     * @inheritDoc
     */
    protected function getDefaultCommand() : string
    {
        return 'showSettings';
    }

    /**
     *
     */
    public function showSettings() : void
    {
        $form = new Form($this->plugin_object, $this, $this->settings);
        $this->pageTemplate->setContent($form->getHTML());
    }

    /**
     *
     */
    public function saveSettings() : void
    {
        $form = new Form($this->plugin_object, $this, $this->settings);
        if ($form->saveObject()) {
            \ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
            $this->ctrl->redirect($this);
        }

        $this->pageTemplate->setContent($form->getHTML());
    }
}