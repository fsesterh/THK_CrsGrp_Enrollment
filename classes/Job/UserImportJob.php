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


namespace ILIAS\Plugin\CrsGrpEnrollment\Job;

use ilCronJob;
use ilCronJobResult;
use ilCrsGrpEnrollmentPlugin;
use ilCSVWriter;
use ilDatabaseException;
use ilFileDataMail;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\AssociatedObjectNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\UserNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Lock\Locker;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ILIAS\Plugin\CrsGrpEnrollment\Services\UserImportService;
use ilLogger;
use ilMail;
use ilObjCourse;
use ilObjectFactory;
use ilObjectNotFoundException;
use ilObjGroup;
use ilObjUser;
use ilPluginAdmin;
use ilPluginException;
use ilUtil;
use ReflectionClass;

/**
 * Class UserImportJob
 * @package ILIAS\Plugin\CrsGrpEnrollment\Job
 * @author Marvin Beym <mbeym@databay.de>
 */
class UserImportJob extends ilCronJob
{
    /**
     * @var Container
     */
    private $dic;
    /**
     * @var ilPluginAdmin
     */
    private $pluginAdmin;
    /**
     * @var ilLogger
     */
    private $logger;

    /**
     * @var Locker
     */
    private $lock;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->pluginAdmin = $this->dic['ilPluginAdmin'];
        $this->logger = $this->dic->logger()->root();
        $this->lock = $this->dic['plugin.crs_grp_enrol.cronjob.locker'];
    }

    public function getId() : string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    public function hasAutoActivation() : bool
    {
        return false;
    }

    public function hasFlexibleSchedule() : bool
    {
        return true;
    }

    public function getDefaultScheduleType() : int
    {
        return self::SCHEDULE_TYPE_IN_HOURS;
    }

    /**
     * @return int[]
     */
    public function getAllScheduleTypes() : array
    {
        return [
            self::SCHEDULE_TYPE_IN_MINUTES,
            self::SCHEDULE_TYPE_IN_HOURS,
            self::SCHEDULE_TYPE_DAILY,
        ];
    }

    public function getDefaultScheduleValue() : int
    {
        return 1;
    }

    public function run() : ilCronJobResult
    {
        $plugin = null;
        $cronResult = new ilCronJobResult();


        try {
            if (
                $this->pluginAdmin->exists('Services', 'UIComponent', 'uihk', 'CrsGrpEnrollment') &&
                $this->pluginAdmin->isActive('Services', 'UIComponent', 'uihk', 'CrsGrpEnrollment')
            ) {
                /**
                 * @var ilCrsGrpEnrollmentPlugin $plugin
                 */
                $plugin = call_user_func(
                    [get_class($this->pluginAdmin), 'getPluginObject'],
                    'Services',
                    'UIComponent',
                    'uihk',
                    'CrsGrpEnrollment'
                );
            }
        } catch (ilPluginException $e) {
        }

        if (!$plugin) {
            $cronResult->setStatus(ilCronJobResult::STATUS_FAIL);
            $cronResult->setMessage('Fatal Error! Plugin not installed!');
            return $cronResult;
        }

        if ($this->lock->acquireLock()) {
            $this->logger->info('Acquired lock.');
        } else {
            $message = sprintf(
                'Terminated import script: %s',
                'Script is probably running, please remove the lock if you are sure no task is running.'
            );
            $this->logger->info($message);
            $cronResult->setStatus(ilCronJobResult::STATUS_NO_ACTION);
            $cronResult->setMessage($message);
            return $cronResult;
        }


        $userImportRepository = new UserImportRepository();
        $userImportService = new UserImportService($plugin);

        $userImports = $userImportRepository->readAll();

        if (count($userImports) === 0) {
            $cronResult->setStatus(ilCronJobResult::STATUS_OK);
            $cronResult->setMessage($plugin->txt("cronResult.noImports"));
            return $cronResult;
        }

        $failedMailDeliveries = 0;

        foreach ($userImports as $userImport) {
            $csvWriter = new ilCSVWriter();

            $user = null;
            $object = null;
            $objectType = "unsupported";
            $objectTypeName = "Unsupported";

            try {
                $this->logger->info(sprintf(
                    'Start User Import with this users: %s',
                    json_encode($userImport->getData(), JSON_PRETTY_PRINT)
                ));

                if (ilObjUser::_lookupLogin($userImport->getUser()) === false) {
                    throw new UserNotFoundException('Executive User not found');
                }
                $user = new ilObjUser($userImport->getUser());

                $object = ilObjectFactory::getInstanceByObjId($userImport->getObjId());
                if (($object instanceof ilObjCourse || $object instanceof ilObjGroup) === false) {
                    throw new AssociatedObjectNotFoundException('Associated object not found');
                }

                if ($object instanceof ilObjCourse) {
                    $objectType = "crs";
                    $objectTypeName = $this->dic->language()->txtlng("common", "crs", $user->getLanguage());
                    $csvWriter = $userImportService->importUserToCourse($object, $userImport);
                }

                if ($object instanceof ilObjGroup) {
                    $objectType = "grp";
                    $objectTypeName = $this->dic->language()->txtlng("common", "grp", $user->getLanguage());
                    $csvWriter = $userImportService->importUserToGroup($object, $userImport);
                }
            } catch (UserNotFoundException $e) {
                $csvWriter->addColumn(sprintf($plugin->txt('report_csv_no_executive_user_found'), $userImport->getUser()));
            } catch (AssociatedObjectNotFoundException | ilDatabaseException | ilObjectNotFoundException $e) {
                $csvWriter->addColumn(sprintf($plugin->txt('report_csv_no_associated_object_found'), $userImport->getObjId()));
            }

            if (!$user) {
                $this->logger->error("Unable to deliver csv result to executive used with id '{$user->getId()}'. User does not exist");
                $userImportRepository->delete($userImport);
                continue;
            }

            $pluginLngModule = "ui_uihk_crs_grp_enrol";

            $tempFile = ilUtil::ilTempnam() . '.csv';
            file_put_contents($tempFile, $csvWriter->getCSVString());

            $fileName = ilUtil::getASCIIFilename(implode('_', [
                $plugin->txt('report_csv_export_name'),
                $object !== null ? $object->getTitle() : "",
                $objectType,
                $userImport->getObjId(),
                date('dmY_H_i'),
            ])) . ".csv";

            $fileDataMail = new ilFileDataMail(ANONYMOUS_USER_ID);
            $fileDataMail->copyAttachmentFile($tempFile, $fileName);
            $mail = new ilMail(ANONYMOUS_USER_ID);
            $errors = $mail->enqueue(
                $user->getEmail(),
                "",
                "",
                sprintf(
                    $this->dic->language()->txtlng($pluginLngModule, "{$pluginLngModule}_mail.message.title", $user->getLanguage()),
                    $objectTypeName,
                    $userImport->getObjId()
                ),
                $this->dic->language()->txtlng($pluginLngModule, "{$pluginLngModule}_mail.message.text", $user->getLanguage()),
                [$fileName],
                false
            );


            if (count($errors) !== 0) {
                $this->logger->error(
                    sprintf(
                        "Mail delivery of import results failed. ID of import: %s, ID of receiving user: %s",
                        $userImport->getId(),
                        $user->getId()
                    )
                );
                $failedMailDeliveries++;
            }
            $userImportRepository->delete($userImport);
        }

        $cronResult->setStatus(ilCronJobResult::STATUS_OK);
        $cronResult->setMessage(
            sprintf(
                $plugin->txt("cronResult"),
                count($userImports),
                $failedMailDeliveries
            )
        );
        $this->lock->releaseLock();



        return $cronResult;
    }
}
