<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

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
    private static ?ilCrsGrpEnrollmentPlugin $instance = null;
    protected static bool $initialized = false;
    protected Container $dic;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;
        parent::__construct($db, $component_repository, $id);

        $this->dic = $DIC;
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
