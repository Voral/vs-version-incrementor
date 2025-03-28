<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\SectionRules\DefaultRule;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Commits\CommitCollection
 */
final class CommitCollectionTest extends TestCase
{
    public function testSetMajorMarker(): void
    {
        $sections = [
            'feat' => new Section('feat', 'Feature', false, [new DefaultRule('feat')], false, false),
            'dev' => new Section('dev', 'Development', false, [new DefaultRule('dev')], false, false),
        ];
        $commit = new CommitCollection($sections, $sections['feat']);
        self::assertFalse($commit->hasMajorMarker(), 'Initial marker state must be false');
        $commit->setMajorMarker(true);
        self::assertTrue($commit->hasMajorMarker(), 'Marker state must be set to true');
        $commit->setMajorMarker(false);
        self::assertTrue($commit->hasMajorMarker(), 'The marker state should not change');
    }
}
