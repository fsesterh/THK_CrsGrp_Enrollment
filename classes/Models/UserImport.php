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

namespace ILIAS\Plugin\CrsGrpEnrollment\Models;

use ILIAS\Plugin\CrsGrpEnrollment\Enum\ImportStatus;

class UserImport
{
    private ?int $id = null;

    private ImportStatus $status;

    private int $user;

    private int $created_timestamp;

    private string $data;

    private int $obj_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): ImportStatus
    {
        return $this->status;
    }

    public function setStatus(ImportStatus $status): void
    {
        $this->status = $status;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getCreatedTimestamp(): int
    {
        return $this->created_timestamp;
    }

    public function setCreatedTimestamp(int $created_timestamp): void
    {
        $this->created_timestamp = $created_timestamp;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }

    public function getObjId(): int
    {
        return $this->obj_id;
    }

    public function setObjId(int $obj_id): void
    {
        $this->obj_id = $obj_id;
    }

    /**
     * @param array{id: int, created_timestamp: int, data: string, obj_id: int, status: int, user: int} $record
     */
    public static function fromRecord(array $record): self
    {
        $import = new self();
        $import->setId((int) $record['id']);
        $import->setCreatedTimestamp((int) $record['created_timestamp']);
        $import->setData($record['data']);
        $import->setObjId((int) $record['obj_id']);
        $import->setStatus(ImportStatus::tryFrom((int) $record['status']) ?? ImportStatus::PENDING);
        $import->setUser((int) $record['user']);
        return $import;
    }
}
