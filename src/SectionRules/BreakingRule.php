<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\SectionRules;

use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Contract\SectionRuleInterface;

class BreakingRule implements SectionRuleInterface
{
    public function __invoke(Commit $commit): bool
    {
        return $commit->breakingChange;
    }
}
