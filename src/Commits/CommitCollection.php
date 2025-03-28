<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

final class CommitCollection
{
    private bool $majorMarker = false;
    private bool $minorMarker = false;

    /**
     * @param array<string,Section> $sections
     */
    public function __construct(
        private readonly array $sections,
        private readonly Section $defaultSection,
    ) {}

    /**
     * Adds a commit to the collection.
     *
     * If the commit type exists in the collection, it is added to the corresponding section.
     * Otherwise, it is added to the default section.
     */
    public function add(Commit $commit): void
    {
        $this->detectMarkers($commit);
        $rawMessage = false;
        $detectedKey = $this->detectionSection($commit, $rawMessage);
        if ($rawMessage) {
            $this->addRawMessage($commit->rawMessage);

            return;
        }
        if ($detectedKey !== $commit->type) {
            $commit = $commit->withType($detectedKey);
            $this->detectMarkers($commit);
        }
        $section = $this->sections[$commit->type] ?? $this->defaultSection;
        if (!$section->hidden) {
            $section->addCommit($commit);
        }
    }

    private function detectMarkers(Commit $commit): void
    {

        if ($commit->breakingChange) {
            $this->majorMarker = true;

            return;
        }
        $section = $this->sections[$commit->type] ?? null;
        if ($section) {
            if ($section->isMajorMarker) {
                $this->majorMarker = true;
            } elseif ($section->isMinorMarker) {
                $this->minorMarker = true;
            }
        }
    }

    /**
     * Adds a commit to the default section.
     *
     * This method performs the following steps:
     * 1. Checks if the default section is not hidden.
     * 2. Creates a new Commit object with the processed message and adds it to the default section.
     */
    public function addRawMessage(string $message): void
    {
        if (!$this->defaultSection->hidden) {
            $this->defaultSection->addCommit(
                new Commit(
                    $message,
                    $this->defaultSection->type,
                    trim($message),
                    false,
                ),
            );
        }
    }

    /**
     * Returns non-empty sections of the commit collection.
     *
     * @return array<string, Section>
     */
    public function getVisibleSections(): array
    {
        return array_filter(
            $this->sections,
            static fn(Section $section): bool => !$section->isEmpty() && !$section->hidden,
        );
    }

    private function detectionSection(
        Commit $commit,
        bool &$rawMessage,
    ): string {
        foreach ($this->sections as $index => $section) {
            foreach ($section->rules as $rule) {
                if ($rule($commit->type, $commit->scope, $commit->flags, $commit->comment)) {
                    return $index;
                }
            }
        }
        $rawMessage = true;

        return $this->defaultSection->type;
    }

    public function hasMajorMarker(): bool
    {

        return $this->majorMarker;
    }

    public function hasMinorMarker(): bool
    {
        return $this->minorMarker;
    }

    public function setMajorMarker(bool $majorMarker): void
    {
        if ($majorMarker) {
            $this->majorMarker = true;
        }
    }
}
