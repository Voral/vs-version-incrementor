<?php

declare(strict_types=1);

use Vasoft\VersionIncrement\Config;

return (new Config())
    ->setSection('chore', 'Other changes', hidden: true)
    ->setSection('style', 'Code style', hidden: true)
    ->setSection('refactor', 'Refactor', hidden: true)
    ->setSection('ci', 'CI', hidden: true)
    ->setSection('build', 'Build', hidden: true)
    ->setSection('ref-public', 'Refactoring', hidden: false)
    ->setSection(Config::DEFAULT_SECTION, 'Default section', hidden: true);
