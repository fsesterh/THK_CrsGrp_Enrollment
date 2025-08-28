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

namespace ILIAS\Plugin\CrsGrpEnrollment\Services;

use Exception;
use ilDatabaseException;
use ilGroupMembershipMailNotification;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ilLogger;
use ilObjCourse;
use ilObject;
use ilObjectFactory;
use ilObjectNotFoundException;
use ilObjGroup;
use ilObjUser;
use ilParticipants;
use ilUserInterfaceHookPlugin;

class UserImportService
{
    private readonly ilLogger $logger;

    public function __construct(private readonly ilUserInterfaceHookPlugin $plugin_object)
    {
        global $DIC;
        $this->logger = $DIC->logger()->root();
    }

    /**
     * @throws FileNotReadableException
     * @return list<string>
     */
    public function convertCSVToArray(string $import_filename): array
    {
        $tmp_filename = fopen($import_filename, 'rb');
        if (!$tmp_filename || !\is_resource($tmp_filename)) {
            throw new FileNotReadableException('CSV not readable');
        }

        $i = 0;
        $data_array = [];
        while (($row = fgetcsv($tmp_filename, 0, ';', '"')) !== false) {
            $i++;

            if ($row === [] || !array_is_list($row)) {
                continue;
            }

            /** @var non-empty-list<string> $row */
            if ($i === 1 && str_starts_with($row[0], \chr(hexdec('EF')) . \chr(hexdec('BB')) . \chr(hexdec('BF')))) {
                $row[0] = substr($row[0], 3);
            }

            $data_array[] = trim($row[0]);
        }

        fclose($tmp_filename);

        return $data_array;
    }

    public function importUserToCourse(
        ilObjCourse $crs,
        UserImport $user_import,
        \ILIAS\Plugin\CrsGrpEnrollment\Report\UserImportReport $report
    ): void {
        global $DIC;

        $usr_ids = $this->getUserIds($user_import, $report);

        $ref_ids = ilObject::_getAllReferences($crs->getId());
        $ref_id = current($ref_ids);

        /** @var \ilCourseParticipants $participant */
        $participant = ilParticipants::getInstance($ref_id);

        $user_has_permission = $DIC->access()->checkAccessOfUser($user_import->getUser(), 'manage_members', '', $ref_id);

        foreach ($usr_ids as $usr_id) {
            try {
                $tmp_obj = ilObjectFactory::getInstanceByObjId($usr_id, false);
            } catch (ilDatabaseException|ilObjectNotFoundException) {
                $tmp_obj = null;
            }

            if (!$user_has_permission) {
                if ($tmp_obj instanceof ilObjUser) {
                    $report->addError(
                        '[' . $tmp_obj->getId() . '] ' . $tmp_obj->getFirstname() . ' ' . $tmp_obj->getLastname(),
                        $this->plugin_object->txt('report_filtered_out_user_err_msg')
                    );
                } else {
                    $report->addError(
                        '[' . $usr_id . '] ',
                        $this->plugin_object->txt('report_filtered_out_user_err_msg')
                    );
                }
                continue;
            }

            if (!($tmp_obj instanceof ilObjUser)) {
                $report->addError(
                    '[' . $usr_id . '] ',
                    $this->plugin_object->txt('report_user_not_found_err_msg')
                );
                continue;
            }

            if ($participant->isAssigned($usr_id)) {
                $report->addError(
                    '[' . $usr_id . '] ',
                    $this->plugin_object->txt('report_user_already_assigned_err_msg')
                );
                continue;
            }

            $participant->add($usr_id, ilParticipants::IL_CRS_MEMBER);
            $participant->sendNotification(ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER, $usr_id);

            $crs->checkLPStatusSync($usr_id);
        }
    }

    public function importUserToGroup(
        ilObjGroup $grp,
        UserImport $usr_import,
        \ILIAS\Plugin\CrsGrpEnrollment\Report\UserImportReport $report
    ): void {
        $usr_ids = $this->getUserIds($usr_import, $report);

        $ref_ids = ilObject::_getAllReferences($grp->getId());
        $ref_id = current($ref_ids);

        /** @var \ilGroupParticipants $participant */
        $participant = ilParticipants::getInstance($ref_id);

        foreach ($usr_ids as $usr_id) {
            $tmp_obj = ilObjectFactory::getInstanceByObjId($usr_id, false);
            if (!($tmp_obj instanceof ilObjUser)) {
                $report->addError(
                    '[' . $usr_id . '] ',
                    $this->plugin_object->txt('report_user_not_found_err_msg')
                );
                continue;
            }

            if ($participant->isAssigned($usr_id)) {
                $report->addError(
                    '[' . $tmp_obj->getId() . '] ' . $tmp_obj->getFirstname() . ' ' . $tmp_obj->getLastname(),
                    $this->plugin_object->txt('report_user_already_assigned_err_msg')
                );
                continue;
            }

            $participant->add($usr_id, ilParticipants::IL_GRP_MEMBER);
            $participant->sendNotification(
                ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER,
                $usr_id
            );
        }
    }

    /**
     * @return list<int>
     */
    private function getUserIds(
        UserImport $usr_import,
        \ILIAS\Plugin\CrsGrpEnrollment\Report\UserImportReport $report
    ): array {
        $user_import_repo = new UserImportRepository();
        $usr_ids = [];

        try {
            $data = json_decode($usr_import->getData(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $ex) {
            $this->logger->error("Unable to decode UserImport data. Ex.: {$ex->getMessage()}");
            return [];
        }

        foreach ($data as $user_identifier) {
            if (!$user_identifier) {
                continue;
            }

            $by_login = ilObjUser::getUserIdByLogin($user_identifier);
            if ($by_login > 0) {
                $usr_ids[] = $by_login;
                continue;
            }

            $by_email = ilObjUser::getUserIdsByEmail($user_identifier);
            if (count($by_email) === 1) {
                $usr_ids[] = $by_email[0];
                continue;
            }

            $by_matric = $user_import_repo->getUserIdsByMatriculation($user_identifier);
            if (count($by_matric) === 1) {
                $usr_ids[] = $by_matric[0];
                continue;
            }

            $report->addError(
                '[' . $user_identifier . '] ',
                $this->plugin_object->txt('report_user_not_found_err_msg')
            );
        }

        return $usr_ids;
    }
}
