<?php declare(strict_types=1);

/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Data;

use ILIAS\Data\URI;

/**
 * Class TrustedURI
 * @package ILIAS\Plugin\Proctorio\Data
 */
class TrustedURI extends URI
{
    /**
     * @var string
     */
    private $uri = '';

    /**
     * TrustedURI constructor.
     * @param string $uri_string
     */
    public function __construct(string $uri_string)
    {
        $this->uri = $uri_string;
    }

    /**
     * @return string
     */
    public function getUri() : string
    {
        return $this->uri;
    }
}
