<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollement\Administration\GeneralSettings\Settings;
use ILIAS\Plugin\CrsGrpEnrollement\Webservice\Rest\Impl;
use ILIAS\Plugin\CrsGrpEnrollement\AccessControl\Acl\Impl as Acl;
use ILIAS\Plugin\CrsGrpEnrollement\AccessControl\Acl\Resource\GenericResource;
use ILIAS\Plugin\CrsGrpEnrollement\AccessControl\Acl\Role\GenericRole;
use ILIAS\Plugin\CrsGrpEnrollement\AccessControl\Acl\Role\Registry;
use ILIAS\Plugin\CrsGrpEnrollement\AccessControl\Handler\Cached;
use ILIAS\Plugin\CrsGrpEnrollement\AccessControl\Handler\RoleBased;

/**
 * Class ilCrsGrpEnrollementPlugin
 * @author Timo Müller <timomueller@databay.de>
 */
class ilCrsGrpEnrollementPlugin extends ilUserInterfaceHookPlugin
{
    /** @var string */
    const CTYPE = 'Services';
    /** @var string */
    const CNAME = 'UIComponent';
    /** @var string */
    const SLOT_ID = 'uihk';
    /** @var string */
    const PNAME = 'CrsGrpEnrollement';
    /** @var self */
    private static $instance = null;
    /** @var bool */
    protected static $initialized = false;

    /**
     * @inheritdoc
     */
    public function getPluginName()
    {
        return self::PNAME;
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        parent::init();
        $this->registerAutoloader();

        if (!self::$initialized) {
            self::$initialized = true;
        }
    }

    /**
     * @inheritDoc
     */
    protected function afterUninstall()
    {
        parent::afterUninstall();

        //ToDo Datenbanktabelle beim Deinstallieren löschen
    }

    /**
     * Registers the plugin autoloader
     */
    public function registerAutoloader() : void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (null === self::$instance) {
            return self::$instance = ilPluginAdmin::getPluginObject(
                self::CTYPE,
                self::CNAME,
                self::SLOT_ID,
                self::PNAME
            );
        }

        return self::$instance;
    }
}
