<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class ConfigNotSetException extends ApplicationException
{
    public const CODE = 100;

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Configuration is not set.', $previous);
    }
}
