<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

class ModifiedFile
{
    public function __construct(
        public readonly FileModifyType $type,
        public readonly string $path,
        public readonly string $destination,
    ) {}
}
