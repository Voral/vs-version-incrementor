<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

class Event
{
    private array $data = [];

    public function __construct(
        public readonly EventType $eventType,
        public string $version = '',
    ) {}

    public function setData(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function getData(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }
}
