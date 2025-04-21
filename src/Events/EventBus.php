<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

use Vasoft\VersionIncrement\Contract\EventListenerInterface;

class EventBus
{
    private array $listeners = [];

    public function addListener(EventType $eventType, EventListenerInterface $listener): void
    {
        $this->listeners[$eventType->value][] = $listener;
    }

    public function dispatch(Event $event): void
    {
        if (!isset($this->listeners[$event->eventType->value])) {
            return;
        }
        foreach ($this->listeners[$event->eventType->value] as $listener) {
            $listener->handle($event);
        }
    }
}
