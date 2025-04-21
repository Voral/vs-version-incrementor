<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Contract\ApplicationHandlerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Core\Help
 */
final class HelpTest extends TestCase
{
    public function testHandleEmptyHandlersArray(): void
    {
        $expected = <<<'HELP'
            Vasoft Semantic Version Increment
            run vs-version-increment [keys] [type]
            Keys:
               --help   Display this help message

            HELP;

        $help = new Help();
        ob_start();
        $resultCode = $help->handle(['--help']);
        $output = ob_get_clean();
        self::assertSame($expected, $output);
        self::assertSame(0, $resultCode);
    }

    public function testHandleTypesAndCustom(): void
    {
        $handlers = [
            self::createMock(ApplicationHandlerInterface::class),
            self::createMock(ApplicationHandlerInterface::class),
        ];
        $handlers[0]->method('getHelp')->willReturn([
            new HelpRow(Help::SECTION_KEYS, '--key', 'Description key'),
            new HelpRow(Help::SECTION_KEYS, '--long-key', 'Description key long key'),
        ]);
        $handlers[1]->method('getHelp')->willReturn([
            new HelpRow(Help::SECTION_KEYS, '--key2', 'Description key2'),
            new HelpRow(Help::SECTION_TYPES, 'type1|type2|type3', 'Description type'),
            new HelpRow('Custom', '--test', 'Description type test'),
            new HelpRow('Custom', '', 'Description empty key string 1'),
            new HelpRow('Custom', '', 'Description empty key string 2'),
        ]);

        $expected = <<<'HELP'
            Vasoft Semantic Version Increment
            run vs-version-increment [keys] [type]
            Keys:
               --key        Description key
               --long-key   Description key long key
               --key2       Description key2
               --help       Display this help message
            Type:
               type1|type2|type3   Description type
            Custom:
               --test   Description type test
                        Description empty key string 1
                        Description empty key string 2

            HELP;

        $help = new Help();
        $help->registerHandlersFromArray($handlers);
        ob_start();
        $resultCode = $help->handle(['--help']);
        $output = ob_get_clean();
        self::assertSame($expected, $output);
        self::assertSame(0, $resultCode);
    }

    public function testHandleOnlyOneFromDouble(): void
    {
        $handlers = [
            self::createMock(ApplicationHandlerInterface::class),
            self::createMock(ApplicationHandlerInterface::class),
        ];
        $handlers[0]->method('getHelp')->willReturn([
            new HelpRow(Help::SECTION_KEYS, '--key', 'Description key from handler 1'),
        ]);
        $handlers[1]->method('getHelp')->willReturn([
            new HelpRow(Help::SECTION_KEYS, '--key', 'Description key from handler 2'),
        ]);

        $expected = <<<'HELP'
            Vasoft Semantic Version Increment
            run vs-version-increment [keys] [type]
            Keys:
               --key    Description key from handler 1
               --help   Display this help message

            HELP;

        $help = new Help();
        $help->registerHandlersFromArray($handlers);
        ob_start();
        $resultCode = $help->handle(['--help']);
        $output = ob_get_clean();
        self::assertSame($expected, $output);
        self::assertSame(0, $resultCode);
    }

    public function testHandleNoKey(): void
    {
        $help = new Help();
        ob_start();
        $resultCode = $help->handle(['--debug']);
        $output = ob_get_clean();
        self::assertSame('', $output, 'Unexpected output');
        self::assertNull($resultCode, 'Unexpected result code');
    }
}
