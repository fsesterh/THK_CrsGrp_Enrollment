<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Acl\Role;

use ILIAS\Plugin\Proctorio\AccessControl\Acl\Exception\InvalidArgument;
use ILIAS\Plugin\Proctorio\AccessControl\Acl\Role;

/**
 * Class Registry
 * @package ILIAS\Plugin\Proctorio\AccessControl\Acl\Role
 * @author  Michael Jansen <mjansen@databay.de>
 */
class Registry
{
    /** @var array */
    private $roles = [];

    /**
     * @param Role $role
     * @return Registry
     * @throws InvalidArgument
     */
    public function add(Role $role) : self
    {
        $roleId = $role->getRoleId();
        if ($this->has($roleId)) {
            throw new InvalidArgument(sprintf(
                'Role id "%s" already exists in the registry',
                $roleId
            ));
        }

        $this->roles[$roleId] = $role;

        return $this;
    }

    /**
     * @param mixed $role
     * @return Role
     * @throws InvalidArgument
     */
    public function get($role) : Role
    {
        if ($role instanceof Role) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }

        if (!$this->has($role)) {
            throw new InvalidArgument("Role '$roleId' not found");
        }

        return $this->roles[$roleId];
    }

    /**
     * @param mixed $role
     * @return bool
     */
    public function has($role) : bool
    {
        if ($role instanceof Role) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }

        return isset($this->roles[$roleId]);
    }

    /**
     * @return Role
     */
    public function getRoles() : array
    {
        return $this->roles;
    }
}
