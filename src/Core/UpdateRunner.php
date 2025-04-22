<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\SemanticVersionUpdater;
use Vasoft\VersionIncrement\Contract\ApplicationHandlerInterface;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ChangelogException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;

class UpdateRunner implements ApplicationHandlerInterface
{
    public const KEY_DEBUG = '--debug';
    public const KEY_NO_COMMIT = '--no-commit';
    private bool $debug = false;
    private bool $doCommit = true;

    public function __construct(
        private readonly string $composerJsonPath,
        private readonly Config $config,
    ) {}

    /**
     * @throws BranchException
     * @throws ChangelogException
     * @throws ComposerException
     * @throws GitCommandException
     * @throws IncorrectChangeTypeException
     * @throws UncommittedException
     */
    public function handle(array $argv): ?int
    {
        $changeType = $this->checkParams($argv);
        (new SemanticVersionUpdater($this->composerJsonPath, $this->config, $changeType, $this->doCommit))
            ->setDebug($this->debug)
            ->updateVersion();

        return null;
    }

    private function checkParams(array $argv): string
    {
        $result = '';

        foreach ($argv as $arg) {
            switch ($arg) {
                case self::KEY_DEBUG:
                    $this->debug = true;
                    break;
                case self::KEY_NO_COMMIT:
                    $this->doCommit = false;
                    break;

                default:
                    if (empty($this->changeType)) {
                        $result = $arg;
                    }
                    break;
            }
        }

        return $result;
    }

    public function getHelp(): array
    {
        return [
            new HelpRow(Help::SECTION_KEYS, self::KEY_DEBUG, 'Enable debug mode'),
            new HelpRow(
                Help::SECTION_KEYS,
                self::KEY_NO_COMMIT,
                'Execute all file updates (e.g., CHANGELOG.md, composer.json) but skip creating the final Git commit and version tag.',
            ),
            new HelpRow(
                Help::SECTION_TYPES,
                implode('|', SemanticVersionUpdater::$availableTypes),
                'Updates version according to the passed type',
            ),
        ];
    }
}
