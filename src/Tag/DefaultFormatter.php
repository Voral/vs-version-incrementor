<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Tag;

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\TagFormatterInterface;

/**
 * Class DefaultFormatter.
 *
 * Default implementation of the `TagFormatterInterface`.
 * Provides basic functionality for formatting tags and extracting version numbers.
 */
final class DefaultFormatter implements TagFormatterInterface
{
    /**
     * Formats a version string into a tag by prepending "v" to the version.
     *
     * Example:
     * - Input: "1.0.0"
     * - Output: "v1.0.0"
     *
     * @param string $version The version number to be formatted (e.g., "1.0.0").
     *
     * @return string The formatted tag (e.g., "v1.0.0").
     */
    public function formatTag(string $version): string
    {
        return 'v' . $version;
    }

    /**
     * Extracts a version number from a tag by removing the leading "v".
     *
     * Example:
     * - Input: "v1.0.0"
     * - Output: "1.0.0"
     *
     * @param string $tag The tag containing the version information (e.g., "v1.0.0").
     *
     * @return string The extracted version number (e.g., "1.0.0").
     */
    public function extractVersion(string $tag): string
    {
        return trim(substr($tag, 1));
    }

    /**
     * Sets the configuration for the formatter.
     *
     * This method is part of the `ConfigurableInterface`, but it does nothing in this implementation
     * since the `DefaultFormatter` does not require any configuration.
     *
     * @codeCoverageIgnore
     *
     * @param Config $config the configuration object
     */
    public function setConfig(Config $config): void
    {
        // Do nothing
    }
}
