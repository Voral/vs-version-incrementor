<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

/**
 * Interface for scope interpreters that transform git commit scopes into formatted representations.
 *
 * Implementations of this interface are responsible for interpreting scope strings from git commits
 * and converting them into human-readable or linked formats for changelog generation.
 *
 * Common use cases include:
 * - Converting task identifiers into clickable URLs
 * - Formatting module names for better readability
 * - Adding contextual information to scope references
 */
interface ScopeInterpreterInterface
{
    /**
     * Interprets a git commit scope and returns its formatted representation.
     *
     * The method takes a raw scope string from a git commit message and transforms it
     * into a display-friendly format. If the scope cannot be interpreted or doesn't match
     * expected patterns, returns null to indicate it should be skipped or handled differently.
     *
     * @param string $scope The raw scope string from git commit (e.g., 'task123', 'database', 'release')
     *
     * @return null|string Formatted scope representation for changelog, or null if scope cannot be interpreted
     */
    public function interpret(string $scope): ?string;
}
