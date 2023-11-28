<?php

declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Models;

/**
 * Class UserImport
 *
 * @package ILIAS\Plugin\CrsGrpEnrollment\Models
 * @author  Timo Müller <timomueller@databay.de>
 */
class UserImport
{
    private ?int $id = null;

    private int $status;

    private int $user;

    private int $createdTimestamp;

    private string $data;

    private int $objId;

    public const STATUS_PENDING = 0;
    public const STATUS_COMPLETED = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
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
        return $this->createdTimestamp;
    }

    public function setCreatedTimestamp(int $createdTimestamp): void
    {
        $this->createdTimestamp = $createdTimestamp;
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
        return $this->objId;
    }

    public function setObjId(int $objId): void
    {
        $this->objId = $objId;
    }

    /**
     * @param array{int, int, string, int, int, int} $record
     */
    public static function fromRecord(array $record): self
    {
        $import = new self();
        $import->setId((int) $record['id']);
        $import->setCreatedTimestamp((int) $record['created_timestamp']);
        $import->setData($record['data']);
        $import->setObjId((int) $record['obj_id']);
        $import->setStatus((int) $record['status']);
        $import->setUser((int) $record['user']);
        return $import;
    }
}
