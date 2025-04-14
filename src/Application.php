<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Exceptions\ApplicationException;
use Vasoft\VersionIncrement\Exceptions\InvalidConfigFileException;

class Application
{
    private bool $debug = false;
    private bool $showList = false;
    private bool $modeHelp = false;
    private string $changeType = '';

    public function run(array $argv): int
    {
        $exitCode = 0;

        try {
            $this->checkParams($argv);
            if ($this->modeHelp) {
                $this->displayHelp();

                return 0;
            }

            $composerJsonPath = getenv('COMPOSER') ?: getcwd();
            $configFile = $composerJsonPath . '/.vs-version-increment.php';
            $config = $this->loadConfig($configFile);
            if ($this->showList) {
                fwrite(STDOUT, implode(PHP_EOL, $config->getSectionDescriptions()) . PHP_EOL);

                return 0;
            }
            $versionUpdater = new SemanticVersionUpdater($composerJsonPath, $config, $this->changeType);
            $versionUpdater
                ->setDebug($this->debug)
                ->updateVersion();
        } catch (ApplicationException $e) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            $exitCode = $e->getCode();
        } catch (\Throwable $e) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            $exitCode = ApplicationException::CODE;
        }

        return $exitCode;
    }

    private function checkParams(array $argv): void
    {
        unset($argv[0]);

        $this->showList = false;
        $this->debug = false;
        $this->changeType = '';

        foreach ($argv as $arg) {
            switch ($arg) {
                case '--help':
                    $this->modeHelp = true;

                    return;

                case '--list':
                    $this->showList = true;

                    return;

                case '--debug':
                    $this->debug = true;
                    break;

                default:
                    if (empty($this->changeType)) {
                        $this->changeType = $arg;
                    }
                    break;
            }
        }
    }

    /**
     * @throws InvalidConfigFileException
     */
    private function loadConfig(string $configFile): Config
    {
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!$config instanceof Config) {
                throw  new InvalidConfigFileException();
            }
        } else {
            $config = new Config();
        }

        return $config;
    }

    private function displayHelp(): void
    {
        echo <<<'HELP'
            Vasoft Semantic Version Increment
            run vs-version-increment [--debug] [--list] [--help] [major|minor|patch]
            Usage:
                --debug   Enable debug mode
                --help    Display this help message
                --list    Show list of sections
                major|minor|patch    Increment type

            HELP;
    }
}
