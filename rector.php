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
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

$dirs = array_filter([
    __DIR__ . "/src",
    __DIR__ . "/classes"
], is_dir(...));

return RectorConfig::configure()
    ->withPaths($dirs)
    ->withoutParallel()
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withSets([
        SetList::PHP_83,
        SetList::PHP_84,
        LevelSetList::UP_TO_PHP_83,
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        ClosureToArrowFunctionRector::class
    ]);
