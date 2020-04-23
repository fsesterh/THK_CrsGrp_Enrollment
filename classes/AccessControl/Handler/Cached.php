<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Handler;

use ILIAS\Plugin\Proctorio\AccessControl;
use ILIAS\Plugin\Proctorio\Entry\Model;

/**
 * Class Cached
 * @package ILIAS\Plugin\Proctorio\AccessControl\Handler
 * @author  Michael Jansen <mjansen@databay.de>
 */
class Cached implements AccessControl\AccessHandler
{
    /** @var AccessControl\AccessHandler */
    private $origin;

    /** @var array */
    private $cache = [];

    /**
     * Cached constructor.
     * @param AccessControl\AccessHandler $origin
     */
    public function __construct(
        AccessControl\AccessHandler $origin
    ) {
        $this->origin = $origin;
    }

    /**
     * @inheritDoc
     */
    public function withActor(\ilObjUser $actor) : AccessControl\AccessHandler
    {
        $clone = clone $this;
        $clone->origin = $clone->origin->withActor($actor);
        $clone->cache = [];

        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function mayTakeTests(\ilObjTest $test) : bool
    {
        if (isset($this->cache[__METHOD__])) {
            return $this->cache[__METHOD__];
        }

        return ($this->cache[__METHOD__] = $this->origin->mayTakeTests($test));
    }

    /**
     * @inheritDoc
     */
    public function mayReadTestReviews(\ilObjTest $test) : bool
    {
        if (isset($this->cache[__METHOD__])) {
            return $this->cache[__METHOD__];
        }

        return ($this->cache[__METHOD__] = $this->origin->mayReadTestReviews($test));
    }

    /**
     * @inheritDoc
     */
    public function mayReadTestSettings(\ilObjTest $test) : bool
    {
        if (isset($this->cache[__METHOD__])) {
            return $this->cache[__METHOD__];
        }

        return ($this->cache[__METHOD__] = $this->origin->mayReadTestSettings($test));
    }

    /**
     * @inheritDoc
     */
    public function mayWriteTestSettings(\ilObjTest $test) : bool
    {
        if (isset($this->cache[__METHOD__])) {
            return $this->cache[__METHOD__];
        }

        return ($this->cache[__METHOD__] = $this->origin->mayWriteTestSettings($test));
    }
}
