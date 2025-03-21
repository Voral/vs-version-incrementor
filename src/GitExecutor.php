<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Contract\GetExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;

class GitExecutor implements GetExecutorInterface
{
    public function status(): array
    {
        return $this->runCommand('status --porcelain');
    }

    public function addFile(string $file): void
    {
        $this->runCommand('add ' . $file);
    }

    public function setVersionTag(string $version): void
    {
        $this->runCommand('tag v' . $version);
    }

    public function commit(string $message): void
    {
        $this->runCommand("commit -am '" . $message . "'");
    }

    public function getCurrentBranch(): string
    {
        $branch = $this->runCommand('rev-parse --abbrev-ref HEAD');

        return trim($branch[0] ?? '');
    }

    public function getLastTag(): ?string
    {
        $tags = $this->runCommand('tag --sort=-creatordate');

        return $tags[0] ?? null;
    }

    public function getCommitsSinceLastTag(?string $lastTag): array
    {
        $command = $lastTag ? "log {$lastTag}..HEAD --pretty=format:%s" : 'log --pretty=format:%s';

        return $this->runCommand($command);
    }

    /**
     * @throws GitCommandException
     */
    private function runCommand(string $command): array
    {
        exec("git {$command} 2>&1", $output, $returnCode);
        if (0 !== $returnCode) {
            throw new GitCommandException($command, $output);
        }

        return $output;
    }
}
