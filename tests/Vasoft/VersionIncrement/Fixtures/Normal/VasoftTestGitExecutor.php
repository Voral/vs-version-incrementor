<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Fixtures\Normal;

use Vasoft\VersionIncrement\Commits\ChangedFiles;
use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;

class VasoftTestGitExecutor implements VcsExecutorInterface
{
    public function setConfig(Config $config): void
    {
        // TODO: Implement setConfig() method.
    }

    public function status(): array
    {
        return [];
    }

    public function addFile(string $file): void
    {
        // do nothing
    }

    public function setVersionTag(string $version): void
    {
        // do nothing
    }

    public function commit(string $message): void
    {
        // do nothing
    }

    public function getCurrentBranch(): string
    {
        return 'master';
    }

    public function getLastTag(): ?string
    {
        return 'v5.0.0';
    }

    public function getCommitsSinceLastTag(?string $lastTag): array
    {
        return [
            'c3d4e5f6g11 upd: Some Changes',
            'c3d4e5f6g14 add: Some Feature',
        ];
    }

    public function getCommitDescription(string $commitId): array
    {
        return ['feat: Some Example'];
    }

    public function getFilesSinceTag(?string $lastTag, string $pathFilter = ''): ChangedFiles
    {
        return new ChangedFiles();
    }
}
