<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

final class Commit
{
    public function __construct(
        public readonly string $rawMessage,
        public readonly string $type,
        public readonly string $comment,
        public readonly bool $breakingChange,
        public readonly string $scope = '',
        public readonly array $flags = [],
    ) {}

    public function withType(string $detectedKey): self
    {
        return new self(
            $this->rawMessage,
            $detectedKey,
            $this->comment,
            $this->breakingChange,
            $this->scope,
            $this->flags,
        );
    }
}
