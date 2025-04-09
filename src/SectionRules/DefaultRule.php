<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\SectionRules;

use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Contract\SectionRuleInterface;

class DefaultRule implements SectionRuleInterface
{
    public function __construct(private readonly string $type) {}

    public function __invoke(Commit $commit): bool
    {
        return $commit->type === $this->type;
    }
}
