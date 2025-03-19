<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

abstract class ApplicationException extends \Exception
{
    public const CODE = 500;

    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            $message,
            static::CODE,
            $previous,
        );
    }
}
