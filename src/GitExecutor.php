<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\FileModifyType;
use Vasoft\VersionIncrement\Commits\ModifiedFile;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;
use Vasoft\VersionIncrement\Exceptions\VcsNoChangedFilesException;

class GitExecutor implements VcsExecutorInterface
{
    /**
     * @codeCoverageIgnore
     */
    public function setConfig(Config $config): void
    {
        // Do nothing
    }

    public function status(): array
    {
        return $this->runCommand('status --porcelain');
    }

    public function getCommitDescription(string $commitId): array
    {
        return $this->runCommand('log -1 --pretty=format:%b ' . $commitId);
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
        $command = $lastTag ? "log {$lastTag}..HEAD --pretty=format:\"%H %s\"" : 'log --pretty=format:"%H %s"';

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

    /**
     * Retrieves the list of changed files since the specified Git tag.
     *
     * This method executes a `git diff` command to analyze changes between the given tag and the current state.
     * It categorizes files into added, removed, modified, renamed, and copied groups, ensuring no file appears
     * in conflicting categories (e.g., a file cannot be both added and removed).
     *
     * @param null|string $lastTag    The Git tag to compare against. If null, compares against the initial commit.
     * @param string      $pathFilter optional path filter to limit the scope of the diff operation
     *
     * @return array<ModifiedFile> List DTO of changed files
     *
     * @throws VcsNoChangedFilesException if no changes are found since the specified tag
     * @throws GitCommandException        if there's an issue executing the `git diff` command
     */
    public function getFilesSinceTag(?string $lastTag, string $pathFilter = ''): array
    {
        $command = "diff --name-status {$lastTag}...";
        if (!empty($pathFilter)) {
            $command .= " -- {$pathFilter}";
        }
        $files = $this->runCommand($command);
        if (empty($files)) {
            throw new VcsNoChangedFilesException($lastTag ?? '<initial commit>');
        }
        $collection = [];
        foreach ($files as $file) {
            $fields = explode("\t", $file);
            if (count($fields) < 2) {
                continue;
            }
            $status = trim($fields[0]);
            $status = $status[0];
            $filePath = trim($fields[1]);
            $oldFilePath = trim($fields[2] ?? '');
            $type = FileModifyType::tryFrom($status);
            if (null === $type) {
                continue;
            }
            $collection[] = new ModifiedFile($type, $filePath, $oldFilePath);
        }

        return $collection;
    }
}
