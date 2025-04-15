<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Commits\Section;
use Vasoft\VersionIncrement\Contract\ChangelogFormatterInterface;
use Vasoft\VersionIncrement\Contract\CommitParserInterface;
use Vasoft\VersionIncrement\Contract\SectionRuleInterface;
use Vasoft\VersionIncrement\Contract\TagFormatterInterface;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\UnknownPropertyException;
use Vasoft\VersionIncrement\SectionRules\DefaultRule;

/**
 * Class Config.
 *
 * Represents the configuration for the version increment tool. This class provides methods to configure various
 * aspects of the tool, such as sections, rules, version control settings, and formatters. It also manages default
 * configurations and ensures consistency across the application.
 */
final class Config
{
    private array $props = [];
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

    private bool $enabledComposerVersioning = true;

    private ?ChangelogFormatterInterface $changelogFormatter = null;
    private ?CommitParserInterface $commitParser = null;
    private ?VcsExecutorInterface $vcsExecutor = null;
    private ?TagFormatterInterface $tagFormatter = null;

    private bool $hideDoubles = false;

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

    /**
     * Sets the sections configuration for the tool.
     *
     * This method allows you to define a custom set of sections, each with its own title, order, and visibility settings.
     * Existing sections are cleared before applying the new configuration. The order of sections is reset, and the default
     * section (`other`) will be added automatically if not explicitly defined.
     *
     * Each section can be configured with the following optional parameters:
     * - `title`: The display name of the section in the CHANGELOG (defaults to the section key).
     * - `order`: The sorting priority of the section (auto-incremented if not provided).
     * - `hidden`: Whether the section should be hidden in the CHANGELOG (defaults to `false`).
     *
     * @param array $sections an associative array where keys are section codes and values are arrays containing
     *                        the section's configuration (`title`, `order`, and `hidden`)
     *
     * @return $this this Config instance for method chaining
     */
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

    /**
     * Sets or updates the configuration for a specific section.
     *
     * This method allows you to define or modify the settings of a section, such as its title, order, and visibility.
     * If the section already exists, its configuration is updated; otherwise, a new section is created. Existing values
     * for `order` and `hidden` are preserved unless explicitly overridden.
     *
     * @param string    $key    the unique identifier (code) of the section
     * @param string    $title  the display name of the section in the CHANGELOG
     * @param int       $order  The sorting priority of the section. Defaults to `-1`, which preserves the existing order
     *                          or assigns a new auto-incremented value if the section does not exist.
     * @param null|bool $hidden Whether the section should be hidden in the CHANGELOG. Defaults to `null`, which
     *                          preserves the existing visibility setting or sets it to `false` if the section
     *                          does not exist.
     *
     * @return $this this Config instance for method chaining
     */
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

    /**
     * Retrieves the index of sections as an array with empty values.
     *
     * This method sorts the sections internally and returns an array where the keys are section names and the values
     * are empty arrays. This is useful for initializing data structures that require a predefined set of sections.
     *
     * @return array an associative array with section names as keys and empty arrays as values
     */
    public function getSectionIndex(): array
    {
        $this->sortSections();

        return array_fill_keys(array_keys($this->sections), []);
    }

