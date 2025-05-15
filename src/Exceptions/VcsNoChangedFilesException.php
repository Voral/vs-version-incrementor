<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class VcsNoChangedFilesException extends ApplicationException
{
    protected int $applicationCode = 110;

    public function __construct(string $tag, ?\Throwable $previous = null)
    {
        parent::__construct("Failed to retrieve files since tag {$tag}", $previous);
    }
}
