<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Events\Event
 */
final class EventTest extends TestCase
{
    /**
     * @dataProvider provideSaveDataAnyTypesCases
     */
    public function testSaveDataAnyTypes(mixed $value, string $message): void
    {
        $event = new Event(EventType::BEFORE_VERSION_SET);
        $event->setData('test', $value);
        self::assertSame($value, $event->getData('test'), $message);
    }

    public static function provideSaveDataAnyTypesCases(): iterable
    {
        yield ['test', 'save string'];
        yield [true, 'save bool'];
        yield [1, 'save int'];
        yield [[1, 2, 3], 'save array'];
        yield [new \DateTime(), 'save date'];
        yield [null, 'save null'];
        yield [1.23, 'save float'];
        yield [new \stdClass(), 'save object'];
    }

    public function testVersionEditable(): void
    {
        $event = new Event(EventType::BEFORE_VERSION_SET);
        self::assertSame('', $event->version, 'Default value from constructor');
        $event->version = '1.2.3';
        self::assertSame('1.2.3', $event->version, 'Value not editable');
    }
}
