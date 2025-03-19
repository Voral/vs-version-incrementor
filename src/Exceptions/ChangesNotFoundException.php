<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class ChangesNotFoundException extends ApplicationException
{
    public const CODE = 40;

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(
            'Changes not found in repository from previous release',
            $previous,
        );
    }
}
