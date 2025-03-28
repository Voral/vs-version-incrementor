<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

interface SectionRuleInterface
{
    /**
     * @param string $type    Commit type
     * @param string $scope   Commit scope
     * @param array  $flags   Commit flags. Example: "!"
     * @param string $comment Commit comment
     */
    public function __invoke(string $type, string $scope, array $flags, string $comment): bool;
}
