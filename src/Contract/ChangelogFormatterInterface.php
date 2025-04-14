<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\CommitCollection;

/**
 * Interface ChangelogFormatterInterface.
 *
 * Defines the contract for formatting changelog content based on a collection of commits and a version number.
 * Implementations of this interface are responsible for generating the final changelog string
 * for a given version and set of commits.
 */
interface ChangelogFormatterInterface extends ConfigurableInterface
{
    /**
     * Formats the changelog content for a specific version.
     *
     * This method is invoked to generate the changelog string based on the provided commit collection
     * and version number. The implementation should define the structure and format of the changelog.
     *
     * @param CommitCollection $commitCollection A collection of commits grouped by sections.
     *                                           Represents all the changes made for the given version.
     * @param string           $version          The version number for which the changelog is being generated (e.g., "1.0.0").
     *
     * @return string the formatted changelog content as a string
     */
    public function __invoke(CommitCollection $commitCollection, string $version): string;
}
