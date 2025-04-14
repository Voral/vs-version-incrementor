<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Config;

/**
 * Interface ConfigurableInterface.
 *
 * Defines the contract for classes that can be configured with a `Config` object.
 * Implementations of this interface must provide a method to set the configuration.
 */
interface ConfigurableInterface
{
    /**
     * Sets the configuration for the implementing class.
     *
     * This method allows the injection of a `Config` object, which can be used to customize
     * the behavior of the class. The configuration typically includes settings such as sections,
     * rules, formatters, or other options relevant to the class's functionality.
     *
     * @param Config $config the configuration object to be applied
     */
    public function setConfig(Config $config): void;
}
