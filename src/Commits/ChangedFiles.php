<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

class ChangedFiles
{
    public function __construct(
        public readonly array $added = [],
        public readonly array $removed = [],
        public readonly array $modified = [],
        public readonly array $renamed = [],
        public readonly array $copied = [],
    ) {}
}
