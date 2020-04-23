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
    protected $pageTemplate;
    /** @var \ilObjUser */
    protected $user;
    /** @var \ilProctorioPlugin */
    protected $plugin_object;
    /** @var \ilRbacReview */
    protected $rbacReview;
    /** @var \ilObjectDataCache */
    protected $objectCache;

    /**
     * Base constructor.
     * @param \ilProctorioPlugin $plugin_object
     */
    public function __construct(\ilProctorioPlugin $plugin_object = null)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->pageTemplate = $DIC->ui()->mainTemplate();
        $this->user = $DIC->user();
        $this->plugin_object = $plugin_object;
        $this->rbacReview = $DIC->rbac()->review();
        $this->objectCache = $DIC['ilObjDataCache'];
    }

    /**
     * @param string $cmd
     */
    public function performCommand($cmd)
    {
        global $DIC;

        $this->settings = $DIC['plugin.proctorio.settings'];

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
