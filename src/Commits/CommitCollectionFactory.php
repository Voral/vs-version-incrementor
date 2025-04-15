<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use Vasoft\VersionIncrement\Config;

final class CommitCollectionFactory
{
    public function __construct(
        private readonly Config $config,
        private readonly array $majorTypes,
        private readonly array $minorTypes,
        private readonly string $defaultSection,
    ) {}

    /**
     * @param array $sortedSections Sorted sections array, where keys are section names and values are section
     *                              configurations and default section exists
     */
    public function getCollection(array $sortedSections): CommitCollection
    {
        $sections = [];
        $default = null;
        foreach ($sortedSections as $key => $section) {
            $section = new Section(
                $key,
                $section['title'],
                $section['hidden'],
                $this->config->getSectionRules($key),
                in_array($key, $this->majorTypes, true),
                in_array($key, $this->minorTypes, true),
                $this->config,
            );
            if ($this->defaultSection === $key) {
                $default = $section;
            }
            $sections[$key] = $section;
        }

        return new CommitCollection($sections, /* @scrutinizer ignore-type */ $default);
    }
}
