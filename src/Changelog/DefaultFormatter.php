<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;

final class DefaultFormatter implements ChangelogFormatterInterface
{
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
