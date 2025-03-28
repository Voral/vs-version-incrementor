<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;

/**
 * It generates a changelog in a default format by iterating through visible sections and their commits.
 * This formatter does not preserve or filter scopes; it simply lists all commits under their respective sections.
 */
final class DefaultFormatter implements ChangelogFormatterInterface
{
    /**
     * Generates a changelog in the default format.
     *
     * @param CommitCollection $commitCollection a collection of commits grouped into sections
     * @param string           $version          the version number for which the changelog is generated
     *
     * @return string The formatted changelog as a string.
     *
     * The changelog includes:
     * - The version number and date at the top.
     * - Sections with their titles.
     * - Commits listed under each section without preserving scopes.
     */
    public function __invoke(CommitCollection $commitCollection, string $version): string
    {
        $date = date('Y-m-d');
        $changelog = "# {$version} ({$date})\n\n";
        $sections = $commitCollection->getVisibleSections();
        foreach ($sections as $section) {
            $changelog .= sprintf("### %s\n", $section->title);
            foreach ($section->getCommits() as $commit) {
                $changelog .= "- {$commit->comment}\n";
            }
            $changelog .= "\n";
        }

        return $changelog;
    }
}
