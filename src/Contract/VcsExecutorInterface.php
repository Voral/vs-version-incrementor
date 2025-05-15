<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\ModifiedFile;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;

/**
 * Interface VcsExecutorInterface.
 *
 * Defines the contract for executing version control system (VCS) commands, such as Git.
 * Implementations of this interface are responsible for interacting with the VCS to perform
 * operations like retrieving status, adding files, creating tags, committing changes, and more.
 */
interface VcsExecutorInterface extends ConfigurableInterface
{
    /**
     * Retrieves the current status of the repository.
     *
     * This method executes a command to get the status of the repository, such as untracked files,
     * modified files, and other relevant information.
     *
     * @return array an array containing the status information of the repository
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function status(): array;

    /**
     * Adds a file to the staging area of the repository.
     *
     * This method executes a command to stage the specified file for the next commit.
     *
     * @param string $file the path to the file to be added
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function addFile(string $file): void;

    /**
     * Creates a version tag in the repository.
     *
     * This method executes a command to create a new tag with the specified version number.
     *
     * @param string $version The version number to be used for the tag (e.g., "v1.0.0").
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function setVersionTag(string $version): void;

    /**
     * Commits changes to the repository with the given commit message.
     *
     * This method executes a command to commit staged changes with the provided message.
     *
     * @param string $message the commit message to be used for the commit
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function commit(string $message): void;

    /**
     * Retrieves the name of the current branch in the repository.
     *
     * This method executes a command to determine the currently active branch.
     *
     * @return string The name of the current branch (e.g., "main" or "master").
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function getCurrentBranch(): string;

    /**
     * Retrieves the most recent tag in the repository.
     *
     * This method executes a command to find the last tag applied to the repository.
     *
     * @return null|string the most recent tag, or null if no tags exist
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function getLastTag(): ?string;

    /**
     * Retrieves the list of commits since the specified tag.
     *
     * This method executes a command to retrieve all commits made after the given tag.
     *
     * @param null|string $lastTag The tag from which to start retrieving commits. If null, retrieves
     *                             commits from the beginning of the repository history.
     *
     * @return array an array of commit information since the specified tag
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function getCommitsSinceLastTag(?string $lastTag): array;

    /**
     * Retrieves the description of a specific commit by its ID.
     *
     * This method executes a command to fetch detailed information about the specified commit.
     *
     * @param string $commitId the unique identifier (hash) of the commit
     *
     * @return array an array containing the details of the commit, such as message, author, and date
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function getCommitDescription(string $commitId): array;

    /**
     * Retrieves the list of changed files since the specified tag.
     *
     * This method analyzes the changes between the given tag and the current state of the repository.
     * It categorizes files into groups such as added, removed, modified, renamed, and copied.
     * Files are grouped to ensure that no file appears in conflicting categories (e.g., a file cannot
     * be both added and removed).
     *
     * @param null|string $lastTag    The tag to compare against. If null, compares against the initial commit.
     * @param string      $pathFilter optional path filter to limit the scope of the diff operation
     *
     * @return array<ModifiedFile> List DTO of changed files
     *
     * @throws GitCommandException if an error occurs while executing the VCS command
     */
    public function getFilesSinceTag(?string $lastTag, string $pathFilter = ''): array;
}
