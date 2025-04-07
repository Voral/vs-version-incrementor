<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Config;

interface ConfigurableInterface
{
    public function setConfig(Config $config): void;
}
