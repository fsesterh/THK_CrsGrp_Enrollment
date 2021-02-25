<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Repositories;

use ilDBPdo;
use ilDBInterface;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\Repository\DataNotFoundException;

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

    public function __construct()
    {
        global $DIC;
        $this->db = $DIC->database();
    }

    public function save(UserImport $userImport) : UserImport
    {
        if ($userImport->getId() === null) {
            return $this->add($userImport);
        }


        $this->findOneById($userImport->getId());
        $this->update($userImport);


        return $userImport;
    }

    /** @var UserImport $userImport */
    private function update($userImport)
    {
        $this->db->manipulateF(
            '
                UPDATE ' . $this->table . ' SET
                status = :status,
                user = :user,
                created_timestamp = :created_timestamp,
                data = :data,
                obj_id = :obj_id
                WHERE id = :id
            ',
            array('integer', 'integer', 'integer', 'clob', 'integer'),
            array((int) $userImport->getStatus(), (int) $userImport->getUser, (int) $userImport->getCreatedTimestamp(), $userImport->getData(), (int) $userImport->getObjId())
        );
    }

    private function add($userImport)
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
            array('integer', 'integer', 'integer', 'integer', 'clob', 'integer'),
            array((int) $userImport->getId(), (int) $userImport->getStatus(), (int) $userImport->getUser(), (int) $userImport->getCreatedTimestamp(), $userImport->getData(), (int) $userImport->getObjId())
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
            '
                SELECT * FROM ' . $this->table . ' 
                WHERE id = %s
            ',
            array('integer'),
            array($userImportId)
        );

        if ($result->numRows() == 0) {
            throw new DataNotFoundException('No UserImport with ID ' . $userImportId . ' found');
        }
        $row = $this->db->fetchAssoc($result);
        $userImport = UserImport::fromRecord($row);
        return $userImport;
    }
}
