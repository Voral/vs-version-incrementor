<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\Events\Event;
use Vasoft\VersionIncrement\Events\EventType;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ChangelogException;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\ConfigNotSetException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;

class SemanticVersionUpdater
{
    public const LAST_VERSION_TAG = 'last_version';
    public const COMMIT_LIST = 'commit_list';
    public const DEFAULT_VERSION = '1.0.0';
    private bool $debug = false;
    private VcsExecutorInterface $gitExecutor;
    public static array $availableTypes = [
        'major',
        'minor',
        'patch',
    ];
    private ?string $lastTag = null;
    private CommitCollection $commitCollection;

    public function __construct(
        private readonly string $projectPath,
        private readonly Config $config,
        private string $changeType = '',
        private readonly bool $doCommit = true,
    ) {
        $this->gitExecutor = $config->getVcsExecutor();
    }

    /**
     * @throws IncorrectChangeTypeException
     */
    private function checkChangeType(): void
    {
        if ('' !== $this->changeType && !in_array($this->changeType, self::$availableTypes, true)) {
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
        if (!is_writable($composer)) {
            throw new ComposerException('Composer file is not writable.');
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
     * @throws ChangelogException
     * @throws ComposerException
     * @throws ChangesNotFoundException
     * @throws ConfigNotSetException
     * @throws GitCommandException
     * @throws IncorrectChangeTypeException
     * @throws UncommittedException
     */
    public function updateVersion(): void
    {
        $this->checkChangeType();
        $this->checkGitBranch();
        $this->checkUncommittedChanges();

        $this->lastTag = $this->gitExecutor->getLastTag();
        if ($this->config->isEnabledComposerVersioning()) {
            $composerJson = $this->getComposerJson();
            $currentVersion = $composerJson['version'] ?? self::DEFAULT_VERSION;
        } elseif (null === $this->lastTag) {
            $currentVersion = self::DEFAULT_VERSION;
        } else {
            $currentVersion = $this->config->getTagFormatter()->extractVersion($this->lastTag);
        }

        $this->commitCollection = $this->config->getCommitParser()->process($this->lastTag);
        $this->detectionTypeChange($this->commitCollection);

        $newVersion = $this->incrementVersion($currentVersion, $this->changeType);

        if ($this->config->isEnabledComposerVersioning()) {
            $composerJson['version'] = $newVersion;
            $this->updateComposerJson($composerJson);
        }
        $changelog = $this->config->getChangelogFormatter()($this->commitCollection, $newVersion);

        $this->updateChangeLog($changelog);
        $this->commitRelease($newVersion);
    }

    /**
     * @throws GitCommandException
     */
    private function commitRelease(string $newVersion): void
    {
        if (!$this->debug) {
            $event = new Event(EventType::BEFORE_VERSION_SET, $newVersion);
            $event->setData(self::LAST_VERSION_TAG, $this->lastTag);
            $event->setData(self::COMMIT_LIST, $this->commitCollection);
            $this->config->getEventBus()->dispatch($event);
            if ($this->doCommit) {
                $this->processWithCommit($newVersion);
            } else {
                $this->processWithOutCommit($newVersion);
            }
            $event = new Event(EventType::AFTER_VERSION_SET, $newVersion);
            $event->setData(self::LAST_VERSION_TAG, $this->lastTag);
            $event->setData(self::COMMIT_LIST, $this->commitCollection);
            $this->config->getEventBus()->dispatch($event);
        }
    }

    /**
     * @throws GitCommandException
     */
    private function processWithCommit(string $newVersion): void
    {
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

    private function processWithOutCommit(string $newVersion): void
    {
        echo "Version {$newVersion} is ready for release.\n";
        echo "To complete the process, commit your changes and add a Git tag:\n";
        echo "    git commit -m \"chore(release): v{$newVersion}\"\n";
        echo "    git tag v{$newVersion}\n";
    }

    /**
     * @throws ChangelogException
     * @throws GitCommandException
     */
    private function updateChangeLog(string $changelog): void
    {
        if ($this->debug) {
            echo $changelog;
        } else {
            $fileChangelog = $this->projectPath . '/CHANGELOG.md';
            if (file_exists($fileChangelog)) {
                if (!is_writable($fileChangelog)) {
                    throw new ChangelogException();
                }

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
            if (!$this->debug) {
                throw new UncommittedException();
            }
            echo PHP_EOL, 'WARNING: there are uncommitted changes.', PHP_EOL, PHP_EOL;
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

    private function incrementVersion(string $currentVersion, string $changeType): string
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
