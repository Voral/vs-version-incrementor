<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Exceptions\UserException
 */
final class UserExceptionTest extends TestCase
{
    public function testUserCod(): void
    {
        $userException = new UserException(1000, 'test');
        self::expectExceptionMessage('test');
        self::expectExceptionCode(6000);

        throw $userException;
    }
}
