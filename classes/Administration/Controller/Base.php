<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Administration\Controller;

use ILIAS\Plugin\Proctorio\Administration\GeneralSettings\Settings;

/**
 * Class Base
 * @package ILIAS\Plugin\Proctorio\Administration\Controller
 * @author  Michael Jansen <mjansen@databay.de>
 */
abstract class Base extends \ilPluginConfigGUI
{
    /** @var Settings */
    protected $settings;
    /** @var \ilCtrl */
    protected $ctrl;
    /** @var \ilLanguage */
    protected $lng;
    /** @var \ilTemplate */
    protected $tpl;
    /** @var \ilObjUser */
    protected $user;
    /** @var \ilProctorioPlugin */
    protected $plugin_object;

    /**
     * Base constructor.
     * @param \ilProctorioPlugin $plugin_object
     */
    public function __construct(\ilProctorioPlugin $plugin_object = null)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->user = $DIC->user();
        $this->settings = $DIC['plugin.proctorio.settings'];

        $this->plugin_object = $plugin_object;
    }

    /**
     * @param string $cmd
     */
    public function performCommand($cmd)
    {
        switch (true) {
            case method_exists($this, $cmd):
                $this->{$cmd}();
                break;

            default:
                $this->{$this->getDefaultCommand()}();
                break;
        }
    }

    /**
     * @return string
     */
    abstract protected function getDefaultCommand() : string;
}