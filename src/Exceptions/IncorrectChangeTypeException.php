<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class IncorrectChangeTypeException extends ApplicationException
{
    protected int $applicationCode = 70;

    public function __construct(string $changeType, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf("Invalid change type: %s. Available types are: major, minor, patch.\n", $changeType),
            $previous,
        );
    }
}
