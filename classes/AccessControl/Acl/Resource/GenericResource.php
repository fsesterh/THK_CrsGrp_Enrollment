<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Acl\Resource;

use ILIAS\Plugin\Proctorio\AccessControl\Acl\Resource;

/**
 * Class GenericResource
 * @package ILIAS\Plugin\Proctorio\AccessControl\Acl\Resource
 * @author  Michael Jansen <mjansen@databay.de>
 */
class GenericResource implements Resource
{
    /**
     * Unique id of Role
     * @var string
     */
    protected $resourceId = '';

    /**
     * GenericRole constructor.
     * @param string $roleId
     */
    public function __construct(string $roleId)
    {
        $this->resourceId = $roleId;
    }

    /**
     * @inheritDoc
     */
    public function getResourceId() : string
    {
        return $this->resourceId;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->getResourceId();
    }
}