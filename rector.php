<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\ValueObject\PhpVersion;

$dirs = array_filter([
    __DIR__ . "/src",
    __DIR__ . "/classes"
], is_dir(...));

return RectorConfig::configure()
    ->withPaths($dirs)
    ->withoutParallel()
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets()
    ->withSkip([
        ClosureToArrowFunctionRector::class
    ]);
