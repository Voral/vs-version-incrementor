<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

final class HelpRow
{
    public function __construct(
        public readonly string $section,
        public readonly string $key,
        public readonly string $description,
    ) {}
}
