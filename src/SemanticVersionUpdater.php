<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Contract\GetExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;

class SemanticVersionUpdater
{
    private bool $debug = false;
    private GetExecutorInterface $gitExecutor;
    private array $availableTypes = [
        'major',
        'minor',
        'patch',
    ];
    private bool $isBreaking = false;

    public function __construct(
        private readonly string $projectPath,
        private readonly Config $config,
        private string $changeType = '',
        ?GetExecutorInterface $gitExecutor = null,
    ) {
        $this->gitExecutor = $gitExecutor ?? new GitExecutor();
    }

    /**
     * @throws IncorrectChangeTypeException
     */
    private function checkChangeType(): void
    {
        if ('' !== $this->changeType && !in_array($this->changeType, $this->availableTypes, true)) {
            throw  new IncorrectChangeTypeException($this->changeType);
        }
    }

    /**
     * @throws ComposerException
     */
    public function getComposerJson(): array
    {
        $composer = $this->projectPath . '/composer.json';
        if (!file_exists($composer)) {
            throw new ComposerException();
        }

        try {
            $result = json_decode(file_get_contents($composer), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ComposerException('JSON: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * @throws BranchException
     * @throws ChangesNotFoundException
     * @throws ComposerException
     * @throws GitCommandException
     * @throws IncorrectChangeTypeException
     * @throws UncommittedException
     */
    public function updateVersion(): void
    {
        $this->checkChangeType();
        $composerJson = $this->getComposerJson();
        $this->checkGitBranch();
        $this->checkUncommittedChanges();

        $lastTag = $this->gitExecutor->getLastTag();
        $commits = $this->gitExecutor->getCommitsSinceLastTag($lastTag);
        $sections = $this->analyzeCommits($commits);
        $this->detectionTypeChange($sections);

        $currentVersion = $composerJson['version'] ?? '1.0.0';

        $newVersion = $this->updateComposerVersion($currentVersion, $this->changeType);

        $composerJson['version'] = $newVersion;
        $this->updateComposerJson($composerJson);
        $date = date('Y-m-d');
        $changelog = $this->generateChangelog($sections, $newVersion, $date);
        $this->updateChangeLog($changelog);
        $this->commitRelease($newVersion);
    }

    private function commitRelease(string $newVersion): void
    {
        if (!$this->debug) {
            $releaseScope = trim($this->config->getReleaseScope());
            if ('' !== $releaseScope) {
                $releaseScope = sprintf('(%s)', $releaseScope);
            }
            $this->gitExecutor->commit(
                sprintf(
                    '%s%s: v%s',
                    $this->config->getReleaseSection(),
                    $releaseScope,
                    $newVersion,
                ),
            );
            $this->gitExecutor->setVersionTag($newVersion);
            echo "Release {$newVersion} successfully created!\n";
        }
    }

    private function updateChangeLog(string $changelog): void
    {
        if ($this->debug) {
            echo $changelog;
        } else {
            $fileChangelog = $this->projectPath . '/CHANGELOG.md';
            if (file_exists($fileChangelog)) {
                $changeLogContent = file_get_contents($fileChangelog);
                $changeLogAddToGit = false;
            } else {
                $changeLogContent = '';
                $changeLogAddToGit = true;
            }
            file_put_contents($fileChangelog, $changelog . $changeLogContent);
            if ($changeLogAddToGit) {
                $this->gitExecutor->addFile('CHANGELOG.md');
            }
        }
    }

    private function updateComposerJson(array $composerJson): void
    {
        if (!$this->debug) {
            file_put_contents(
                $this->projectPath . '/composer.json',
                json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            );
        }
    }

    private function detectionTypeChange(array $sections): void
    {
        if ('' === $this->changeType) {
            if ($this->isBreaking) {
                $this->changeType = 'major';
            } else {
                $this->changeType = 'patch';
                if ($this->hasTypedCommits($sections, $this->config->getMajorTypes())) {
                    $this->changeType = 'major';
                } elseif ($this->hasTypedCommits($sections, $this->config->getMinorTypes())) {
                    $this->changeType = 'minor';
                }
            }
        }
    }

    private function hasTypedCommits(array $sections, array $keys): bool
    {
        foreach ($keys as $key) {
            if (!empty($sections[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws GitCommandException
     * @throws UncommittedException
     */
    private function checkUncommittedChanges(): void
    {
        $out = $this->gitExecutor->status();
        if ($this->config->mastIgnoreUntrackedFiles()) {
            $out = array_filter($out, static fn(string $item): bool => !str_starts_with($item, '??'));
        }

        if (!empty($out)) {
            throw new UncommittedException();
        }
    }

    /**
     * @throws BranchException
     * @throws GitCommandException
     */
    private function checkGitBranch(): void
    {
        $currentBranch = $this->gitExecutor->getCurrentBranch();
        $targetBranch = $this->config->getMasterBranch();
        if ($currentBranch !== $targetBranch) {
            throw new BranchException($currentBranch, $targetBranch);
        }
    }

    /**
     * @throws ChangesNotFoundException
     */
    private function analyzeCommits(array $commits): array
    {
        if (empty($commits)) {
            throw new ChangesNotFoundException();
        }
        $sections = $this->config->getSectionIndex();
        $aggregateKey = $this->config->getAggregateSection();
        $shouldProcessDefaultSquashedCommit = $this->config->shouldProcessDefaultSquashedCommit();
        $squashedCommitMessage = $this->config->getSquashedCommitMessage();
        $defaultSectionNotHidden = !$this->config->isSectionHidden(Config::DEFAULT_SECTION);
        foreach ($commits as $commit) {
            if (
                $shouldProcessDefaultSquashedCommit
                && str_ends_with($commit, $squashedCommitMessage)
                && preg_match('/^(?<hash>[^ ]+).+/', $commit, $matches)
            ) {
                $this->processAggregated($matches['hash'], $sections);

                continue;
            }
            if (preg_match(
                '/^(?<hash>[^ ]+) (?<key>[a-z]+)(?:\((?<scope>[^\)]+)\))?(?<breaking>!)?:\s+(?<message>.+)/',
                $commit,
                $matches,
            )) {
                $this->analyzeFlags($matches['breaking']);
                $key = trim($matches['key']);
                if ($aggregateKey === $key) {
                    $this->processAggregated($matches['hash'], $sections);
                } else {
                    $rawMessage = false;
                    $key = $this->detectionSection(
                        $sections,
                        $matches['key'],
                        $matches['scope'],
                        [$matches['breaking']],
                        $matches['message'],
                        $rawMessage,
                    );
                    if (!$this->config->isSectionHidden($key)) {
                        $sections[$key][] = $rawMessage
                            ? trim(preg_replace('/^[^ ]+ /', '', $commit))
                            : $matches['message'];
                    }
                }
            } elseif ($defaultSectionNotHidden) {
                $sections[Config::DEFAULT_SECTION][] = trim(preg_replace('/^[^ ]+ /', '', $commit));
            }
        }

        return $sections;
    }

    private function processAggregated(string $hash, array &$sections): void
    {
        $description = $this->gitExecutor->getCommitDescription($hash);
        foreach ($description as $line) {
            $matches = [];
            if (preg_match(
                "/^[\t *-]*((?<key>[a-z]+)(?:\\((?<scope>[^\\)]+)\\))?(?<breaking>!)?:\\s+(?<message>.+))/",
                $line,
                $matches,
            )) {
                $this->analyzeFlags($matches['breaking']);
                $rawMessage = false;
                $key = $this->detectionSection(
                    $sections,
                    $matches['key'],
                    $matches['scope'],
                    [$matches['breaking']],
                    $matches['message'],
                    $rawMessage,
                );
                if (!$this->config->isSectionHidden($key)) {
                    $sections[$key][] = $rawMessage ? trim($line) : $matches['message'];
                }
            }
        }
    }

    private function detectionSection(
        array $sections,
        string $key,
        string $scope,
        array $flags,
        string $message,
        bool &$rawMessage,
    ): string {
        foreach ($sections as $index => $values) {
            $rules = $this->config->getSectionRules($index);
            foreach ($rules as $rule) {
                if ($rule($key, $scope, $flags, $message)) {
                    return $index;
                }
            }
        }
        $rawMessage = true;

        return Config::DEFAULT_SECTION;
    }

    private function analyzeFlags(string $flags): void
    {
        if ('!' === $flags) {
            $this->isBreaking = true;
        }
    }

    private function generateChangelog(array $sections, string $version, string $date): string
    {
        $changelog = "# {$version} ({$date})\n\n";

        foreach ($sections as $key => $messages) {
            if (!empty($messages)) {
                $changelog .= sprintf("### %s\n", $this->config->getSectionTitle($key));
                foreach ($messages as $message) {
                    $changelog .= "- {$message}\n";
                }
                $changelog .= "\n";
            }
        }

        return $changelog;
    }

    private function updateComposerVersion(string $currentVersion, string $changeType): string
    {
        [$major, $minor, $patch] = explode('.', $currentVersion);

        switch ($changeType) {
            case 'major':
                $major++;
                $minor = 0;
                $patch = 0;
                break;
            case 'minor':
                $minor++;
                $patch = 0;
                break;
            case 'patch':
            default:
                $patch++;
                break;
        }

        return "{$major}.{$minor}.{$patch}";
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }
}
