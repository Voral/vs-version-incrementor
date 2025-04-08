<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Exceptions\GitCommandException;

interface VcsExecutorInterface extends ConfigurableInterface
{
    /**
     * @throws GitCommandException
     */
    public function status(): array;

    /**
     * @throws GitCommandException
     */
    public function addFile(string $file): void;

    /**
     * @throws GitCommandException
     */
    public function setVersionTag(string $version): void;

    /**
     * @throws GitCommandException
     */
    public function commit(string $message): void;

    /**
     * @throws GitCommandException
     */
    public function getCurrentBranch(): string;

    /**
     * @throws GitCommandException
     */
    public function getLastTag(): ?string;

    /**
     * @throws GitCommandException
     */
    public function getCommitsSinceLastTag(?string $lastTag): array;

    /**
     * @throws GitCommandException
     */
    public function getCommitDescription(string $commitId): array;
}
