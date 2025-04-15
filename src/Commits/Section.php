<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\SectionRuleInterface;

final class Section
{
    /** @var Commit[] */
    private array $commits = [];

    /**
     * @param SectionRuleInterface[] $rules
     */
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly bool $hidden,
        public readonly array $rules,
        public readonly bool $isMajorMarker,
        public readonly bool $isMinorMarker,
        public readonly Config $config,
    ) {}

    public function addCommit(Commit $commit): void
    {
        $this->commits[] = $commit;
    }

    /**
     * @return Commit[]
     */
    public function getCommits(): array
    {
        if ($this->config->isHideDoubles()) {
            $result = [];
            $index = [];
            foreach ($this->commits as $commit) {
                if (!isset($index[$commit->comment])) {
                    $result[] = $commit;
                    $index[$commit->comment] = true;
                }
            }

            return $result;
        }

        return $this->commits;
    }

    public function isEmpty(): bool
    {
        return empty($this->commits);
    }
}
