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

use ILIAS\Cron\CronJob;
use ILIAS\Cron\Job\JobProvider;
use ILIAS\Cron\Job\JobResult;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Job\UserImportJob;
use ILIAS\Plugin\CrsGrpEnrollment\Lock\PidBasedLocker;
use ILIAS\Plugin\CrsGrpEnrollment\Setup\DBUpdateSteps;

class ilCrsGrpEnrollmentPlugin extends ilUserInterfaceHookPlugin implements JobProvider
{
    public const string ID = 'crs_grp_enrol';
    private static ilPlugin|ilCrsGrpEnrollmentPlugin|null $instance = null;
    protected static bool $initialized = false;
    protected Container $dic;

    public function __construct(ilDBInterface $db, ilComponentRepositoryWrite $component_repository, string $id)
    {
        global $DIC;
        parent::__construct($db, $component_repository, $id);

        $this->dic = $DIC;
    }

    public function run(): JobResult
    {
        return (new UserImportJob())->run();
    }

    protected function init(): void
    {
        parent::init();
        $this->registerAutoloader();

        if (!self::$initialized) {
            self::$initialized = true;

            $GLOBALS['DIC']['plugin.' . self::ID . '.cronjob.locker'] = function () {
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

        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC['component.factory'];
        self::$instance = $component_factory->getPlugin(self::ID);
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
    public function getCronJobInstance(string $job_id): CronJob
    {
        foreach ($this->getCronJobInstances() as $cron_job_instance) {
            if ($cron_job_instance->getId() === $job_id) {
                return $cron_job_instance;
            }
        }
        throw new Exception("No cron job found with the id '$job_id'.");
    }

    public function install(): void
    {
        (new DBUpdateSteps())->install($this->db);
        parent::install();
    }

    public function update(): bool
    {
        //DbUpdateSteps should be executed before the parent update so potential exceptions result in no il_plugin db changes
        (new DBUpdateSteps())->install($this->db);

        return parent::update();
    }

    protected function uninstallCustom(): void
    {
        (new DBUpdateSteps())->uninstall($this->db);
    }
}
