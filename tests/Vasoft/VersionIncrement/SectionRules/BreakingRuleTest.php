<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\SectionRules;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Commits\Commit;

/**
 * @coversDefaultClass \Vasoft\VersionIncrement\SectionRules\BreakingRule
 *
 * @internal
 */
final class BreakingRuleTest extends TestCase
{
    public function testBreakingChangeCommit(): void
    {
        $commit = new Commit('feat!: title', 'feat', 'title', true);
        $rule = new BreakingRule();
        self::assertTrue($rule($commit), 'Breaking change commit must be matched');
    }

    public function testNotBreakingChangeCommit(): void
    {
        $commit = new Commit('feat: title', 'feat', 'title', false);
        $rule = new BreakingRule();
        self::assertFalse($rule($commit), 'Not breaking change commit must not be matched');
    }
}
