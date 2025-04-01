<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Config;

interface CommitParserInterface
{
    public function __construct(VcsExecutorInterface $vcs);

    public function process(Config $config, ?string $tagsFrom, string $tagsTo = ''): CommitCollection;
}
