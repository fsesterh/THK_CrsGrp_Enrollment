<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\BackgroundTask;

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\Repository\DataNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ilObjUser;

/**
 * Class UserImportJob
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportJob extends AbstractJob
{
    /**
     * @inheritdoc
     */
    public function run(array $input, Observer $observer)
    {
        global $DIC;
        $userImportRepository = new UserImportRepository();

        try {
            /** @var UserImport $userImport */
            $userImport = $userImportRepository->findOneById((int) $input[0]->getValue());

            $DIC->logger()->root()->info(sprintf(
                'Start User Import with this users: %s',
                json_encode($userImport->getData(), JSON_PRETTY_PRINT)
            ));

            $users = $this->getUserObjects($userImport);

            //ToDo - Weitere Implementation von den Zuweisungen
            //Schritt 1: Benutzer ID und Object ID auf existenz prüfen
            //Schritt 2: Ist es ein Kurs oder eine Gruppe
            //Schritt 3: Zuweisungslogik implementieren
            //Schritt 4: Protokoll im UserImportReport bauen und zum Download bereitstellen
            //Schritt 5: Fertig
        } catch (DataNotFoundException $e) {
            $DIC->logger()->root()->info(sprintf(
                'No User Import found for the ID: %s',
                $input[0]->getValue()
            ));
        }

        $output = new StringValue();
        $output->setValue($userImport->getData());
        return $output;
    }
    /** @var UserImport $userImport */
    private function getUserObjects($userImport)
    {
        foreach (json_decode($userImport->getData(), true) as $userData) {
            $users = [];
            if (is_numeric($userData)) {
                $user[] = new ilObjUser($userData);
            } else {
                $userIds = ilObjUser::getUserIdsByEmail($userData);
                if (count($userIds) > 0) {
                    foreach ($userIds as $userId) {
                        $user[] = new ilObjUser($userId);
                    }
                }
            }
        }

        return $users;
    }

    /**
     * @inheritdoc
     */
    public function getInputTypes()
    {
        return [
            new SingleType(IntegerValue::class), // 0. User Import Id
        ];
    }

    /**
     * @inheritdoc
     */
    public function getOutputType()
    {
        return new SingleType(StringValue::class);
    }

    /**
     * @inheritdoc
     */
    public function isStateless()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getExpectedTimeOfTaskInSeconds()
    {
        return 30;
    }
}
