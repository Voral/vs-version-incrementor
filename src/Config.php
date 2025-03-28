<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Commits\Section;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;
use Vasoft\VersionIncrement\SectionRules\DefaultRule;
use Vasoft\VersionIncrement\SectionRules\SectionRuleInterface;

final class Config
{
    private string $squashedCommitMessage = 'Squashed commit of the following:';
    private bool $processDefaultSquashedCommit = false;
    private array $minorTypes = [
        'feat',
    ];
    private array $majorTypes = [];
    private array $sectionRules = [];
    public const DEFAULT_SECTION = 'other';

    private int $defaultOrder = 500;
    private bool $sored = true;
    private string $masterBranch = 'master';
    private string $releaseSection = 'chore';

    private string $releaseScope = 'release';

    private string $aggregateSection = '';

    private ?ChangelogFormatterInterface $changelogFormatter = null;

    private bool $ignoreUntrackedFiles = false;
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

    public function getSectionIndex(): array
    {
        $this->sortSections();

        return array_fill_keys(array_keys($this->sections), []);
    }

    public function getCommitCollection(): CommitCollection
    {
        $this->sortSections();
        $sections = [];
        $default = null;
        foreach ($this->sections as $key => $section) {
            $section = new Section(
                $key,
                $section['title'],
                $section['hidden'],
                $this->getSectionRules($key),
                in_array($key, $this->majorTypes, true),
                in_array($key, $this->minorTypes, true),
            );
            if (self::DEFAULT_SECTION === $key) {
                $default = $section;
            }
            $sections[$key] = $section;
        }

        return new CommitCollection($sections, $default);
    }

    private function sortSections(): void
    {
        if (!$this->sored) {
            $this->checkDefaultSection();
            uasort($this->sections, static fn($a, $b) => $a['order'] <=> $b['order']);
            $this->sored = true;
        }
    }

    public function getSectionDescriptions(): array
    {
        $this->sortSections();
        $result = [];
        foreach ($this->sections as $key => $section) {
            $result[] = sprintf('%s - %s', $key, $section['title']);
        }

        return $result;
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

    public function setReleaseScope(string $releaseScope): self
    {
        $this->releaseScope = $releaseScope;

        return $this;
    }

    public function getReleaseScope(): string
    {
        return $this->releaseScope;
    }

    public function setIgnoreUntrackedFiles(bool $ignoreUntrackedFiles): self
    {
        $this->ignoreUntrackedFiles = $ignoreUntrackedFiles;

        return $this;
    }

    public function mastIgnoreUntrackedFiles(): bool
    {
        return $this->ignoreUntrackedFiles;
    }

    public function addSectionRule(string $key, SectionRuleInterface $rule): self
    {
        $this->sectionRules[$key][] = $rule;

        return $this;
    }

    /**
     * @return SectionRuleInterface[]
     */
    public function getSectionRules(string $key): array
    {
        $this->sectionRules[$key]['default'] = new DefaultRule($key);

        return $this->sectionRules[$key];
    }

    public function setAggregateSection(string $aggregateSection): self
    {
        $this->aggregateSection = $aggregateSection;

        return $this;
    }

    public function getAggregateSection(): string
    {
        return $this->aggregateSection;
    }

    public function setSquashedCommitMessage(string $squashedCommitMessage): self
    {
        $this->squashedCommitMessage = $squashedCommitMessage;

        return $this;
    }

    public function getSquashedCommitMessage(): string
    {
        return $this->squashedCommitMessage;
    }

    public function setProcessDefaultSquashedCommit(bool $processDefaultSquashedCommit): self
    {
        $this->processDefaultSquashedCommit = $processDefaultSquashedCommit;

        return $this;
    }

    public function shouldProcessDefaultSquashedCommit(): bool
    {
        return $this->processDefaultSquashedCommit;
    }

    public function getChangelogFormatter(): ChangelogFormatterInterface
    {
        if (!$this->changelogFormatter) {
            $this->changelogFormatter = new Changelog\DefaultFormatter();
        }

        return $this->changelogFormatter;
    }

    public function setChangelogFormatter(ChangelogFormatterInterface $changelogFormatter): void
    {
        $this->changelogFormatter = $changelogFormatter;
    }
}
