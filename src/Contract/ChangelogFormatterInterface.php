<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\CommitCollection;

interface ChangelogFormatterInterface
{
    public function __invoke(CommitCollection $commitCollection, string $version): string;
}
