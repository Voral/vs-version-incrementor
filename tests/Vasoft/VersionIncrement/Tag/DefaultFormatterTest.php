<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Tag;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Tag\DefaultFormatter
 */
final class DefaultFormatterTest extends TestCase
{
    public function testExtractVersion(): void
    {
        $formatter = new DefaultFormatter();
        self::assertSame('1.0.0', $formatter->extractVersion('v1.0.0'));
        self::assertSame('1.0.1', $formatter->extractVersion('v1.0.1'));
    }

    public function testFormatTag(): void
    {
        $formatter = new DefaultFormatter();
        self::assertSame('v1.2.0', $formatter->formatTag('1.2.0'));
        self::assertSame('v2.0.0', $formatter->formatTag('2.0.0'));
    }
}
