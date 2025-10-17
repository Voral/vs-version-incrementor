<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog\Interpreter;

use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;

/**
 * Regular expression-based scope interpreter.
 *
 * This interpreter uses PCRE patterns to match and transform scope strings from git commits
 * into formatted output for changelog generation. It supports both simple validation
 * (when template is empty) and pattern-based substitution.
 *
 * Usage examples:
 * - Transform task identifiers into URLs:
 *   pattern: '#^task(\d+)$#', template: '[task](https://example.com/task/$1) '
 *   input: 'task123' → output: '[task](https://example.com/task/123) '
 *
 * - Validate scope format (empty template):
 *   pattern: '#^task\d+$#', template: ''
 *   input: 'task123' → output: 'task123'
 *   input: 'invalid' → output: null
 */
class RegexpScopeInterpreter implements ScopeInterpreterInterface
{
    /**
     * @param string $pattern  PCRE-compatible regular expression pattern
     * @param string $template Replacement template using $1, $2, etc. for captured groups
     *                         When empty, acts as a validator - returns original scope on match
     */
    public function __construct(private readonly string $pattern, private readonly string $template = '') {}

    /**
     * Interprets a scope string by applying regular expression matching and substitution.
     *
     * If template is empty:
     * - Returns the original scope when pattern matches
     * - Returns null when pattern doesn't match
     *
     * If template is provided:
     * - Returns transformed string when pattern matches and substitution occurs
     * - Returns null when pattern doesn't match (no substitution occurs)
     *
     * @param string $scope The scope string from git commit (e.g., 'task123', 'database')
     *
     * @return null|string Transformed scope or original scope on validation, null if no match
     */
    public function interpret(string $scope): ?string
    {
        if ('' === $this->template) {
            return preg_match($this->pattern, $scope) ? $scope : null;
        }
        $result = preg_replace($this->pattern, $this->template, $scope);

        return $result !== $scope ? $result : null;
    }
}
