<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog\Interpreter;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Changelog\Interpreter\RegexpScopeInterpreter
 */
final class RegexpScopeInterpreterTest extends TestCase
{
    /**
     * @dataProvider provideInterpreterCases
     */
    public function testInterpreter(string $pattern, string $template, string $scope, ?string $expected): void
    {
        $interpreter = new RegexpScopeInterpreter($pattern, $template);
        self::assertSame($expected, $interpreter->interpret($scope));
    }

    public static function provideInterpreterCases(): iterable
    {
        return [
            ['#task(\d+)#', '[task](https://example.com/task/$1)', 'task123', '[task](https://example.com/task/123)'],
            ['#task(\d+)#', 'url: https://example.com/task/$1', 'taska123', null],
            ['#^task(\d+)$#', 'url: https://example.com/task/$1', 'u_task123', null],

            ['#task(\d+)#', '', 'task123', 'task123'],
            ['#^task(\d+)$#', '$1', 'task123', '123'],
            ['#task(\d+)#', 'url: https://example.com/task/$1', '', null],
            ['#task(\d+)#', 'url: https://example.com/task/$1', 'task', null],

            ['#task\.(\d+)#', 'task-$1', 'task.123', 'task-123'],
            ['#task\[(\d+)\]#', 'task-$1', 'task[123]', 'task-123'],
            ['#task(\d+)-(\w+)#', '$1/$2', 'task123-feature', '123/feature'],
        ];
    }
}
