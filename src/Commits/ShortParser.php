<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\CommitParserInterface;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;

final class ShortParser implements CommitParserInterface
{
    public const REG_EXP = "/^[\t *-]*((?<key>[a-z]+)(?:\\((?<scope>[^)]+)\\))?(?<breaking>!)?:\\s+(?<message>.+))/";

    public function __construct(private readonly VcsExecutorInterface $vcs) {}

    public function process(
        Config $config,
        ?string $tagsFrom,
        string $tagsTo = '',
    ): CommitCollection {
        $commits = $this->vcs->getCommitsSinceLastTag($tagsFrom);
        if (empty($commits)) {
            throw new ChangesNotFoundException();
        }
        $commitCollection = $config->getCommitCollection();
        $aggregateKey = $config->getAggregateSection();
        $shouldProcessDefaultSquashedCommit = $config->shouldProcessDefaultSquashedCommit();
        $squashedCommitMessage = $config->getSquashedCommitMessage();
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
        $description = $this->vcs->getCommitDescription($hash);
        foreach ($description as $line) {
            $this->parseCommit($line, $commitCollection, '', '');
        }
    }
}
