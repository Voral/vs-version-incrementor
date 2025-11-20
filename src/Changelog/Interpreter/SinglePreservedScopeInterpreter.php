<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog\Interpreter;

use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;
use Vasoft\VersionIncrement\Config;

/**
 * Scope interpreter that checks if a single scope is in a predefined list of preserved scopes
 * and formats it according to a specified template.
 *
 * This interpreter is typically used in conjunction with a formatter that handles
 * individual scopes (e.g., ScopePreservingFormatter) or processes a list of scopes
 * derived from a single commit's scope field (e.g., a future MultipleScopePreservingFormatter).
 *
 * It supports mapping the raw scope name to a human-readable title using the provided Config.
 * If the input scope is not in the list of preserved scopes, or if the mapped title is empty,
 * it returns null, indicating the scope should not be included or formatted.
 */
class SinglePreservedScopeInterpreter implements ScopeInterpreterInterface
{
    /**
     * Constructs a new SinglePreservedScopeInterpreter instance.
     *
     * @param array<string> $preservedScopes An array of scope names that are allowed to be processed and formatted.
     *                                       Only scopes present in this list will be handled.
     * @param Config        $config          the configuration object, used to retrieve human-readable titles for scope names
     * @param string        $template        The template used to format the scope name. Defaults to '%s :'.
     *                                       The '%s' placeholder will be replaced by the processed scope string
     *                                       (either the mapped title from config or the raw scope name if no mapping exists).
     *                                       Note: The template itself is responsible for adding any desired separators
     *                                       (e.g., ':', ' - '). The example '%s :' will result in 'scope_name :'.
     */
    public function __construct(
        private readonly array $preservedScopes,
        private readonly Config $config,
        private readonly string $template = '%s: ',
    ) {}

    /**
     * Interprets a single scope string.
     *
     * Checks if the provided scope is in the list of preserved scopes.
     * If yes, it attempts to map the scope name to a human-readable title using the config.
     * Then, it formats the resulting title using the configured template.
     * If the scope is not preserved or the final formatted string would be empty,
     * it returns null.
     *
     * @param string $scope the raw scope string to interpret
     *
     * @return null|string The formatted scope string (e.g., 'api :') if the scope is preserved and non-empty,
     *                     or null if the scope is not in the preserved list or results in an empty formatted string.
     */
    public function interpret(string $scope): ?string
    {
        if (!in_array($scope, $this->preservedScopes, true)) {
            return null;
        }
        $scopes = $this->config->getScopes();
        $scopeFormatted = $scopes[$scope] ?? $scope;

        return '' === $scopeFormatted ? '' : sprintf($this->template, $scopeFormatted);
    }
}
