<?php

/**
 * PSR-12, replacing the StyleCI "psr2" preset. PSR-2 was deprecated in favour
 * of PSR-12 in 2019.
 *
 * Rules that would emit syntax newer than PHP 7.0 must stay off: this library
 * still supports PHP 7.0, and the fixer runs on a modern PHP that would happily
 * introduce syntax the oldest supported runtime cannot parse.
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules(array(
        '@PSR12' => true,

        // PSR-12 requires an explicit visibility on class constants, but
        // "public const" is PHP 7.1+ syntax and this library still supports
        // 7.0, where it is a parse error. Restrict the rule to methods and
        // properties, which have carried visibility since PHP 5.
        //
        // The rule is named modifier_keywords in PHP-CS-Fixer 3.90+; the older
        // visibility_required name is deprecated and configuring it has no
        // effect on the @PSR12 preset any more.
        'modifier_keywords' => array('elements' => array('method', 'property')),

        // Keep long array syntax. Short arrays are PHP 5.4+, so they would in
        // fact be safe, but rewriting every array in the library would bury the
        // real changes in this branch under thousands of lines of noise.
        'array_syntax' => array('syntax' => 'long'),
    ))
    ->setFinder($finder);
