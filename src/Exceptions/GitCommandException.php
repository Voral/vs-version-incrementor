<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class GitCommandException extends ApplicationException
{
    protected int $applicationCode = 60;

    public function __construct(string $command, array $output, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                "Error executing Git command: git %s\n%s",
                $command,
                implode("\n", $output),
            ),
            $previous,
        );
    }
}
