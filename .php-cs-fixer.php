<?php

/**
 * Configuration de PHP-CS-Fixer pour le projet Smart Taxi.
 *
 * Usage :
 *   composer format         # corrige les fichiers
 *   composer format:check   # vérifie sans corriger (utilisé par la CI)
 *
 * PHP-CS-Fixer ne corrige que ce que l'on lui demande : plutôt que le
 * jeu de règles complet @PSR12 (qui réécrirait tout le code d'un coup),
 * on liste explicitement les règles attendues, ce qui rend les
 * corrections lisibles et réversibles.
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'PHPMailer-master',
        'node_modules',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    // Les règles choisies sont purement cosmétiques : autoriser l'exécution
    // sur une version de PHP plus récente que le minimum du projet évite un
    // avertissement à chaque exécution.
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        // --- Listes et tableaux ---
        'array_syntax' => ['syntax' => 'short'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'trim_array_spaces' => true,
        'whitespace_after_comma_in_array' => true,

        // --- Espacement ---
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => true,
        'cast_spaces' => ['space' => 'none'],
        // Doit rester cohérent avec PSR-12 (phpcs), qui impose un espace
        // de part et d'autre du point de concaténation.
        'concat_space' => ['spacing' => 'one'],
        'indentation_type' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_trailing_whitespace' => true,
        'no_trailing_whitespace_in_comment' => true,
        'no_whitespace_in_blank_line' => true,
        'space_after_semicolon' => true,
        'ternary_operator_spaces' => true,
        'unary_operator_spaces' => true,

        // --- Structure ---
        'elseif' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => ['tokens' => ['extra', 'use']],
        'no_leading_import_slash' => true,
        'no_multiple_statements_per_line' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unneeded_curly_braces' => ['namespaces' => true],
        'no_useless_else' => true,
        'single_blank_line_at_eof' => true,
        'single_quote' => true,
        'switch_case_space' => true,

        // --- Casse et casts ---
        'lowercase_cast' => true,
        'lowercase_keywords' => true,
        'lowercase_static_reference' => true,
        'magic_constant_casing' => true,
        'native_function_casing' => true,
        'short_scalar_cast' => true,

        // --- Imports ---
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],

        // --- En-tête et fin de fichier ---
        'encoding' => true,
        'full_opening_tag' => true,
        'line_ending' => true,
    ])
    ->setFinder($finder);
