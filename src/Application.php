<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use Vasoft\VersionIncrement\Core\Help;
use Vasoft\VersionIncrement\Core\Legend;
use Vasoft\VersionIncrement\Core\UpdateRunner;
use Vasoft\VersionIncrement\Events\Event;
use Vasoft\VersionIncrement\Events\EventType;
use Vasoft\VersionIncrement\Exceptions\ApplicationException;
use Vasoft\VersionIncrement\Exceptions\InvalidConfigFileException;

class Application
{
    public function run(array $argv): int
    {
        array_shift($argv);
        $config = null;

        try {
            $composerJsonPath = getenv('COMPOSER') ?: getcwd();
            $configFile = $composerJsonPath . '/.vs-version-increment.php';
            $config = $this->loadConfig($configFile);
            $helper = new Help();
            $handlers = [
                $helper,
                new Legend($config),
                new UpdateRunner($composerJsonPath, $config),
            ];
            $helper->registerHandlersFromArray($handlers);
            foreach ($handlers as $handler) {
                if (($exitCode = $handler->handle($argv)) !== null) {
                    return $exitCode;
                }
            }

            return 0;
        } catch (ApplicationException $e) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            $config?->getEventBus()->dispatch(new Event(EventType::ON_ERROR));

            return $e->getCode();
        } catch (\Throwable $e) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            $config?->getEventBus()->dispatch(new Event(EventType::ON_ERROR));

            return ApplicationException::DEFAULT_CODE;
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
}
