<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

enum EventType: int
{
    case BEFORE_VERSION_SET = 1;
    case AFTER_VERSION_SET = 2;
    case ON_ERROR = 3;
}