    /**
     * Retrieves the commit collection based on the configured sections.
     *
     * This method creates a `CommitCollection` object by converting the configured sections into `Section` objects.
     * It ensures that all sections are sorted and that a default section exists if not explicitly defined.
     *
     * @return CommitCollection a collection of commits grouped by sections
     */
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
                $this,
            );
            if (self::DEFAULT_SECTION === $key) {
                $default = $section;
            }
            $sections[$key] = $section;
        }

        return new CommitCollection($sections, /* @scrutinizer ignore-type */ $default);
    }

    private function sortSections(): void
    {
        if (!$this->sored) {
            $this->checkDefaultSection();
            uasort($this->sections, static fn($a, $b) => $a['order'] <=> $b['order']);
            $this->sored = true;
        }
    }

    /**
     * Retrieves the descriptions of all configured sections.
     *
     * This method generates a list of section descriptions in the format "key - title".
     * The sections are sorted internally before generating the descriptions. Each description consists of the section's
     * unique identifier (key) and its display name (title).
     *
     * Used for --list option
     *
     * @return array an array of strings, where each string represents a section description in the format "key - title"
     */
    public function getSectionDescriptions(): array
    {
        $this->sortSections();
        $result = [];
        foreach ($this->sections as $key => $section) {
            $result[] = sprintf('%s - %s', $key, $section['title']);
        }

        return $result;
    }

    /**
     * Retrieves the title of a specific section.
     *
     * This method returns the title of the section identified by the given key. If the section does not exist, the key
     * itself is returned as the title.
     *
     * @param string $key the key of the section
     *
     * @return string the title of the section or the key if the section does not exist
     */
    public function getSectionTitle(string $key): string
    {
        return $this->sections[$key]['title'] ?? $key;
    }

    /**
     * Checks whether a specific section is hidden.
     *
     * This method determines if the section identified by the given key is marked as hidden in the configuration. If
     * the section does not exist, it is considered visible (not hidden).
     *
     * @param string $key the key of the section
     *
     * @return bool returns `true` if the section is hidden, `false` otherwise
     */
    public function isSectionHidden(string $key): bool
    {
        return $this->sections[$key]['hidden'] ?? false;
    }

    /**
     * Sets the section to be used for release commits.
     *
     * This method defines the section that will be associated with release-related commits. The specified section will be
     * used when generating release commit messages or determining the scope of a release.
     *
     * @param string $section The key of the section to be used for releases (e.g., 'release').
     *
     * @return $this this Config instance for method chaining
     */
    public function setReleaseSection(string $section): self
    {
        $this->releaseSection = $section;

        return $this;
    }

    /**
     * Retrieves the key of the section configured for release commits.
     *
     * This method returns the key of the section that is associated with release-related commits. If the configured
     * release section does not exist in the sections list, the default section (`other`) is returned instead.
     *
     * @return string the key of the release section or the default section if the configured release section is invalid
     */
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

    /**
     * Retrieves the name of the main branch in the repository.
     *
     * By default, it is set to "master".
     *
     * @return string The name of the main branch (e.g., "main" or "master").
     */
    public function getMasterBranch(): string
    {
        return $this->masterBranch;
    }

    /**
     * Sets the name of the main branch in the repository.
     *
     * This method allows you to configure the name of the main branch (e.g., "main" or "master") used by the tool.
     *
     * @param string $masterBranch the name of the main branch
     *
     * @return $this this Config instance for method chaining
     */
    public function setMasterBranch(string $masterBranch): self
    {
        $this->masterBranch = $masterBranch;

        return $this;
    }

    /**
     * Sets the types of changes that trigger a minor version increment.
     *
     * This method configures the list of commit types that, when present, will cause the minor version to be
     * incremented during version updates.
     *
     * @param array $minorTypes An array of commit type codes (e.g., ['feat', 'fix']).
     *
     * @return $this this Config instance for method chaining
     */
    public function setMinorTypes(array $minorTypes): self
    {
        $this->minorTypes = $minorTypes;

        return $this;
    }

    /**
     * Sets the types of changes that trigger a major version increment.
     *
     * This method configures the list of commit types that, when present, will cause the major version to be
     * incremented during version updates.
     *
     * @param array $majorTypes An array of commit type codes (e.g., ['breaking']).
     *
     * @return $this this Config instance for method chaining
     */
    public function setMajorTypes(array $majorTypes): self
    {
        $this->majorTypes = $majorTypes;

        return $this;
    }

    /**
     * Sets the scope to be used for release commit messages.
     *
     * This method defines the scope that will be included in the description of release-related commits.
     * If an empty string is provided, no scope will be added to the release commit message.
     *
     * @param string $releaseScope The scope to be used for release commits (e.g., 'rel').
     *                             Use an empty string to omit the scope from the commit message.
     *
     * @return $this this Config instance for method chaining
     */
    public function setReleaseScope(string $releaseScope): self
    {
        $this->releaseScope = $releaseScope;

        return $this;
    }

    /**
     * Retrieves the scope configured for release commit messages.
     *
     * This method returns the scope that will be included in the description of release-related commits.
     * If no scope is configured, an empty string is returned, indicating that no scope will be added to the commit message.
     *
     * @return string the scope for release commit messages, or an empty string if no scope is configured
     */
    public function getReleaseScope(): string
    {
        return $this->releaseScope;
    }

    /**
     * Enables or disables ignoring untracked files in the repository.
     *
     * This method configures whether untracked files should be ignored when running the version increment tool.
     * By default, untracked files are not ignored, and their presence may cause the tool to fail.
     *
     * @param bool $ignoreUntrackedFiles Whether to ignore untracked files:
     *                                   - `true`: Ignore untracked files.
     *                                   - `false`: Do not ignore untracked files (default behavior).
     *
     * @return $this this Config instance for method chaining
     */
    public function setIgnoreUntrackedFiles(bool $ignoreUntrackedFiles): self
    {
        $this->ignoreUntrackedFiles = $ignoreUntrackedFiles;

        return $this;
    }

    /**
     * Checks whether untracked files are ignored in the repository.
     *
     * This method retrieves the current configuration for ignoring untracked files. If enabled, the tool will not
     * consider untracked files when performing operations.
     *
     * @return bool returns `true` if untracked files are ignored, `false` otherwise
     */
    public function mastIgnoreUntrackedFiles(): bool
    {
        return $this->ignoreUntrackedFiles;
    }

    /**
     * Adds a rule for determining whether a commit belongs to a specific section.
     *
     * This method associates a rule with a specific section. The rule is used to evaluate whether a commit should be
     * included in the specified section.
     *
     * @param string               $key  the key of the section
     * @param SectionRuleInterface $rule the rule to be added
     *
     * @return $this this Config instance for method chaining
     */
    public function addSectionRule(string $key, SectionRuleInterface $rule): self
    {
        $this->sectionRules[$key][] = $rule;

        return $this;
    }

    /**
     * Retrieves the rules associated with a specific section.
     *
     * This method returns an array of rules for the specified section. If no custom rules are defined, a default rule
     * is automatically added and returned.
     *
     * @param string $key the key of the section
     *
     * @return SectionRuleInterface[] an array of rules for the specified section
     */
    public function getSectionRules(string $key): array
    {
        $this->sectionRules[$key]['default'] = new DefaultRule($key);

        return $this->sectionRules[$key];
    }

    /**
     * Sets the section to be used for identifying squashed (aggregate) commits.
     *
     * This method configures the section that will be treated as a marker for squashed commits. When a commit is
     * associated with this section, it will be processed as a squashed commit. Squashed commits typically contain
     * a summary of multiple commits and are parsed accordingly to extract individual changes.
     *
     * @param string $aggregateSection The key of the section to be used for identifying squashed commits
     *                                 (e.g., 'aggregate').
     *
     * @return $this this Config instance for method chaining
     */
    public function setAggregateSection(string $aggregateSection): self
    {
        $this->aggregateSection = $aggregateSection;

        return $this;
    }

    /**
     * Retrieves the section configured for identifying squashed (aggregate) commits.
     *
     * This method returns the key of the section that is used to identify squashed commits. If no section has been
     * explicitly configured, an empty string is returned, indicating that no specific section is assigned for
     * identifying squashed commits.
     *
     * @return string the key of the section used for identifying squashed commits, or an empty string if no section
     *                is configured
     */
    public function getAggregateSection(): string
    {
        return $this->aggregateSection;
    }

    /**
     * Sets the commit message template for squashed commits.
     *
     * This method allows you to customize the message used to identify squashed commits in the repository.
     *
     * @param string $squashedCommitMessage the custom message template for squashed commits
     *
     * @return $this this Config instance for method chaining
     */
    public function setSquashedCommitMessage(string $squashedCommitMessage): self
    {
        $this->squashedCommitMessage = $squashedCommitMessage;

        return $this;
    }

    /**
     * Retrieves the commit message template for squashed commits.
     *
     * @return string the message template for squashed commits
     */
    public function getSquashedCommitMessage(): string
    {
        return $this->squashedCommitMessage;
    }

    /**
     * Enables or disables processing of default squashed commits.
     *
     * This method configures whether the tool should process default squashed commits (those matching the default
     * message template).
     *
     * @param bool $processDefaultSquashedCommit whether to enable processing of default squashed commits
     *
     * @return $this this Config instance for method chaining
     */
    public function setProcessDefaultSquashedCommit(bool $processDefaultSquashedCommit): self
    {
        $this->processDefaultSquashedCommit = $processDefaultSquashedCommit;

        return $this;
    }

    /**
     * Checks whether processing of default squashed commits is enabled.
     *
     * @return bool returns `true` if processing of default squashed commits is enabled, `false` otherwise
     */
    public function shouldProcessDefaultSquashedCommit(): bool
    {
        return $this->processDefaultSquashedCommit;
    }

    /**
     * Retrieves the changelog formatter instance.
     *
     * If no custom changelog formatter is set, a default instance of `DefaultFormatter` is created and configured.
     * The formatter's configuration is automatically updated to use the current `Config` instance.
     *
     * @return ChangelogFormatterInterface the changelog formatter instance
     */
    public function getChangelogFormatter(): ChangelogFormatterInterface
    {
        if (null === $this->changelogFormatter) {
            $this->changelogFormatter = new Changelog\DefaultFormatter();
            $this->changelogFormatter->setConfig($this);
        }

        return $this->changelogFormatter;
    }

    /**
     * Sets a custom changelog formatter for the configuration.
     *
     * The provided formatter will be used for all changelog-related operations. The formatter's configuration
     * is automatically updated to use the current `Config` instance.
     *
     * @param ChangelogFormatterInterface $changelogFormatter the custom changelog formatter to set
     *
     * @return $this this Config instance for method chaining
     */
    public function setChangelogFormatter(ChangelogFormatterInterface $changelogFormatter): self
    {
        $this->changelogFormatter = $changelogFormatter;
        $this->changelogFormatter->setConfig($this);

        return $this;
    }

    /**
     * Retrieves the VCS executor instance.
     *
     * If no custom VCS executor is set, a default instance of `GitExecutor` is created and used.
     *
     * @return VcsExecutorInterface the VCS executor instance
     */
    public function getVcsExecutor(): VcsExecutorInterface
    {
        if (null === $this->vcsExecutor) {
            $this->vcsExecutor = new GitExecutor();
        }

        return $this->vcsExecutor;
    }

    /**
     * Sets a custom VCS executor for the configuration.
     *
     * The provided executor will be used for all version control system operations.
     *
     * @param VcsExecutorInterface $vcsExecutor the custom VCS executor to set
     *
     * @return $this this Config instance for method chaining
     */
    public function setVcsExecutor(VcsExecutorInterface $vcsExecutor): self
    {
        $this->vcsExecutor = $vcsExecutor;

        return $this;
    }

    /**
     * Retrieves the commit parser instance.
     *
     * If no custom commit parser is set, a default instance of `ShortParser` is created and configured.
     * The parser's configuration is automatically updated to use the current `Config` instance.
     *
     * @return CommitParserInterface the commit parser instance
     */
    public function getCommitParser(): CommitParserInterface
    {
        if (null === $this->commitParser) {
            $this->commitParser = new Commits\ShortParser();
            $this->commitParser->setConfig($this);
        }

        return $this->commitParser;
    }

    /**
     * Sets a custom commit parser for the configuration.
     *
     * The provided parser will be used for all commit parsing operations. The parser's configuration is automatically
     * updated to use the current `Config` instance.
     *
     * @return $this this Config instance for method chaining
     */
    public function setCommitParser(CommitParserInterface $changelogFormatter): self
    {
        $this->commitParser = $changelogFormatter;
        $this->commitParser->setConfig($this);

        return $this;
    }

    /**
     * Retrieves the tag formatter instance.
     *
     * If no custom tag formatter is set, a default instance of `DefaultFormatter` is created and configured.
     *
     * @return TagFormatterInterface the tag formatter instance
     */
    public function getTagFormatter(): TagFormatterInterface
    {
        if (null === $this->tagFormatter) {
            $this->tagFormatter = new Tag\DefaultFormatter();
            $this->tagFormatter->setConfig($this);
        }

        return $this->tagFormatter;
    }

    /**
     * Sets a custom tag formatter for the configuration.
     *
     * The provided formatter will be used for all tag-related operations. The formatter's configuration
     * is automatically updated to use the current `Config` instance.
     *
     * @param TagFormatterInterface $tagFormatter the custom tag formatter to set
     *
     * @return $this this Config instance for method chaining
     */
    public function setTagFormatter(TagFormatterInterface $tagFormatter): self
    {
        $this->tagFormatter = $tagFormatter;
        $this->tagFormatter->setConfig($this);

        return $this;
    }

    /**
     * Enables or disables version management in the `composer.json` file.
     *
     * When disabled, version management will rely solely on Git tags instead of updating `composer.json`.
     *
     * @param bool $enabledComposerVersioning Whether to enable version management in `composer.json`.
     *                                        - `true`: Enable version management in `composer.json`.
     *                                        - `false`: Disable version management in `composer.json`.
     *
     * @return $this this Config instance for method chaining
     */
    public function setEnabledComposerVersioning(bool $enabledComposerVersioning): self
    {
        $this->enabledComposerVersioning = $enabledComposerVersioning;

        return $this;
    }

    /**
     * Checks whether version management in the `composer.json` file is enabled.
     *
     * @return bool Returns `true` if version management in `composer.json` is enabled, `false` otherwise.
     */
    public function isEnabledComposerVersioning(): bool
    {
        return $this->enabledComposerVersioning;
    }

    /**
     * Sets a custom property in the configuration.
     *
     * This method allows you to store custom key-value pairs in the configuration. These properties can be used to pass
     * additional parameters required by custom implementations (e.g., formatters, VCS executors, parsers, etc.).
     *
     * @param string $key   The name of the property to set. This should be a unique identifier for the property.
     * @param mixed  $value The value to associate with the property. This can be any type of data required by the custom
     *                      implementation.
     *
     * @return $this this Config instance for method chaining
     *
     * @example
     * ```php
     * return (new \Vasoft\VersionIncrement\Config())
     *     ->set('customParam', 'customValue');
     * ```
     */
    public function set(string $key, mixed $value): self
    {
        $this->props[$key] = $value;

        return $this;
    }

    /**
     * Retrieves the value of a custom property from the configuration.
     *
     * This method retrieves the value associated with the specified property key. If the property does not exist,
     * an exception is thrown to indicate that the property is unknown.
     *
     * @param string $key The name of the property to retrieve. This should match the key used when setting the property.
     *
     * @return mixed the value associated with the property
     *
     * @throws UnknownPropertyException if the specified property does not exist in the configuration
     */
    public function get(string $key): mixed
    {
        if (!isset($this->props[$key])) {
            throw new UnknownPropertyException($key);
        }

        return $this->props[$key];
    }

    /**
     * Enables or disables hiding of duplicate entries within the same section in the CHANGELOG.
     *
     * This method configures whether duplicate entries (lines with identical content) should be hidden in the generated
     * CHANGELOG. When enabled, only the first occurrence of a duplicate entry will be displayed within each section.
     *
     * @param bool $hideDoubles Whether to hide duplicate entries:
     *                          - `true`: Hide duplicate entries within the same section.
     *                          - `false`: Display all entries, including duplicates (default behavior).
     *
     * @return $this this Config instance for method chaining
     */
    public function setHideDoubles(bool $hideDoubles): self
    {
        $this->hideDoubles = $hideDoubles;

        return $this;
    }

    /**
     * Checks whether hiding of duplicate entries within the same section is enabled.
     *
     * This method retrieves the current configuration for hiding duplicate entries in the CHANGELOG. If enabled, duplicate
     * entries within the same section will be hidden during the generation of the CHANGELOG.
     *
     * @return bool returns `true` if hiding of duplicate entries is enabled, `false` otherwise
     */
    public function isHideDoubles(): bool
    {
        return $this->hideDoubles;
    }
}
