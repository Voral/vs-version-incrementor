<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Config;
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
        $config = new Config();
        $sections = [
            'feat' => new Section('feat', 'Feature', false, [new DefaultRule('feat')], false, false, $config),
            'dev' => new Section('dev', 'Development', false, [new DefaultRule('dev')], false, false, $config),
        ];
        $commit = new CommitCollection($sections, $sections['feat']);
        self::assertFalse($commit->hasMajorMarker(), 'Initial marker state must be false');
        $commit->setMajorMarker(true);
        self::assertTrue($commit->hasMajorMarker(), 'Marker state must be set to true');
        $commit->setMajorMarker(false);
        self::assertTrue($commit->hasMajorMarker(), 'The marker state should not change');
    }

    public function testIterable(): void
    {
        $config = new Config();
        $sections = [
            'feat' => new Section('feat', 'Feature', false, [new DefaultRule('feat')], false, false, $config),
            'dev' => new Section('dev', 'Development', false, [new DefaultRule('dev')], false, false, $config),
        ];
        $commit = new CommitCollection($sections, $sections['feat']);
        $iterator = $commit->getIterator();
        $items  = iterator_to_array($iterator);

        self::assertSame($sections['feat'], $items[0]);
        self::assertSame($sections['dev'], $items[1]);
    }
}
