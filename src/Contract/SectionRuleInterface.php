<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Commits\Commit;

/**
 * Interface SectionRuleInterface.
 *
 * Defines the contract for rules that determine whether a commit belongs to a specific section.
 * Implementations of this interface are responsible for evaluating a commit and deciding if it matches
 * the criteria for inclusion in a particular section.
 */
interface SectionRuleInterface
{
    /**
     * Evaluates whether a given commit matches the rule's criteria.
     *
     * This method is invoked to check if the provided commit should be included in a specific section
     * based on the rule's logic. The implementation should define the conditions for matching.
     *
     * @param Commit $commit the commit object to be evaluated
     *
     * @return bool returns `true` if the commit matches the rule's criteria, `false` otherwise
     */
    public function __invoke(Commit $commit): bool;
}
