<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Contract\EventListenerInterface;

/**
 * @internal
 *
 * @coversDefaultClass \VASoft\VersionIncrement\Events\EventBus
 */
final class EventBusTest extends TestCase
{
    public function testDispatchEmptyListenerList(): void
    {
        $eventBus = new EventBus();

        $listener = $this->createMock(EventListenerInterface::class);
        $listener->method('handle')
            ->willReturnCallback(static function (Event $event): void {
                $event->version = 'changed';
            });
        $eventBus->addListener(EventType::BEFORE_VERSION_SET, $listener);

        $event1 = new Event(EventType::ON_ERROR);
        $eventBus->dispatch($event1);
        self::assertSame('', $event1->version, 'version can not be changed if listener list is empty');

        $event2 = new Event(EventType::BEFORE_VERSION_SET);
        $eventBus->dispatch($event2);
        self::assertSame('changed', $event2->version, 'version not changed');
    }
}
