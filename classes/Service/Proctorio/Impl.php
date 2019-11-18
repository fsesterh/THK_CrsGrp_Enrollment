<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Service\Proctorio;

use ILIAS\Plugin\Proctorio\Administration\GeneralSettings\Settings;

/**
 * Class Impl
 * @package ILIAS\Plugin\Proctorio\Service/Proctorio
 * @author Michael Jansen <mjansen@databay.de>
 */
class Impl
{
    /** @var \ilObjUser */
    private $actor;
    /** @var Settings */
    private $globalSettings;

    /**
     * Impl constructor.
     * @param \ilObjUser $actor
     * @param Settings $globalSettings
     */
    public function __construct(\ilObjUser $actor, Settings $globalSettings)
    {
        $this->actor = $actor;
        $this->globalSettings = $globalSettings;
    }

    /**
     * @return \ilObjUser
     */
    public function getActor() : \ilObjUser
    {
        return $this->actor;
    }

    /**
     * @param \ilObjTest $test
     * @return bool
     */
    public function isTestSupported(\ilObjTest $test) : bool
    {
        return $test->isRandomTest() || $test->isFixedTest();
    }
}