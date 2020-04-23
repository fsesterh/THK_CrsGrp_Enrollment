<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Webservice;

use ILIAS\Data\URI;

/**
 * Interface Rest
 * @package ILIAS\Plugin\Proctorio\Webservice
 * @author Michael Jansen <mjansen@databay.de>
 */
interface Rest
{
    /**
     * @param \ilObjTest $test
     * @param URI $testLaunchUrl
     * @param URI $testUrl
     * @return URI
     */
    public function getLaunchUrl(
        \ilObjTest $test,
        URI $testLaunchUrl,
        URI $testUrl
    ) : URI;

    /**
     * @param \ilObjTest $test
     * @param URI $testLaunchUrl
     * @param URI $testUrl
     * @return URI
     */
    public function getReviewUrl(
        \ilObjTest $test,
        URI $testLaunchUrl,
        URI $testUrl
    ) : URI;
}
