<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Repositories;

use ilDBPdo;
use ilDBInterface;
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
    private $table = "xcge_user_import";

    public function __construct()
    {
        global $DIC;
        $this->db = $DIC->database();
    }

    public function save(UserImport $userImport) : UserImport
    {
        if (!empty($this->findOneById($userImport->getId()))) {
            $this->update($userImport);
        } else {
            $userImport = $this->add($userImport);
        }

        return $userImport;
    }

    /** @var UserImport $userImport */
    private function update($userImport)
    {
        $this->db->prepare('
            UPDATE ' . $this->table . ' SET
            status = :status,
            user = :user,
            created_timestamp = :created_timestamp,
            data = :data
            WHERE id = :id
        ');
        $this->db->execute(array(
            'status' => $userImport->getStatus(),
            'user' => $userImport->getUser()->getId(),
            'created_timestamp' => $userImport->getCreatedTimestamp(),
            'data' => $userImport->getData()
        ));
    }

    private function add($userImport)
    {
        $this->db->prepare('
            INSERT INTO ' . $this->table . '
            (status, user, created_timestamp, data)
            VALUES
            (:status, :user, :created_timestamp, :data)
        ');
        $this->db->execute(array(
            'status' => $userImport->getStatus(),
            'user' => $userImport->getUser()->getId(),
            'created_timestamp' => $userImport->getCreatedTimestamp(),
            'data' => $userImport->getData()
        ));

        $this->db->prepare('
            SELECT id FROM ' . $this->table . '
            ORDER BY id DESC
            LIMIT 1
        ');
        $result = $this->db->execute(array());
        $resultRow = $result->fetchAssoc();
        $userImport->setId($resultRow[0]['id']);

        return $userImport;
    }

    /** int $userImportId */
    public function findOneById($userImportId)
    {
        $this->db->prepare('
            SELECT * FROM ' . $this->table . ' 
            WHERE id = :id
        ');

        $result = $this->db->execute(array("id" => $userImportId));
        return ($result->numRows() > 0) ? $result->fetchAssoc() : null;
    }
}
