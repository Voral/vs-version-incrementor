<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Application
 */
final class ApplicationTestExtend extends TestCase
{
    use PHPMock;

    private string $wrongConfigPath = '';
    private string $configPath = '';

    protected function setUp(): void
    {
        echo __METHOD__, PHP_EOL;
        parent::setUp();
        $this->wrongConfigPath = __DIR__ . '/fixtures/wrong';
        $this->configPath = __DIR__ . '/fixtures/normal';
        //        if (!file_exists($this->wrongConfigPath)) {
        //            mkdir($this->wrongConfigPath, 0o755, true);
        //        }
        //        if (!file_exists($this->configPath)) {
        //            mkdir($this->configPath, 0o755, true);
        //        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        //        array_map('unlink', glob($this->wrongConfigPath . '/*'));
        //        rmdir($this->wrongConfigPath);
        //        array_map('unlink', glob($this->configPath . '/*'));
        //        rmdir($this->configPath);
    }

    public function testAppExecutedDebug(): void
    {
        $expectedOutput = '# 5.0.1 (2025-04-14)

### Added
- Some Feature

### Changed
- Some Changes

';
        $getenv = $this->getFunctionMock(__NAMESPACE__, 'getenv');
        echo $this->configPath,PHP_EOL;
        $getenv->expects(self::once())->willReturn($this->configPath);

        $app = $this->getMockBuilder(Application::class)
            ->onlyMethods(['terminate'])
            ->getMock();
        ob_start();
        $app->run(['script.php', '--debug']);
        $output = ob_get_clean();
        self::assertSame($expectedOutput, $output);
        echo $output;
    }
}
