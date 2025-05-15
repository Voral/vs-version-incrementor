<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\ChangedFiles;
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
     * @return ChangedFiles a DTO containing categorized lists of changed files
     *
     * @throws VcsNoChangedFilesException if no changes are found since the specified tag
     * @throws GitCommandException        if there's an issue executing the `git diff` command
     */
    public function getFilesSinceTag(?string $lastTag, string $pathFilter = ''): ChangedFiles
    {
        $command = "diff --name-status {$lastTag}...";
        if (!empty($pathFilter)) {
            $command .= " -- {$pathFilter}";
        }
        $added = [];
        $removed = [];
        $modified = [];
        $renamed = [];
        $copied = [];

        $files = $this->runCommand($command);
        if (empty($files)) {
            throw new VcsNoChangedFilesException($lastTag);
        }

        foreach ($files as $file) {
            $fields = explode("\t", $file);
            if (count($fields) < 2) {
                continue;
            }
            $status = trim($fields[0]);
            $status = preg_replace('/\s+/', '', $status);

            $filePath = trim($fields[1]);
            $oldFilePath = trim($fields[2] ?? '');
            switch ($status) {
                case 'A':
                    $this->checkUnset($filePath, $renamed);
                    $this->checkUnset($filePath, $removed);
                    $this->checkUnset($filePath, $copied);
                    $added[$filePath] = $filePath;
                    break;
                case 'D':
                    $this->checkUnset($filePath, $added);
                    $this->checkUnset($filePath, $modified);
                    $this->checkInArrayUnset($filePath, $renamed);
                    $this->checkInArrayUnset($filePath, $copied);
                    $removed[$filePath] = $filePath;
                    break;
                case 'M':
                    $modified[$filePath] = $filePath;
                    break;
                case 'R':
                    $this->checkInArrayUnset($filePath, $added);
                    $renamed[$filePath] = $oldFilePath;
                    break;
                case 'C':
                    $this->checkInArrayUnset($filePath, $added);
                    $copied[$filePath] = $oldFilePath;
                    break;
            }
        }

        return new ChangedFiles(
            $added,
            $removed,
            $modified,
            $renamed,
            $copied,
        );
    }

    /**
     * Removes a value from an array if it exists.
     *
     * This method searches for the given value in the array and removes it by its key.
     * It ensures that the value is completely removed from the array to avoid conflicts.
     *
     * @param string        $value  the value to search for and remove
     * @param array<string> &$array the array to modify by reference
     */
    private function checkInArrayUnset(string $value, array &$array): void
    {
        $key = array_search($value, $array, true);

        if (false !== $key) {
            unset($array[$key]);
        }
    }

    /**
     * Removes a key from an array if it exists.
     *
     * This method checks if the specified key exists in the array and removes it.
     * It is used to ensure that a file does not appear in multiple change categories.
     *
     * @param string               $key    the key to check and remove
     * @param array<string,string> &$array the array to modify by reference
     */
    private function checkUnset(string $key, array &$array): void
    {
        if (isset($array[$key])) {
            unset($array[$key]);
        }
    }
}
