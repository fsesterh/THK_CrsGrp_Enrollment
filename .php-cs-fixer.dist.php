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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

require_once __DIR__ . "/vendor/autoload.php";

$dirs = array_filter([
    __DIR__ . "/src",
    __DIR__ . "/classes"
], is_dir(...));


$finder = Finder::create()
    ->exclude([__DIR__ . "/vendor"])
    ->in($dirs)
    ->name("*.php");

return (new Config())
    ->setUsingCache(false)
    ->setFinder($finder)
    ->setRules([
        "@PSR12" => true,
        "strict_param" => false,
        "cast_spaces" => true,
        "concat_space" => ["spacing" => "one"],
        "unary_operator_spaces" => true,
        "type_declaration_spaces" => true,
        "binary_operator_spaces" => true,
        "array_syntax" => ["syntax" => "short"],
        "function_declaration" => ["closure_fn_spacing" => "none"],
        "fully_qualified_strict_types" => ["import_symbols" => true],
        "no_superfluous_phpdoc_tags" => [
            "allow_mixed" => true,
            "remove_inheritdoc" => true,
        ],
        "no_empty_phpdoc" => true,
        "no_unused_imports" => true
    ]);
