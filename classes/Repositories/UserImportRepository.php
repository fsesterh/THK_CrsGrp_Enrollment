<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Repositories;

use ilDBInterface;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\Repository\DataNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;

/**
 * Class UserImportRepository
 * @package ILIAS\Plugin\CrsGrpEnrollment\Repositories
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportRepository
{
    /** @var ilDBInterface */
    private $db;
    /** @var string */
    private $table = 'xcge_user_import';

    /**
     * UserImportRepository constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
    }

    /**
     * @param UserImport $userImport
     * @return UserImport
     * @throws DataNotFoundException
     */
    public function save(UserImport $userImport) : UserImport
    {
        if ($userImport->getId() === null) {
            return $this->add($userImport);
        }

        $this->findOneById($userImport->getId());
        $this->updateStatus($userImport);

        return $userImport;
    }

    /**
     * @param UserImport $userImport
     */
    private function updateStatus(UserImport $userImport) : void
    {
        $this->db->manipulateF(
            '
                UPDATE ' . $this->table . ' SET
                status = %s,
                WHERE id = %s
            ',
            ['integer', 'integer'],
            [(int) $userImport->getStatus(), (int) $userImport->getObjId()]
        );
    }

    /**
     * @param UserImport $userImport
     * @return UserImport
     */
    private function add(UserImport $userImport) : UserImport
    {
        $nextId = $this->db->nextId($this->table);
        $userImport->setId((int) $nextId);
        $this->db->manipulateF(
            '
                INSERT INTO ' . $this->table . '
                (id, status, user, created_timestamp, data, obj_id)
                VALUES
                (%s, %s, %s, %s, %s, %s)
            ',
            ['integer', 'integer', 'integer', 'integer', 'clob', 'integer'],
            [
                (int) $userImport->getId(),
                (int) $userImport->getStatus(),
                (int) $userImport->getUser(),
                (int) $userImport->getCreatedTimestamp(),
                $userImport->getData(),
                (int) $userImport->getObjId()
            ]
        );

        return $userImport;
    }

    /**
     * @param int $userImportId
     * @return UserImport
     * @throws DataNotFoundException
     */
    public function findOneById(int $userImportId) : UserImport
    {
        $result = $this->db->queryF(
            'SELECT * FROM ' . $this->table . ' WHERE id = %s',
            ['integer'],
            [$userImportId]
        );

        if ($result->numRows() == 0) {
            throw new DataNotFoundException('No UserImport with ID ' . $userImportId . ' found');
        }

        $row = $this->db->fetchAssoc($result);
        $userImport = UserImport::fromRecord($row);

        return $userImport;
    }

    /**
     * @param string $matriculation
     * @return int[]
     */
    public function getUserIdsByMatriculation(string $matriculation) : array
    {
        $usrIds = [];

        $result = $this->db->queryF(
            'SELECT usr_id FROM usr_data WHERE matriculation = %s',
            ['text'],
            [$matriculation]
        );
        
        while ($row = $this->db->fetchAssoc($result)) {
            $usrIds[] = (int) $row['usr_id'];
        }

        return $usrIds;
    }
}
