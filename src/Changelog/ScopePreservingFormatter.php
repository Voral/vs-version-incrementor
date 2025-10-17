<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog;

use Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter;
use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;
use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;

/**
 * It generates a changelog while preserving specific scopes passed to the constructor.
 * If no scopes are specified, all scopes are preserved.
 */
class ScopePreservingFormatter implements ChangelogFormatterInterface
{
    private ?Config $config = null;

    /**
     * Constructs a new ScopePreservingFormatter instance.
     *
     * @param array<RegexpScopeInterpreter|string> $preservedScopes An optional array of scopes to preserve in the changelog.
     *                                                              If empty, all scopes will be included.
     */
    public function __construct(private readonly array $preservedScopes = []) {}

    /**
     * Generates a changelog while preserving specified scopes.
     *
     * @param CommitCollection $commitCollection a collection of commits grouped into sections
     * @param string           $version          the version number for which the changelog is generated
     *
     * @return string The formatted changelog as a string.
     *
     * The changelog includes:
     * - The version number and date at the top.
     * - Sections with their titles.
     * - Commits listed under each section, with scopes preserved based on the constructor configuration.
     *   If a commit has a scope that matches one of the preserved scopes, it is included in the output.
     *   Otherwise, the scope is omitted unless no scopes are specified (all scopes are preserved).
     */
    public function __invoke(CommitCollection $commitCollection, string $version): string
    {
        $date = date('Y-m-d');
        $changelog = "# {$version} ({$date})\n\n";
        $sections = $commitCollection->getVisibleSections();
        foreach ($sections as $section) {
            $changelog .= sprintf("### %s\n", $section->title);
            foreach ($section->getCommits() as $commit) {
                $scope = $this->getScopeForCommit($commit);
                $changelog .= "- {$scope}{$commit->comment}\n";
            }
            $changelog .= "\n";
        }

        return $changelog;
    }

    private function getScopeForCommit(Commit $commit): string
    {
        if ('' === $commit->scope) {
            return '';
        }

        foreach ($this->preservedScopes as $scope) {
            if ($scope instanceof ScopeInterpreterInterface) {
                $result = $scope->interpret($commit->scope);
                if (null !== $result) {
                    return $result;
                }
            } elseif ($commit->scope === $scope) {
                $scopes = $this->config?->getScopes() ?? [];
                $scope = $scopes[$commit->scope] ?? $commit->scope;

                return sprintf('%s: ', $scope);
            }
        }

        return '';
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }
}
