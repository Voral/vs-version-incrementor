<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\ApplicationHandlerInterface;

class Legend implements ApplicationHandlerInterface
{
    public const KEY = '--list';

    public function __construct(private readonly Config $config) {}

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
        echo 'Available sections:' . PHP_EOL;
        $titles = $this->config->getSections()->getTitles();
        echo $this->formatList($titles);
        $scopes = $this->config->getScopes();
        if (!empty($scopes)) {
            echo PHP_EOL . 'Available scopes:' . PHP_EOL;
            echo $this->formatList($scopes);
        }
    }

    private function formatList(array $values): string
    {
        $output = '';
        foreach ($values as $key => $title) {
            $output .= '    ' . $key . ' - ' . $title . PHP_EOL;
        }

        return $output;
    }

    public function getHelp(): array
    {
        return [
            new HelpRow(Help::SECTION_KEYS, self::KEY, 'Show list of sections'),
        ];
    }
}
