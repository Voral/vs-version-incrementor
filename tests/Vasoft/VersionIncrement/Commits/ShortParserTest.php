<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Exceptions\ConfigNotSetException;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Commits\ShortParser
 */
final class ShortParserTest extends TestCase
{
    public function testShortParserProcess(): void
    {
        $parser = new ShortParser();
        self::expectException(ConfigNotSetException::class);
        self::expectExceptionMessage('Configuration is not set.');
        self::expectExceptionCode(100);
        $parser->process('v1.0.0');
    }
}
