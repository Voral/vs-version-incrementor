<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\CommitCollection;

interface CommitParserInterface extends ConfigurableInterface
{
    public function process(?string $tagsFrom, string $tagsTo = ''): CommitCollection;
}
