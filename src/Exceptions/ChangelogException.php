<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

final class ChangelogException extends ApplicationException
{
    protected int $applicationCode = 80;

    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            $message ?: 'Changelog file is not writable.',
            $previous,
        );
    }
}
