<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Events;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Events\EventError
 */
final class EventErrorTest extends TestCase
{
    public function testSaveException(): void
    {
        $exception = new \RuntimeException('error message');
        $error = new EventError($exception);
        self::assertSame($exception, $error->getData(EventError::DATA_EXCEPTION));
    }
}
