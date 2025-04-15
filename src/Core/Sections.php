<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

final class Sections
{
    public const DEFAULT_SECTION = 'other';
    private bool $sored = true;
    private int $defaultOrder = 500;
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
        ?bool $hidden = null,
    ): self {
        $this->sored = false;
        $exists = $this->sections[$key] ?? null;
        $this->sections[$key] = [
            'title' => $title,
            'order' => -1 === $order ? ($exists ? $exists['order'] : ++$this->defaultOrder) : $order,
            'hidden' => null !== $hidden ? $hidden : ($exists ? $exists['hidden'] : false),
        ];

        return $this;
    }

    public function getIndex(): array
    {
        $this->sortSections();

        return array_fill_keys(array_keys($this->sections), []);
    }

    private function sortSections(): void
    {
        if (!$this->sored) {
            $this->checkDefaultSection();
            uasort($this->sections, static fn($a, $b) => $a['order'] <=> $b['order']);
            $this->sored = true;
        }
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

    public function getSortedSections(): array
    {
        $this->sortSections();

        return $this->sections;
    }

    public function getTitles(): array
    {
        $this->sortSections();
        $result = [];
        foreach ($this->sections as $key => $value) {
            $result[$key] = $value['title'];
        }

        return $result;
    }

    public function isHidden(string $key): bool
    {
        return $this->sections[$key]['hidden'] ?? false;
    }

    public function getTitle(string $key): string
    {
        return $this->sections[$key]['title'] ?? $key;
    }

    public function exits(string $key): bool
    {
        return array_key_exists($key, $this->sections);
    }
}
