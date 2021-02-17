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

    /** @var ilObjUser|integer */
    private $user;

    /** @var string */
    private $createdTimestamp;

    /** @var string */
    private $data;

    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) : void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status) : void
    {
        $this->status = $status;
    }

    /**
     * @return ilObjUser|int
     */
    public function getUser()
    {
        if (is_numeric($this->user)) {
            $this->user = new ilObjUser($this->user);
        }
        return $this->user;
    }

    /**
     * @param ilObjUser|int $user
     */
    public function setUser($user) : void
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getCreatedTimestamp()
    {
        return $this->createdTimestamp;
    }

    /**
     * @param string $createdTimestamp
     */
    public function setCreatedTimestamp($createdTimestamp) : void
    {
        $this->createdTimestamp = $createdTimestamp;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data) : void
    {
        $this->data = $data;
    }
}
