<?php declare(strict_types=1);

/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\Plugin\CrsGrpEnrollment\Job\UserImportJob;
use ILIAS\Plugin\CrsGrpEnrollment\Lock\PidBasedLocker;

/**
 * Class ilCrsGrpEnrollmentPlugin
 * @author Timo Müller <timomueller@databay.de>
 */
class ilCrsGrpEnrollmentPlugin extends ilUserInterfaceHookPlugin
{
    /** @var string */
    const CTYPE = 'Services';
    /** @var string */
    const CNAME = 'UIComponent';
    /** @var string */
    const SLOT_ID = 'uihk';
    /** @var string */
    const PNAME = 'CrsGrpEnrollment';
    /** @var self */
    private static $instance = null;
    /** @var bool */
    protected static $initialized = false;
    /** @var \ILIAS\DI\Container */
    protected $dic;

    public function __construct()
    {
        global $DIC;

        parent::__construct();

        $this->dic = $DIC;
    }

    public function getPluginName() : string
    {
        return self::PNAME;
    }

    public function run() : ilCronJobResult
    {
        $job = new UserImportJob();
        return $job->run();
    }

    protected function init() : void
    {
        parent::init();
        $this->registerAutoloader();

        if (!self::$initialized) {
            self::$initialized = true;

            $GLOBALS['DIC']['plugin.crs_grp_enrol.cronjob.locker'] = function () {
                return new PidBasedLocker(
                    new ilSetting($this->getPluginName())
                );
            };
        }
    }

    protected function afterUninstall() : void
    {
        parent::afterUninstall();

        if ($this->dic->database()->tableExists('xcge_user_import')) {
            $this->dic->database()->dropTable('xcge_user_import');
        }
    }

    public function registerAutoloader() : void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

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
