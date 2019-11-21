<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Acl;

use ILIAS\Plugin\Proctorio\AccessControl\Acl;
use ILIAS\Plugin\Proctorio\AccessControl\Acl\Role\Registry;

/**
 * Class Impl
 * @package ILIAS\Plugin\Proctorio\AccessControl\Acl
 * @author  Michael Jansen <mjansen@databay.de>
 */
class Impl implements Acl
{
    /** Rule type: allow */
    const TYPE_ALLOW = 'TYPE_ALLOW';
    /** Rule type: deny */
    const TYPE_DENY = 'TYPE_DENY';
    /** Rule operation: add */
    const OP_ADD = 'OP_ADD';
    /** Rule operation: remove */
    const OP_REMOVE = 'OP_REMOVE';

    /** @var Registry */
    private $roleRegistry;
    /** @var array */
    private $resources = [];

    /**
     * ACL rules; whitelist (deny everything to all) by default
     * @var array
     */
    protected $rules = [
        'allResources' => [
            'allRoles' => [
                'allPrivileges' => [
                    'type'   => self::TYPE_DENY,
                    'assert' => null
                ],
                'byPrivilegeId' => []
            ],
            'byRoleId' => []
        ],
        'byResourceId' => []
    ];

    /**
     * Impl constructor.
     * @param Registry $roleRegistry
     */
    public function __construct(Registry $roleRegistry)
    {
        $this->roleRegistry = $roleRegistry;
    }

    /**
     * @param Role $role
     * @return Impl
     */
    public function addRole(Role $role) : Impl
    {
        $this->roleRegistry->add($role);

        return $this;
    }

    /**
     * @param string $role
     * @return Role
     */
    public function getRole(string $role) : Role
    {
        return $this->roleRegistry->get($role);
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role) : bool
    {
        return $this->roleRegistry->has($role);
    }

    /**
     * @param Resource $resource
     * @return Impl
     */
    public function addResource(Resource $resource) : Impl
    {
        $this->resources[$resource->getResourceId()] = [
            'instance' => $resource,
        ];

        return $this;
    }

    /**
     * @param mixed $resource
     * @return Resource
     * @throws \InvalidArgumentException
     */
    public function getResource($resource) : Resource
    {
        if ($resource instanceof Resource) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        if (!$this->hasResource($resource)) {
            throw new \InvalidArgumentException("Resource '$resourceId' not found");
        }

        return $this->resources[$resourceId]['instance'];
    }

    /**
     * @param mixed $resource
     * @return bool
     */
    public function hasResource($resource) : bool
    {
        if ($resource instanceof Resource) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        return isset($this->resources[$resourceId]);
    }

    /**
     * @param string $role
     * @param string $resource
     * @param string $privilege
     * @return Impl
     */
    public function allow(
        string $role,
        string $resource,
        string $privilege
    ) : Impl {
        return $this->setRule(self::OP_ADD, self::TYPE_ALLOW, $role, $resource, $privilege);
    }

    /**
     * @param string $operation
     * @param string $type
     * @param string $role
     * @param string $resource
     * @param string $privilege
     * @return Impl
     */
    private function setRule(
        string $operation,
        string $type,
        string $role,
        string $resource,
        string $privilege
    ) : Impl {
        $roleObj     = $this->roleRegistry->get($role);
        $resourceObj = $this->getResource($resource);

        switch ($operation) {
            case self::OP_ADD:
                $rules                                      =& $this->getRules($resourceObj, $roleObj, true);
                $rules['byPrivilegeId'][$privilege]['type'] = $type;
                break;

            case self::OP_REMOVE:
                $rules =& $this->getRules($resourceObj, $roleObj, true);

                if (
                    isset($rules['byPrivilegeId'][$privilege]) &&
                    $type === $rules['byPrivilegeId'][$privilege]['type']
                ) {
                    unset($rules['byPrivilegeId'][$privilege]);
                }
                break;
        }

        return $this;
    }

    /**
     * Returns the rules associated with a Resource and a Role, or null if no such rules exist
     * @param Resource|null $resource
     * @param Role|null     $role
     * @param bool          $create
     * @return array
     */
    private function &getRules(
        Resource $resource = null,
        Role $role = null,
        $create = false
    ) : array {
        $null    = null;
        $nullRef =& $null;

        do {
            if (null === $resource) {
                $visitor =& $this->rules['allResources'];
                break;
            }

            $resourceId = $resource->getResourceId();

            if (!isset($this->rules['byResourceId'][$resourceId])) {
                if (!$create) {
                    return $nullRef;
                }

                $this->rules['byResourceId'][$resourceId] = [];
            }

            $visitor =& $this->rules['byResourceId'][$resourceId];
        } while (false);

        if (null === $role) {
            if (!isset($visitor['allRoles'])) {
                if (!$create) {
                    return $nullRef;
                }

                $visitor['allRoles']['byPrivilegeId'] = [];
            }

            return $visitor['allRoles'];
        }

        $roleId = $role->getRoleId();
        if (!isset($visitor['byRoleId'][$roleId])) {
            if (!$create) {
                return $nullRef;
            }

            $visitor['byRoleId'][$roleId]['byPrivilegeId'] = [];
        }

        return $visitor['byRoleId'][$roleId];
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(string $role, string $resource, string $privilege) : bool
    {
        $role     = $this->roleRegistry->get($role);
        $resource = $this->getResource($resource);
        if (!isset($this->rules['byResourceId'][$resource->getResourceId()])) {
            return false;
        }
        $rulesOfResource = $this->rules['byResourceId'][$resource->getResourceId()];

        if (!isset($rulesOfResource['byRoleId'][$role->getRoleId()])) {
            return false;
        }
        $rulesOfRole = $rulesOfResource['byRoleId'][$role->getRoleId()];

        if (!isset($rulesOfRole['byPrivilegeId'][$privilege])) {
            return false;
        }
        $decision = $rulesOfRole['byPrivilegeId'][$privilege];

        return self::TYPE_ALLOW === $decision['type'];
    }

    /**
     * @return Role[]
     */
    public function getRoles() : array
    {
        return $this->roleRegistry->getRoles();
    }
}