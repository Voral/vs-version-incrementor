<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;

class SemanticVersionUpdater
{
    private bool $debug = false;
    private VcsExecutorInterface $gitExecutor;
    private array $availableTypes = [
        'major',
        'minor',
        'patch',
    ];

    public function __construct(
        private readonly string $projectPath,
        private readonly Config $config,
        private string $changeType = '',
        ?VcsExecutorInterface $gitExecutor = null,
    ) {
        if (null !== $gitExecutor) {
            $config->setVcsExecutor($gitExecutor);
        }
        $this->gitExecutor = $config->getVcsExecutor();
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
        $commitCollection = $this->config->getCommitParser()->process($this->config, $lastTag);
        $this->detectionTypeChange($commitCollection);

        $currentVersion = $composerJson['version'] ?? '1.0.0';

        $newVersion = $this->updateComposerVersion($currentVersion, $this->changeType);

        $composerJson['version'] = $newVersion;
        $this->updateComposerJson($composerJson);
        $changelog = $this->config->getChangelogFormatter()($commitCollection, $newVersion);

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
