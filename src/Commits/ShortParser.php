<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\CommitParserInterface;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ConfigNotSetException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;

final class ShortParser implements CommitParserInterface
{
    public const REG_EXP = "/^[\t *-]*((?<key>[a-z]+)(?:\\((?<scope>[^)]+)\\))?(?<breaking>!)?:\\s+(?<message>.+))/";
    private ?Config $config = null;

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @throws ChangesNotFoundException
     * @throws ConfigNotSetException
     * @throws GitCommandException
     */
    public function process(
        ?string $tagsFrom,
        string $tagsTo = '',
    ): CommitCollection {
        if (null === $this->config) {
            throw new ConfigNotSetException();
        }
        $vcs = $this->config->getVcsExecutor();
        $commits = $vcs->getCommitsSinceLastTag($tagsFrom);
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
                if (!$this->parseCommit($commit, $commitCollection, $aggregateKey, $hash)) {
                    $commitCollection->addRawMessage($commit);
                }
            }
        }

        return $commitCollection;
    }

    /**
     * @throws GitCommandException
     */
    private function parseCommit(
        string $line,
        CommitCollection $commitCollection,
        string $aggregateKey,
        string $hash,
    ): bool {
        $matches = [];
        if (preg_match(self::REG_EXP, $line, $matches)) {
            $key = trim($matches['key']);
            if ('' !== $hash && '' !== $aggregateKey && $aggregateKey === $key) {
                $commitCollection->setMajorMarker('!' === $matches['breaking']);
                $this->processAggregated($hash, $commitCollection);
            } else {
                $commitCollection->add(
                    new Commit(
                        $line,
                        $key,
                        $matches['message'],
                        '!' === $matches['breaking'],
                        $matches['scope'],
                        [$matches['breaking']],
                    ),
                );
            }

            return true;
        }

        return false;
    }

    /**
     * @throws GitCommandException
     */
    private function processAggregated(string $hash, CommitCollection $commitCollection): void
    {
        $description = $this->config->getVcsExecutor()->getCommitDescription($hash);
        foreach ($description as $line) {
            $this->parseCommit($line, $commitCollection, '', '');
        }
    }
}
