<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Models;

use DateTime;
use ilObjUser;

/**
 * Class UserImport
 * @package ILIAS\Plugin\CrsGrpEnrollment\Models
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImport
{
    /** @var integer */
    private $id;

    /** @var integer */
    private $status;

    /** @var integer */
    private $user;

    /** @var integer */
    private $createdTimestamp;

    /** @var string */
    private $data;

    /** @var integer */
    private $objId;

    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getStatus() : int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status) : void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getUser() : int
    {
        return $this->user;
    }

    /**
     * @param int $user
     */
    public function setUser(int $user) : void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getCreatedTimestamp() : int
    {
        return $this->createdTimestamp;
    }

    /**
     * @param int $createdTimestamp
     */
    public function setCreatedTimestamp(int $createdTimestamp) : void
    {
        $this->createdTimestamp = $createdTimestamp;
    }

    /**
     * @return string
     */
    public function getData() : string
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData(string $data) : void
    {
        $this->data = $data;
    }

    /**
     * @return int
     */
    public function getObjId() : int
    {
        return $this->objId;
    }

    /**
     * @param int $objId
     */
    public function setObjId(int $objId) : void
    {
        $this->objId = $objId;
    }

    public static function fromRecord(array $record) : self
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
