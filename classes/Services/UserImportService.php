<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Services;

use Exception;
use ilCSVWriter;
use ilGroupMembershipMailNotification;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ilLogger;
use ilObjCourse;
use ilObject;
use ilObjectFactory;
use ilObjGroup;
use ilObjUser;
use ilParticipants;
use ilUserInterfaceHookPlugin;

/**
 * Class UserImportService
 * @package ILIAS\Plugin\CrsGrpEnrollment\Services
 * @author  Timo Müller <timomueller@databay.de>
 */
class UserImportService
{
    /** @var ilCSVWriter */
    protected $csv = null;
    /** @var ilUserInterfaceHookPlugin */
    protected $pluginObject = null;
    /**
     * @var ilLogger
     */
    private $logger;

    /**
     * UserImportService constructor.
     * @param ilUserInterfaceHookPlugin $pluginObject
     */
    public function __construct(ilUserInterfaceHookPlugin $pluginObject)
    {
        global $DIC;
        $this->pluginObject = $pluginObject;
        $this->logger = $DIC->logger()->root();
    }

    /**
     * @param string $importFile
     * @return array
     * @throws FileNotReadableException
     */
    public function convertCSVToArray(string $importFile) : array
    {
        $tmpFile = fopen($importFile, 'rb');

        if (!$tmpFile || !is_resource($tmpFile)) {
            throw new FileNotReadableException('CSV not readable');
        }

        $i = 0;
        $dataArray = [];
        while (($row = fgetcsv($tmpFile, 0, ';')) !== false) {
            if ($i === 0 && strpos($row[0], chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) === 0) {
                $row[0] = substr($row[0], 3);
            }
            $dataArray[] = trim($row[0]);
            $i++;
        }

        if (is_resource($tmpFile)) {
            fclose($tmpFile);
        }

        return $dataArray;
    }

    /**
     * @param ilObjCourse $courseObject
     * @param UserImport $userImport
     * @return ilCSVWriter
     */
    public function importUserToCourse(ilObjCourse $courseObject, UserImport $userImport) : ilCSVWriter
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
            $user = ilObjectFactory::getInstanceByObjId($filteredOutUserId, false);
            if (false === $user || !($user instanceof ilObjUser)) {
                $this->csv->addColumn('[' . $user->getId() . '] ');
            } else {
                $this->csv->addColumn('[' . $user->getId() . '] ' . $user->getFirstname() . ' ' . $user->getLastname());
            }
            $this->csv->addColumn($this->pluginObject->txt('report_csv_filtered_out_user_err_msg'));
            $this->csv->addRow();
        }

        foreach ($filteredUserIds as $filteredUserId) {
            $tmp_obj = ilObjectFactory::getInstanceByObjId($filteredUserId, false);
            if (false === $tmp_obj || !($tmp_obj instanceof ilObjUser)) {
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

    /**
     * @param ilObjGroup $groupObject
     * @param UserImport $userImport
     * @return ilCSVWriter
     */
    public function importUserToGroup(ilObjGroup $groupObject, UserImport $userImport) : ilCSVWriter
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
            if (false === $tmp_obj || !($tmp_obj instanceof ilObjUser)) {
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
     * @return int[]
     */
    private function getUserIds(UserImport $userImport) : array
    {
        $userImportRepository = new UserImportRepository();
        $usrIds = [];

        try {
            $data = json_decode($userImport->getData(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $ex) {
            $this->logger->error("Unable to decode UserImport data. Ex.: {$ex->getMessage()}");
            return [];
        }

        foreach ($data as $userData) {
            if (!$userData) {
                continue;
            }

            $userId = ilObjUser::getUserIdByLogin($userData);
            if ($userId > 0) {
                $usrIds[] = $userId;
                continue;
            }

            $userIds = ilObjUser::getUserIdsByEmail($userData);
            if (1 === count($userIds)) {
                foreach ($userIds as $userId) {
                    $usrIds[] = $userId;
                }
                continue;
            }

            $userIds = $userImportRepository->getUserIdsByMatriculation($userData);
            if (1 === count($userIds)) {
                foreach ($userIds as $userId) {
                    $usrIds[] = $userId;
                }
                continue;
            }

            $this->csv->addColumn('[' . $userData . '] ');
            $this->csv->addColumn($this->pluginObject->txt('report_csv_user_not_found_err_msg'));
            $this->csv->addRow();
        }

        return $usrIds;
    }
}
