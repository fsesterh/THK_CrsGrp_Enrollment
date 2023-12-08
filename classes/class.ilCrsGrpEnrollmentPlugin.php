<?php

declare(strict_types=1);

/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Job\UserImportJob;
use ILIAS\Plugin\CrsGrpEnrollment\Lock\PidBasedLocker;

/**
 * Class ilCrsGrpEnrollmentPlugin
 *
 * @author Timo Müller <timomueller@databay.de>
 */
class ilCrsGrpEnrollmentPlugin extends ilUserInterfaceHookPlugin implements ilCronJobProvider
{
    /** @var string */
    public const CTYPE = 'Services';
    /** @var string */
    public const CNAME = 'UIComponent';
    /** @var string */
    public const SLOT_ID = 'uihk';
    /** @var string */
    public const PNAME = 'CrsGrpEnrollment';
    private static ?ilCrsGrpEnrollmentPlugin $instance = null;
    protected static bool $initialized = false;
    protected Container $dic;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;
        parent::__construct($db, $component_repository, $id);

        $this->dic = $DIC;
    }

    public function getPluginName(): string
    {
        return self::PNAME;
    }

    protected function init(): void
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

    protected function afterUninstall(): void
    {
        parent::afterUninstall();

        if ($this->dic->database()->tableExists('xcge_user_import')) {
            $this->dic->database()->dropTable('xcge_user_import');
        }
    }

    public function registerAutoloader(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    public static function getInstance(): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        global $DIC;

        /** @var ilComponentFactory $componentFactory */
        $componentFactory = $DIC['component.factory'];
        self::$instance = $componentFactory->getPlugin('crs_grp_enrol');
        return self::$instance;
    }

    public function getCronJobInstances(): array
    {
        return [
            new UserImportJob()
        ];
    }

    /**
     * @throws Exception
     */
    public function getCronJobInstance(string $jobId): ilCronJob
    {
        foreach ($this->getCronJobInstances() as $cronJobInstance) {
            if ($cronJobInstance->getId() === $jobId) {
                return $cronJobInstance;
            }
        }
        throw new Exception("No cron job found with the id '$jobId'.");
    }
}
