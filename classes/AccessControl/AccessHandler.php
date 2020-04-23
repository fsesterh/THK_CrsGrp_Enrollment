<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl;

/**
 * Interface AccessHandler
 * @package ILIAS\Plugin\Proctorio\AccessControl
 * @author  Michael Jansen <mjansen@databay.de>
 */
interface AccessHandler
{
    /**
     * @param \ilObjUser $actor
     * @return self
     */
    public function withActor(\ilObjUser $actor) : self;

    /** @return bool */
    public function mayTakeTests(\ilObjTest $test) : bool;

    /** @return bool */
    public function mayReadTestReviews(\ilObjTest $test) : bool;

    /** @return bool */
    public function mayReadTestSettings(\ilObjTest $test) : bool;

    /** @return bool */
    public function mayWriteTestSettings(\ilObjTest $test) : bool;
}
