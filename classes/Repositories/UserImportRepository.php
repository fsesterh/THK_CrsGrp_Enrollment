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

namespace ILIAS\Plugin\CrsGrpEnrollment\Repositories;

use ilDBInterface;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\Repository\DataNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;

class UserImportRepository
{
    private ilDBInterface $db;
    private string $table = 'xcge_user_import';

    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
    }

    /**
     * @throws DataNotFoundException
     */
    public function save(UserImport $userImport): UserImport
    {
        if ($userImport->getId() === null) {
            return $this->add($userImport);
        }

        $this->findOneById($userImport->getId());
        $this->updateStatus($userImport);

        return $userImport;
    }

    public function delete(UserImport $userImport): void
    {
        $this->db->manipulateF(
            '
                DELETE FROM ' . $this->table . '
                WHERE id = %s
            ',
            ['integer'],
            [(int) $userImport->getId()]
        );
    }

    private function updateStatus(UserImport $userImport): void
    {
        $this->db->manipulateF(
            '
                UPDATE ' . $this->table . ' SET
                status = %s,
                WHERE id = %s
            ',
            ['integer', 'integer'],
            [(int) $userImport->getStatus(), (int) $userImport->getStatus()]
        );
    }

    private function add(UserImport $userImport): UserImport
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
     * @throws DataNotFoundException
     */
    public function findOneById(int $userImportId): UserImport
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
     * @return UserImport[]
     */
    public function readAll(): array
    {
        $result = $this->db->query("SELECT * FROM " . $this->table);

        $data = [];
        while ($row = $this->db->fetchAssoc($result)) {
            $data[] = UserImport::fromRecord($row);
        }

        return $data;
    }

    /**
     * @return int[]
     */
    public function getUserIdsByMatriculation(string $matriculation): array
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
