<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Services;

use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ilObjectFactory;
use ilParticipants;
use ilObjCourse;
use ilCSVWriter;
use ilUserInterfaceHookPlugin;
use ilObjUser;
use ilGroupMembershipMailNotification;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ilObject;

/**
 * Class UserImportService
 * @package ILIAS\Plugin\CrsGrpEnrollment\Services
 * @author  Timo Müller <timomueller@databay.de>
 */
class UserImportService
{
    /**
     * @var ilCSVWriter
     */
    protected $csv = null;

    /**
     * @var ilUserInterfaceHookPlugin
     */
    protected $pluginObject = null;

    public function __construct($pluginObject)
    {
        $this->pluginObject = $pluginObject;
    }

    public function convertCSVToArray($importFile)
    {
        $tmpFile = fopen($importFile, 'r');

        if (!$tmpFile || !is_resource($tmpFile)) {
            throw new FileNotReadableException('CSV not readable');
        }

        $i = 0;
        $dataArray = [];
        while (($row = fgetcsv($tmpFile, 0, ';')) !== false) {
            if ($i == 0) {
                if (substr($row[0], 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
                    $row[0] = substr($row[0], 3);
                }
            }
            $dataArray[] = $row[0];
            $i++;
        }

        if (is_resource($tmpFile)) {
            fclose($tmpFile);
        }

        return $dataArray;
    }

    public function importUserToCourse(ilObjCourse $courseObject, UserImport $userImport)
    {
        global $DIC;
        $this->csv = new ilCSVWriter();
        $this->csv->addColumn($this->pluginObject->txt('report_csv_field_name'));
        $this->csv->addColumn($this->pluginObject->txt('report_csv_field_error'));
        $this->csv->addRow();

        $userIds = $this->getUserIds($userImport);

        $refIds = ilObject::_getAllReferences($courseObject->getId());
        $refId = current($refIds);
        $participant = ilParticipants::getInstance($refId);

        $filteredUserIds = $DIC->access()->filterUserIdsByRbacOrPositionOfCurrentUser(
            'manage_members',
            'manage_members',
            $refId,
            $userIds
        );

        $filteredOutUserIds = array_diff($userIds, $filteredUserIds);
        foreach ($filteredOutUserIds as $filteredOutUserId) {
            /** @var ilObjUser $user */
            $user = ilObjectFactory::getInstanceByObjId($filteredOutUserId);
            if (!$user) {
                $this->csv->addColumn('[' . $user->getId() . '] ');
            } else {
                $this->csv->addColumn('[' . $user->getId() . '] ' . $user->getFirstname() . ' ' . $user->getLastname());
            }
            $this->csv->addColumn($this->pluginObject->txt('report_csv_filtered_out_user_err_msg'));
            $this->csv->addRow();
        }

        foreach ($filteredUserIds as $filteredUserId) {
            $tmp_obj = ilObjectFactory::getInstanceByObjId($filteredUserId, false);
            if (!$tmp_obj) {
                $this->csv->addColumn('[' . $filteredUserId . '] ');
                $this->csv->addColumn($this->pluginObject->txt('report_csv_user_not_found_err_msg'));
                $this->csv->addRow();
                continue;
            }
            if ($participant->isAssigned($filteredUserId)) {
                $this->csv->addColumn('[' . $tmp_obj->getId() . '] ' . $tmp_obj->getFirstname() . ' ' . $tmp_obj->getLastname());
                $this->csv->addColumn($this->pluginObject->txt('report_csv_user_already_assigned_err_msg'));
                $this->csv->addRow();
                continue;
            }

            $participant->add($filteredUserId, IL_CRS_MEMBER);
            $participant->sendNotification($participant->NOTIFY_ACCEPT_USER, $filteredUserId);

            $courseObject->checkLPStatusSync($filteredUserId);
        }
        return $this->csv;
    }

    public function importUserToGroup(\ilObjGroup $groupObject, UserImport $userImport)
    {
        $refIds = ilObject::_getAllReferences($groupObject->getId());
        $refId = current($refIds);

        $participant = ilParticipants::getInstance($refId);

        $this->csv = new ilCSVWriter();
        $this->csv->addColumn($this->pluginObject->txt('report_csv_field_name'));
        $this->csv->addColumn($this->pluginObject->txt('report_csv_field_error'));
        $this->csv->addRow();

        $userIds = $this->getUserIds($userImport);

        foreach ((array) $userIds as $new_member) {
            $tmp_obj = ilObjectFactory::getInstanceByObjId($new_member, false);
            if (!$tmp_obj) {
                $this->csv->addColumn('[' . $new_member . '] ');
                $this->csv->addColumn($this->pluginObject->txt('report_csv_user_not_found_err_msg'));
                $this->csv->addRow();
                continue;
            }

            if ($participant->isAssigned($new_member)) {
                $this->csv->addColumn('[' . $tmp_obj->getId() . '] ' . $tmp_obj->getFirstname() . ' ' . $tmp_obj->getLastname());
                $this->csv->addColumn($this->pluginObject->txt('report_csv_user_already_assigned_err_msg'));
                $this->csv->addRow();
                continue;
            }

            $participant->add($new_member, IL_GRP_MEMBER);
            include_once './Modules/Group/classes/class.ilGroupMembershipMailNotification.php';
            $participant->sendNotification(
                ilGroupMembershipMailNotification::TYPE_ADMISSION_MEMBER,
                $new_member
            );
        }

        return $this->csv;
    }

    /**
     * @param UserImport $userImport
     * @return array{int} $users
     */
    private function getUserIds(UserImport $userImport) : array
    {
        $users = [];
        foreach (json_decode($userImport->getData(), true) as $userData) {
            $findUserFlag = false;

            $userId = ilObjUser::getUserIdByLogin($userData);
            if ($userId > 0) {
                $findUserFlag = true;
                $users[] = $userId;
            }

            $userIds = ilObjUser::getUserIdsByEmail($userData);
            if (count($userIds) > 0) {
                $findUserFlag = true;
                foreach ($userIds as $userId) {
                    $users[] = $userId;
                }
            }


            if (!$findUserFlag) {
                $this->csv->addColumn('[' . $userData . '] ');
                $this->csv->addColumn($this->pluginObject->txt('report_csv_user_not_found_err_msg'));
                $this->csv->addRow();
            }
        }

        return $users;
    }
}
