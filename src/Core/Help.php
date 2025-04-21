<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

use Vasoft\VersionIncrement\Contract\ApplicationHandlerInterface;

class Help implements ApplicationHandlerInterface
{
    public const SECTION_KEYS = 'Keys';
    public const SECTION_TYPES = 'Type';
    public const KEY = '--help';
    /**
     * @var ApplicationHandlerInterface[]
     */
    private array $handlers = [];

    public function handle(array $argv): ?int
    {
        if (!in_array(self::KEY, $argv, true)) {
            return null;
        }
        $this->display();

        return 0;
    }

    private function display(): void
    {
        echo <<<'HELP'
            Vasoft Semantic Version Increment
            run vs-version-increment [keys] [type]

            HELP;
        $this->displaySections();
    }

    private function displaySections(): void
    {
        $registeredRows = $this->getRows();
        foreach ($registeredRows as $sectionName => $rows) {
            echo $sectionName, ':', PHP_EOL;
            $this->displayRows($rows);
        }
    }

    /**
     * @param HelpRow[] $rows
     */
    private function displayRows(array $rows): void
    {
        $maxKeyLength = array_reduce(
            $rows,
            static fn($result, HelpRow $row): int => max($result, strlen($row->key)),
            5,
        );
        $existsKeys = [];
        foreach ($rows as $row) {
            $key = trim($row->key);
            if ('' !== $key) {
                if (isset($existsKeys[$key])) {
                    continue;
                }
                $existsKeys[$key] = true;
            }
            $formattedKey = str_pad($key, $maxKeyLength);
            echo '   ', $formattedKey . str_repeat(' ', 3), $row->description, PHP_EOL;
        }
    }

    private function getRows(): array
    {
        $sections = [];

        foreach ($this->handlers as $handler) {
            $rows = $handler->getHelp();
            foreach ($rows as $row) {
                $sections[$row->section][] = $row;
            }
        }
        $sections[self::SECTION_KEYS][] = new HelpRow(self::SECTION_KEYS, self::KEY, 'Display this help message');

        return $sections;
    }

    /**
     * @param ApplicationHandlerInterface[] $handlers
     */
    public function registerHandlersFromArray(array $handlers): void
    {
        $this->handlers = $handlers;
    }

    public function getHelp(): array
    {
        return [];
    }
}
