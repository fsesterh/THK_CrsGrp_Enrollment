<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Refinery\Transformation;

use ILIAS\Data\URI;
use ILIAS\Transformation\Transformation;

/**
 * Class UriToString
 * @package ILIAS\Plugin\Proctorio\Refinery\Transformation
 * @author Michael Jansen <mjansen@databay.de>
 */
class UriToString implements Transformation
{
    /**
     * @inheritdoc
     */
    public function transform($from)
    {
        if (false === $from instanceof URI) {
            throw new \InvalidArgumentException(
                sprintf('The value MUST be of type "%s"', URI::class),
                'not_uri_object'
            );
        }

        /** @var URI $from */
        $result = $from->baseURI();

        $query  = $from->query();
        if (null !== $query) {
            $query = '?' . $query;
        }
        $result .= $query;

        $fragment = $from->fragment();
        if (null !== $fragment) {
            $fragment = '#' . $fragment;
        }
        $result   .= $fragment;

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function __invoke($from)
    {
        return $this->transform($from);
    }
}
