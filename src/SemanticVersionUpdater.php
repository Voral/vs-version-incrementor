<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Commits\CommitCollection;
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
        $commitCollection = $this->analyzeCommits($commits);
        $this->detectionTypeChange($commitCollection);

        $currentVersion = $composerJson['version'] ?? '1.0.0';

        $newVersion = $this->updateComposerVersion($currentVersion, $this->changeType);

        $composerJson['version'] = $newVersion;
        $this->updateComposerJson($composerJson);
        $date = date('Y-m-d');
        $changelog = $this->generateChangelog($commitCollection, $newVersion, $date);
        $this->updateChangeLog($changelog);
        $this->commitRelease($newVersion);
    }

    /**
     * @throws GitCommandException
     */
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

    /**
     * @throws GitCommandException
     */
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

    private function detectionTypeChange(CommitCollection $commitCollection): void
    {
        if ('' === $this->changeType) {
            if ($commitCollection->hasMajorMarker()) {
                $this->changeType = 'major';
            } elseif ($commitCollection->hasMinorMarker()) {
                $this->changeType = 'minor';
            } else {
                $this->changeType = 'patch';
            }
        }
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
     * @throws GitCommandException
     */
    private function analyzeCommits(array $commits): CommitCollection
    {
        if (empty($commits)) {
            throw new ChangesNotFoundException();
        }
        $commitCollection = $this->config->getCommitCollection();
        $aggregateKey = $this->config->getAggregateSection();
        $shouldProcessDefaultSquashedCommit = $this->config->shouldProcessDefaultSquashedCommit();
        $squashedCommitMessage = $this->config->getSquashedCommitMessage();
        foreach ($commits as $commit) {
            if (preg_match(
                '/^(?<hash>[^ ]+) (?<commit>.+)/',
                $commit,
                $matches,
            )) {
                $hash = $matches['hash'];
                $commit = $matches['commit'];
                if (
                    $shouldProcessDefaultSquashedCommit
                    && str_ends_with($commit, $squashedCommitMessage)
                ) {
                    $this->processAggregated($hash, $commitCollection);

                    continue;
                }
                if (preg_match(
                    '/^(?<key>[a-z]+)(?:\((?<scope>[^\)]+)\))?(?<breaking>!)?:\s+(?<message>.+)/',
                    $commit,
                    $matches,
                )) {
                    $key = trim($matches['key']);
                    if ($aggregateKey === $key) {
                        $commitCollection->setMajorMarker('!' === $matches['breaking']);
                        $this->processAggregated($hash, $commitCollection);
                    } else {
                        $commitCollection->add(
                            new Commit(
                                $commit,
                                $key,
                                $matches['message'],
                                '!' === $matches['breaking'],
                                $matches['scope'],
                                [$matches['breaking']],
                            ),
                        );
                    }
                } else {
                    $commitCollection->addRawMessage($commit);
                }
            }
        }

        return $commitCollection;
    }

    /**
     * @throws GitCommandException
     */
    private function processAggregated(string $hash, CommitCollection $commitCollection): void
    {
        $description = $this->gitExecutor->getCommitDescription($hash);
        foreach ($description as $line) {
            $matches = [];
            if (preg_match(
                "/^[\t *-]*((?<key>[a-z]+)(?:\\((?<scope>[^\\)]+)\\))?(?<breaking>!)?:\\s+(?<message>.+))/",
                $line,
                $matches,
            )) {
                $commitCollection->add(
                    new Commit(
                        $line,
                        $matches['key'],
                        $matches['message'],
                        '!' === $matches['breaking'],
                        $matches['scope'],
                        [$matches['breaking']],
                    ),
                );
                //                }
            }
        }
    }

    private function generateChangelog(CommitCollection $commitCollection, string $version, string $date): string
    {
        $changelog = "# {$version} ({$date})\n\n";
        $sections = $commitCollection->getVisibleSections();
        foreach ($sections as $section) {
            $changelog .= sprintf("### %s\n", $section->title);
            foreach ($section->getCommits() as $commit) {
                $changelog .= "- {$commit->comment}\n";
            }
            $changelog .= "\n";
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
