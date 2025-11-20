<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog\Interpreter;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Config;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Changelog\Interpreter\SinglePreservedScopeInterpreter
 */
final class SinglePreservedScopeInterpreterTest extends TestCase
{
    public function testDefaultFormat(): void
    {
        $interpreter = new SinglePreservedScopeInterpreter(['dev'], new Config());
        self::assertSame('dev: ', $interpreter->interpret('dev'));
    }

    public function testCustomFormat(): void
    {
        $interpreter = new SinglePreservedScopeInterpreter(['dev'], new Config(), '#%s# ');
        self::assertSame('#dev# ', $interpreter->interpret('dev'));
    }
}
