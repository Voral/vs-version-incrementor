<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

require_once __DIR__ . '/vendor/autoload.php';

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return
    (new Config())
        ->setFinder($finder)
        ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
        ->setRiskyAllowed(true)
        ->setRules([
            '@PHP80Migration:risky' => true,
            '@PHP81Migration' => true,
            '@PhpCsFixer' => true,
            '@PhpCsFixer:risky' => true,
            '@PHPUnit84Migration:risky' => true,
            '@PER-CS2.0' => true,
            '@PER-CS2.0:risky' => true,
            'blank_line_before_statement' => [
                'statements' => [
                    'continue',
                    'declare',
                    'default',
                    'return',
                    'throw',
                    'try',
                ],
            ],
            'native_function_invocation' => false,
            'multiline_whitespace_before_semicolons' => true,
        ]);
