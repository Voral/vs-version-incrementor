<?php

declare(strict_types=1);

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\SectionRules;

return (new Config())
    ->setHideDoubles(true)
    ->setSection('breaking', 'BREAKING CHANGES', 0)
    ->addSectionRule('breaking', new SectionRules\BreakingRule())
    ->setSection('chore', 'Other changes', hidden: true)
    ->setSection('style', 'Code style', hidden: true)
    ->setSection('ci', 'CI', hidden: true)
    ->setSection('build', 'Build', hidden: true)
    ->setSection('pubref', 'Refactoring', hidden: false)
    ->setSection(Config::DEFAULT_SECTION, 'Default section', hidden: true);
