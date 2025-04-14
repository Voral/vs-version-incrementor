<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class UnknownPropertyException extends ApplicationException
{
    public const CODE = 90;

    public function __construct(string $key, ?\Throwable $previous = null)
    {
        parent::__construct(
            'Unknown property: ' . $key,
            $previous,
        );
    }
}
