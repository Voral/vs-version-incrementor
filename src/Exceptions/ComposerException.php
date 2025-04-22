<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

final class ComposerException extends ApplicationException
{
    protected int $applicationCode = 10;

    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            $message ?: 'Invalid composer.json file. Please check your composer.json file.',
            $previous,
        );
    }
}
