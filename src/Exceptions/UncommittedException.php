<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class UncommittedException extends ApplicationException
{
    protected int $applicationCode = 30;

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'There are uncommitted changes in the repository.',
            $previous,
        );
    }
}
