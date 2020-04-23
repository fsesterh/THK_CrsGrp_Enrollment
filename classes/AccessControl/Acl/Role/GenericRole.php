<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Acl\Role;

use ILIAS\Plugin\Proctorio\AccessControl\Acl\Role;

/**
 * Class GenericRole
 * @package ILIAS\Plugin\Proctorio\AccessControl\Acl\Role
 * @author Michael Jansen <mjansen@databay.de>
 */
class GenericRole implements Role
{
    /**
     * Unique id of Role
     * @var string
     */
    protected $roleId = '';

    /**
     * GenericRole constructor.
     * @param string $roleId
     */
    public function __construct(string $roleId)
    {
        $this->roleId = $roleId;
    }

    /**
     * @inheritDoc
     */
    public function getRoleId() : string
    {
        return $this->roleId;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->getRoleId();
    }
}
