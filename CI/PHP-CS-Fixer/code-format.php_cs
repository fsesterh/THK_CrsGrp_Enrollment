<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
	->exclude(array(
		__DIR__ . '/../../sql'
	))
	->in([
		__DIR__ .  '/../../classes'
	])
;

return PhpCsFixer\Config::create()
	->setRules([
        '@PSR2' => true,
        'strict_param' => false,
        'cast_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
        'unary_operator_spaces' => true,
        'function_typehint_space' => true,
        'return_type_declaration' => ['space_before' => 'one'],
        'binary_operator_spaces' => true
	])
	->setFinder($finder);
