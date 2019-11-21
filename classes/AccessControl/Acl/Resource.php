<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Acl;

/**
 * Interface Resource
 * @package ILIAS\Plugin\Proctorio\AccessControl\Acl
 */
interface Resource
{
    /**
     * @return string
     */
    public function getResourceId() : string;
}