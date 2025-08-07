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
use ilFileUtils;
use ILIAS\Cron\Schedule\CronJobScheduleType;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\AssociatedObjectNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\UserNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Lock\Locker;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ILIAS\Plugin\CrsGrpEnrollment\Services\UserImportService;
use ilLink;
use ilLogger;
use ilMail;
use ilObjCourse;
use ilObject;
use ilObjectFactory;
use ilObjectNotFoundException;
use ilObjGroup;
use ilObjUser;
use ilPluginAdmin;
use JsonException;
use ReflectionClass;

class UserImportJob extends ilCronJob
{
    private Container $dic;
    private ilPluginAdmin $pluginAdmin;
    private ilLogger $logger;

    private Locker $lock;
    private ilCrsGrpEnrollmentPlugin $plugin;

    public function __construct()
    {
        global $DIC;
        $this->dic = $DIC;
        $this->pluginAdmin = $this->dic['ilPluginAdmin'];
        $this->logger = $this->dic->logger()->root();
        $this->lock = $this->dic['plugin.' . ilCrsGrpEnrollmentPlugin::ID . '.cronjob.locker'];
        $this->plugin = ilCrsGrpEnrollmentPlugin::getInstance();
    }

    public function getTitle(): string
    {
        return $this->plugin->txt('job.title');
    }

    public function getDescription(): string
    {
        return $this->plugin->txt('job.description');
    }

