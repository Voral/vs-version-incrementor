<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

final class Config
{
    private array $minorTypes = [
        'feat',
    ];
    private array $majorTypes = [

    ];
    public const DEFAULT_SECTION = 'other';

    private int $defaultOrder = 500;
    private bool $sored = true;
    private string $masterBranch = 'master';
    private string $releaseSection = 'chore';

    private string $releaseScope = 'release';
    private array $sections = [
        'feat' => [
            'title' => 'New features',
            'order' => 10,
            'hidden' => false,
        ],
        'fix' => [
            'title' => 'Fixes',
            'order' => 20,
            'hidden' => false,
        ],
        'chore' => [
            'title' => 'Other changes',
            'order' => 30,
            'hidden' => false,
        ],
        'docs' => [
            'title' => 'Documentation',
            'order' => 40,
            'hidden' => false,
        ],
        'style' => [
            'title' => 'Styling',
            'order' => 50,
            'hidden' => false,
        ],
        'refactor' => [
            'title' => 'Refactoring',
            'order' => 60,
            'hidden' => false,
        ],
        'test' => [
            'title' => 'Tests',
            'order' => 70,
            'hidden' => false,
        ],
        'perf' => [
            'title' => 'Performance',
            'order' => 80,
            'hidden' => false,
        ],
        'ci' => [
            'title' => 'Configure CI',
            'order' => 90,
            'hidden' => false,
        ],
        'build' => [
            'title' => 'Change build system',
            'order' => 100,
            'hidden' => false,
        ],
        'other' => [
            'title' => 'Other',
            'order' => 110,
            'hidden' => false,
        ],
    ];

    public function setSections(array $sections): self
    {
        $this->sored = false;
        $this->sections = [];
        foreach ($sections as $key => $value) {
            $this->sections[$key] = [
                'title' => $value['title'] ?? $key,
                'order' => $value['order'] ?? ++$this->defaultOrder,
                'hidden' => $value['hidden'] ?? false,
            ];
        }

        return $this;
    }

    public function setSection(
        string $key,
        string $title,
        int $order = -1,
        bool $hidden = false,
    ): self {
        $this->sored = false;
        $this->sections[$key] = [
            'title' => $title,
            'order' => -1 === $order ? ++$this->defaultOrder : $order,
            'hidden' => $hidden,
        ];

        return $this;
    }

    public function getSectionIndex(): array
    {
        if (!$this->sored) {
            $this->checkDefaultSection();
            uasort($this->sections, static fn($a, $b) => $a['order'] <=> $b['order']);
            $this->sored = true;
        }

        return array_fill_keys(array_keys($this->sections), []);
    }

    public function getSectionTitle(string $key): string
    {
        return $this->sections[$key]['title'] ?? $key;
    }

    public function isSectionHidden(string $key): bool
    {
        return $this->sections[$key]['hidden'] ?? false;
    }

    public function setReleaseSection(string $section): self
    {
        $this->releaseSection = $section;

        return $this;
    }

    public function getReleaseSection(): string
    {
        return isset($this->sections[$this->releaseSection]) ? $this->releaseSection : self::DEFAULT_SECTION;
    }

    private function checkDefaultSection(): void
    {
        if (!isset($this->sections[self::DEFAULT_SECTION])) {
            $maxOrder = 0;
            foreach ($this->sections as $section) {
                $maxOrder = max($maxOrder, $section['order']);
            }
            $this->sections[self::DEFAULT_SECTION] = [
                'title' => 'Other',
                'order' => $maxOrder + 1000,
                'hidden' => false,
            ];
        }
    }

    public function getMasterBranch(): string
    {
        return $this->masterBranch;
    }

    public function setMasterBranch(string $masterBranch): self
    {
        $this->masterBranch = $masterBranch;

        return $this;
    }

    public function setMinorTypes(array $minorTypes): self
    {
        $this->minorTypes = $minorTypes;

        return $this;
    }

    public function setMajorTypes(array $majorTypes): self
    {
        $this->majorTypes = $majorTypes;

        return $this;
    }

    public function getMajorTypes(): array
    {
        return $this->majorTypes;
    }

    public function getMinorTypes(): array
    {
        return $this->minorTypes;
    }

    public function setReleaseScope(string $releaseScope): self
    {
        $this->releaseScope = $releaseScope;

        return $this;
    }

    public function getReleaseScope(): string
    {
        return $this->releaseScope;
    }
}
