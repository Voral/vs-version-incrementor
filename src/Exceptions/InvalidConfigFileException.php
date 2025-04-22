<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class InvalidConfigFileException extends ApplicationException
{
    protected int $applicationCode = 50;

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('Invalid configuration file.', $previous);
    }
}
