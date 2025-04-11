<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

/**
 * Interface TagFormatterInterface.
 *
 * Defines methods for formatting tags and extracting version numbers from them.
 * This interface is used to standardize tag-related operations across the system.
 */
interface TagFormatterInterface extends ConfigurableInterface
{
    /**
     * Formats a version string into a tag.
     *
     * @param string $version The version number to be formatted (e.g., "1.0.0").
     *
     * @return string The formatted tag (e.g., "v1.0.0").
     */
    public function formatTag(string $version): string;

    /**
     * Extracts a version number from a tag.
     *
     * @param string $tag The tag containing the version information (e.g., "v1.0.0").
     *
     * @return string The extracted version number (e.g., "1.0.0").
     */
    public function extractVersion(string $tag): string;
}
