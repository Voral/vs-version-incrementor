<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

class BranchException extends ApplicationException
{
    protected int $applicationCode = 20;

    public function __construct(string $currentBranch = '', string $targetBranch = '', ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'You are not on the target branch. Current branch is "%s", target is "%s"',
                $currentBranch,
                $targetBranch,
            ),
            $previous,
        );
    }
}
