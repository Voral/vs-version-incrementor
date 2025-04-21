<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

class EventError extends Event
{
    public const DATA_EXCEPTION = 'exception';

    public function __construct(\Throwable $exception)
    {
        parent::__construct(EventType::ON_ERROR);
        $this->setData(self::DATA_EXCEPTION, $exception);
    }
}