    public function getId(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    public function hasAutoActivation(): bool
    {
        return false;
    }

    public function hasFlexibleSchedule(): bool
    {
        return true;
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS;
    }

    public function getAllScheduleTypes(): array
    {
        return [
            CronJobScheduleType::SCHEDULE_TYPE_IN_MINUTES,
            CronJobScheduleType::SCHEDULE_TYPE_IN_HOURS,
            CronJobScheduleType::SCHEDULE_TYPE_DAILY,
        ];
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;
    }

    public function run(): ilCronJobResult
    {
        $cron_result = new ilCronJobResult();

        if ($this->lock->acquireLock()) {
            $this->logger->info('Acquired lock.');
        } else {
            $message = \sprintf(
                'Terminated import script: %s',
                'Script is probably running, please remove the lock if you are sure no task is running.'
            );
            $this->logger->info($message);
            $cron_result->setStatus(ilCronJobResult::STATUS_NO_ACTION);
            $cron_result->setMessage($message);
            return $cron_result;
        }

        $user_import_repo = new UserImportRepository();
        $user_import_service = new UserImportService($this->plugin);

        $user_imports = $user_import_repo->readAll();

        if (\count($user_imports) === 0) {
            $cron_result->setStatus(ilCronJobResult::STATUS_OK);
            $cron_result->setMessage($this->plugin->txt('cronResult.noImports'));
            $this->lock->releaseLock();
            return $cron_result;
        }

        $num_failed_mail_deliveries = 0;

        /** @var array<int, int> $ref_id_by_object_id */
        $ref_id_by_object_id = [];

        foreach ($user_imports as $user_import) {
            $report = new \ILIAS\Plugin\CrsGrpEnrollment\Report\UserImportReport(
                new ilCSVWriter(),
                $this->plugin->txt('report_identification_element_txt'),
                $this->plugin->txt('report_field_error')
            );

            $user = null;
            $object = null;
            $object_type = 'unsupported';
            $object_name = 'Unsupported';

            try {
                $this->logger->info(
                    \sprintf(
                        'Start User Import with this users: %s',
                        json_encode($user_import->getData(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
                    )
                );

                if (!ilObjUser::_lookupLogin($user_import->getUser())) {
                    throw new UserNotFoundException('Executive User not found');
                }
                $user = new ilObjUser($user_import->getUser());

                $object = ilObjectFactory::getInstanceByObjId($user_import->getObjId(), false);
                if (($object instanceof ilObjCourse || $object instanceof ilObjGroup) === false) {
                    throw new AssociatedObjectNotFoundException('Associated object not found');
                }

                if ($object instanceof ilObjCourse) {
                    $object_type = 'crs';
                    $object_name = $this->dic->language()->txtlng('common', 'crs', $user->getLanguage());
                    $user_import_service->importUserToCourse($object, $user_import, $report);
                }

                if ($object instanceof ilObjGroup) {
                    $object_type = 'grp';
                    $object_name = $this->dic->language()->txtlng('common', 'grp', $user->getLanguage());
                    $user_import_service->importUserToGroup($object, $user_import, $report);
                }
            } catch (UserNotFoundException) {
                $report->addError(
                    (string) $user_import->getUser(),
                    \sprintf(
                        $this->plugin->txt('report_no_executive_user_found'),
                        $user_import->getUser()
                    )
                );
            } catch (AssociatedObjectNotFoundException|ilDatabaseException|ilObjectNotFoundException) {
                $report->addError(
                    (string) $user_import->getObjId(),
                    \sprintf(
                        $this->plugin->txt('report_no_associated_object_found'),
                        $user_import->getObjId()
                    )
                );
            } catch (JsonException) {
                $report->addError(
                    (string) $user_import->getObjId(),
                    \sprintf(
                        $this->plugin->txt('report_json_encoding_error'),
                        $user_import->getObjId()
                    )
                );
            }

            if (!$user) {
                $this->logger->error("Unable to deliver CSV result to executive used with id '{$user_import->getUser()}'. User does not exist");
                $user_import_repo->delete($user_import);
                continue;
            }

            $plugin_lng_module = 'ui_uihk_' . ilCrsGrpEnrollmentPlugin::ID;

            $attachments = [];
            if ($report->hasErrors()) {
                $tmp_filename = ilFileUtils::ilTempnam() . '.csv';
                file_put_contents($tmp_filename, $report->asText());

                $filename = ilFileUtils::getASCIIFilename(implode('_', [
                        $this->plugin->txt('report_export_name'),
                        $object !== null ? $object->getTitle() : '',
                        $object_type,
                        $user_import->getObjId(),
                        date('dmY_H_i'),
                    ])) . '.csv';

                $mail_file_service = new ilFileDataMail(ANONYMOUS_USER_ID);
                $mail_file_service->copyAttachmentFile($tmp_filename, $filename);
                $attachments[] = $filename;
            }

            if (!isset($ref_id_by_object_id[$user_import->getObjId()])) {
                $refId = current(ilObject::_getAllReferences($user_import->getObjId()));
                $ref_id_by_object_id[$user_import->getObjId()] = $refId;
            }

            $permanent_link = ilLink::_getStaticLink($ref_id_by_object_id[$user_import->getObjId()]);

            $mail = new ilMail(ANONYMOUS_USER_ID);
            $errors = $mail->enqueue(
                $user->getLogin(),
                '',
                '',
                \sprintf(
                    $this->dic->language()->txtlng(
                        $plugin_lng_module,
                        "{$plugin_lng_module}_mail.message.title",
                        $user->getLanguage()
                    ),
                    $object_name,
                    ilObject::_lookupTitle($user_import->getObjId())
                ),
                \sprintf(
                    $this->dic->language()->txtlng(
                        $plugin_lng_module,
                        "{$plugin_lng_module}_mail.message.text",
                        $user->getLanguage()
                    ),
                    $permanent_link
                ),
                $attachments
            );

            if (\count($errors) !== 0) {
                $this->logger->error(
                    \sprintf(
                        'Mail delivery of import results failed. ID of import: %s, ID of receiving user: %s',
                        $user_import->getId(),
                        $user->getId()
                    )
                );
                $num_failed_mail_deliveries++;
            }
            $user_import_repo->delete($user_import);
        }

        $cron_result->setStatus(ilCronJobResult::STATUS_OK);
        $cron_result->setMessage(
            \sprintf(
                $this->plugin->txt('cron_result'),
                \count($user_imports),
                $num_failed_mail_deliveries
            )
        );
        $this->lock->releaseLock();

        return $cron_result;
    }
}
