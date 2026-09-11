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

use ilDBConstants;
use ilDBInterface;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\Repository\DataNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;

class UserImportRepository
{
    private readonly ilDBInterface $db;
    public const TABLE_NAME = 'xcge_user_import';

    public function __construct()
    {
        global $DIC;

        $this->db = $DIC->database();
    }

    /**
     * @throws DataNotFoundException
     */
    public function save(UserImport $user_import): UserImport
    {
        if ($user_import->getId() === null) {
            return $this->add($user_import);
        }

        $this->findOneById($user_import->getId());
        $this->updateStatus($user_import);

        return $user_import;
    }

    public function delete(UserImport $user_import): void
    {
        $this->db->manipulateF(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE id = %s',
            [ilDBConstants::T_INTEGER],
            [(int) $user_import->getId()]
        );
    }

    private function updateStatus(UserImport $user_import): void
    {
        $this->db->manipulateF(
            '
                UPDATE ' . self::TABLE_NAME . ' SET
                status = %s
                WHERE id = %s
            ',
            [ilDBConstants::T_INTEGER, ilDBConstants::T_INTEGER],
            [$user_import->getStatus()->value, (int) $user_import->getId()]
        );
    }

    private function add(UserImport $user_import): UserImport
    {
        $next_id = $this->db->nextId(self::TABLE_NAME);
        $user_import->setId($next_id);
        $this->db->manipulateF(
            '
                INSERT INTO ' . self::TABLE_NAME . '
                (id, status, user, created_timestamp, data, obj_id)
                VALUES
                (%s, %s, %s, %s, %s, %s)
            ',
            [
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_INTEGER,
                ilDBConstants::T_CLOB,
                ilDBConstants::T_INTEGER
            ],
            [
                (int) $user_import->getId(),
                $user_import->getStatus()->value,
                $user_import->getUser(),
                $user_import->getCreatedTimestamp(),
                $user_import->getData(),
                $user_import->getObjId()
            ]
        );

        return $user_import;
    }

    /**
     * @throws DataNotFoundException
     */
    public function findOneById(int $user_import_id): UserImport
    {
        $result = $this->db->queryF(
            'SELECT * FROM ' . self::TABLE_NAME . ' WHERE id = %s',
            [ilDBConstants::T_INTEGER],
            [$user_import_id]
        );

        if ($result->numRows() === 0) {
            throw new DataNotFoundException('No UserImport with ID ' . $user_import_id . ' found');
        }

        $row = $this->db->fetchAssoc($result);
        return UserImport::fromRecord($row);
    }

    /**
     * @return UserImport[]
     */
    public function readAll(): array
    {
        $result = $this->db->query("SELECT * FROM " . self::TABLE_NAME);

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
        $usr_ids = [];

        $result = $this->db->queryF(
            'SELECT usr_id FROM usr_data WHERE matriculation = %s',
            [ilDBConstants::T_TEXT],
            [$matriculation]
        );

        while ($row = $this->db->fetchAssoc($result)) {
            $usr_ids[] = (int) $row['usr_id'];
        }

        return $usr_ids;
    }
}
