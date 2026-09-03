<?php

require_once __DIR__ . "/vendor/autoload.php";

$dirs = array_filter([
    __DIR__ . "/src",
    __DIR__ . "/classes"
], is_dir(...));


$finder = PhpCsFixer\Finder::create()
    ->exclude([__DIR__ . "/vendor"])
    ->in($dirs)
    ->name("*.php");

return (new PhpCsFixer\Config())
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
