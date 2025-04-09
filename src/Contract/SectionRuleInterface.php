<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\Commit;

interface SectionRuleInterface
{
    public function __invoke(Commit $commit): bool;
}
