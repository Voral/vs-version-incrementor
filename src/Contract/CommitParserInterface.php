<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ConfigNotSetException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;

/**
 * Interface CommitParserInterface.
 *
 * Defines the contract for parsing commit messages and organizing them into a collection of commits.
 * Implementations of this interface are responsible for processing commit messages according to a specific format
 * (e.g., Conventional Commits) and returning a structured collection of commits.
 */
interface CommitParserInterface extends ConfigurableInterface
{
    /**
     * Processes commit messages within a specified range of tags.
     *
     * This method parses commit messages between the given tags (`$tagsFrom` and `$tagsTo`) and organizes them into
     * a structured collection of commits. The exact behavior of parsing depends on the implementation.
     *
     * @param null|string $tagsFrom The starting tag for the range of commits to process. If null, processing starts
     *                              from the beginning of the commit history.
     * @param string      $tagsTo   The ending tag for the range of commits to process. If empty, processing continues
     *                              up to the latest commit.
     *
     * @return CommitCollection a collection of parsed commits, grouped and organized according to the parser's logic
     *
     * @throws ChangesNotFoundException
     * @throws ConfigNotSetException
     * @throws GitCommandException
     */
    public function process(?string $tagsFrom, string $tagsTo = ''): CommitCollection;
}
