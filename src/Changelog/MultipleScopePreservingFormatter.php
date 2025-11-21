<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog;

use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;

/**
 * A changelog formatter that handles commits with multiple scopes separated by a source delimiter.
 *
 * This formatter splits the commit's scope string (e.g., 'api|db|frontend') using the source separator,
 * processes each individual scope against the list of preserved scopes (which can include interpreters
 * or literal strings), filters out non-preserved ones, joins the processed scopes using the destination
 * separator, and finally applies an overall template to the resulting string.
 *
 * It bypasses the parent's 'outputTemplate' logic. Formatting is controlled solely by the
 * 'overallTemplate' applied to the final joined scope string.
 *
 * Example:
 * - Commit scope: 'api|db'
 * - Preserved scopes: ['api', 'db'] (or interpreters)
 * - Source separator: '|'
 * - Destination separator: '|'
 * - Overall template: '%s: '
 * - Result: 'api|db: commit message'
 *
 * Example with interpreters:
 * - Commit scope: 'api|task123'
 * - Preserved scopes: [new SinglePreservedScopeInterpreter(...), new RegexpScopeInterpreter(...)]
 * - Assume 'api' maps to 'API' and 'task123' maps to '[TASK-123]' via interpreters
 * - Overall template: '%s - '
 * - Result: 'API|[TASK-123] - commit message'
 */
class MultipleScopePreservingFormatter extends ScopePreservingFormatter
{
    /**
     * Constructs a new MultipleScopePreservingFormatter instance.
     *
     * @param array<ScopeInterpreterInterface|string> $preservedScopes an array of scope names or interpreters
     *                                                                 used to determine which scopes are preserved
     *                                                                 and how they are formatted
     * @param string                                  $srcSeparator    The delimiter used to split the original commit scope string. Defaults to '|'.
     * @param string                                  $dstSeparator    The delimiter used to join the processed, preserved scopes. Defaults to '|'.
     * @param string                                  $overallTemplate The template applied to the final joined scope string. Defaults to '%s'.
     *                                                                 The '%s' placeholder will be replaced by the string resulting from
     *                                                                 joining the processed, preserved scopes.
     *                                                                 Example: if joined result is 'api|db' and template is '%s: ',
     *                                                                 the final output for scopes will be 'api|db: '.
     */
    public function __construct(
        array $preservedScopes,
        private readonly string $srcSeparator = '|',
        private readonly string $dstSeparator = '|',
        private readonly string $overallTemplate = '%s ',
    ) {
        parent::__construct($preservedScopes);
    }

    protected function getScopeForCommit(Commit $commit): string
    {
        $scopes = explode($this->srcSeparator, $commit->scope);
        $result = [];
        foreach ($scopes as $scope) {
            $formatted = $this->processScope(trim($scope));
            if ('' !== $formatted) {
                $result[] = $formatted;
            }
        }

        return empty($result) ? '' : sprintf($this->overallTemplate, implode($this->dstSeparator, $result));
    }

    private function processScope(string $commitScope): string
    {
        $scopes = $this->config?->getScopes() ?? [];
        foreach ($this->preservedScopes as $scope) {
            if ($scope instanceof ScopeInterpreterInterface) {
                $result = $scope->interpret($commitScope);
                if (null !== $result) {
                    return $result;
                }
            } elseif ($commitScope === $scope) {
                return $scopes[$commitScope] ?? $commitScope;
            }
        }

        return '';
    }
}
