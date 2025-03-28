<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\SectionRules;

use Vasoft\VersionIncrement\Contract\SectionRuleInterface;

class DefaultRule implements SectionRuleInterface
{
    public function __construct(private readonly string $type) {}

    public function __invoke(string $type, string $scope, array $flags, string $comment): bool
    {
        return $type === $this->type;
    }
}
