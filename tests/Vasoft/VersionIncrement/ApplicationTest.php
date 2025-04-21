<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Application
 */
final class ApplicationTest extends TestCase
{
    use PHPMock;

    private string $wrongConfigPath = '';
    private string $configPath = '';
    private string $configWithScopes = '';

    private ?MockObject $mockGetEnv = null;
    public static int $mockGetEnvCount = 0;
    public static false|string $mockGetEnvResult = false;
    public static string $mockGetEnvVariableName = '';
    public static bool $mockGetEnvThrowException = false;
    public static int $mockGetCwdCount = 0;
    public static int $mockFileExistsCount = 0;
    public static bool $mockFileExistsResult = false;
    public static int $mockFWriteCount = 0;
    public static string $mockFWriteOutput = '';
    public static string $mockIsWritableFileName = '';
    public static bool $mockIsWritableResult = false;
    public static int $mockIsWritableCount = 0;
    public static string $mockFilePutContentsFileName = '';
    public static string $mockFilePutContentsContents = '';
    public static int $mockFilePutContentsCount = 0;
    public static string $mockFileGetContentsFileName = '';
    public static int $mockFileGetContentsCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wrongConfigPath = __DIR__ . '/Fixtures/Wrong';
        $this->configPath = __DIR__ . '/Fixtures/Normal';
        $this->configWithScopes = __DIR__ . '/Fixtures/Scopes';
        if (null === $this->mockGetEnv) {
            $this->mockGetEnv = $this->getFunctionMock(__NAMESPACE__, 'getenv');
            $this->mockGetEnv->expects(TestCase::any())->willReturnCallback(
                static function (string $variableName): false|string {
                    self::$mockGetEnvVariableName = $variableName;
                    ++self::$mockGetEnvCount;

                    if (self::$mockGetEnvThrowException) {
                        throw new \RuntimeException('Test Exception');
                    }

                    return self::$mockGetEnvResult;
                },
            );
            $mockGetcwd = $this->getFunctionMock(__NAMESPACE__, 'getcwd');
            $mockGetcwd->expects(self::any())->willReturnCallback(
                static function (): string {
                    ++self::$mockGetCwdCount;

                    return '/tmp/example1';
                },
            );
            $mockFileExits = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
            $mockFileExits->expects(self::any())->willReturnCallback(
                static function (): bool {
                    ++self::$mockFileExistsCount;

                    return self::$mockFileExistsResult;
                },
            );
            $mockFWrite = $this->getFunctionMock(__NAMESPACE__, 'fwrite');
            $mockFWrite->expects(self::any())->willReturnCallback(
                static function ($stream, $string): int {
                    self::$mockFWriteOutput = $string;
                    ++self::$mockFWriteCount;

                    return strlen($string);
                },
            );

            $mockIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
            $mockIsWritable->expects(self::any())->willReturnCallback(
                static function ($fileName): bool {
                    self::$mockIsWritableFileName = $fileName;
                    ++self::$mockIsWritableCount;

                    return self::$mockIsWritableResult;
                },
            );

            $mockFilePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
            $mockFilePutContents
                ->expects(self::any())
                ->willReturnCallback(
                    static function (string $fileName, string $contents): void {
                        self::$mockFilePutContentsFileName = $fileName;
                        self::$mockFilePutContentsContents = $contents;
                        ++self::$mockFilePutContentsCount;
                    },
                );
            $mockFileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
            $mockFileGetContents
                ->expects(self::any())
                ->willReturnCallback(
                    static function (string $fileName): string {
                        self::$mockFileGetContentsFileName = $fileName;
                        ++self::$mockFileGetContentsCount;

                        return '';
                    },
                );
        }
    }

    public function testAppListPathFromEnvironment(): void
    {
        $expectedVariableName = 'COMPOSER';
        $expectedOutput = <<<'TEXT'
            Available sections:
                feat - New features
                fix - Fixes
                chore - Other changes
                docs - Documentation
                style - Styling
                refactor - Refactoring
                test - Tests
                perf - Performance
                ci - Configure CI
                build - Change build system
                other - Other

            TEXT;
        $this->resetGetenv('/tmp/example', false);
        $this->resetGetcwd();
        $this->resetFileExists(false);
        $this->resetFWrite();

        ob_start();
        $exitCode = (new Application())->run(['script.php', '--list']);
        $output = ob_get_clean();
        self::assertSame(1, self::$mockGetEnvCount, 'Getenv should be called once');
        self::assertSame($expectedVariableName, self::$mockGetEnvVariableName, 'Wrong variable name');
        self::assertSame(0, self::$mockGetCwdCount, 'Getcwd should not be called');
        self::assertSame(1, self::$mockFileExistsCount, 'File exists should be called once');

        self::assertSame($expectedOutput, $output, 'Wrong output');
        self::assertSame(0, $exitCode, 'Wrong exit code');
    }

    public function testAppListPathFromCwd(): void
    {
        $expectedVariableName = 'COMPOSER';
        $expectedOutput = <<<'TEXT'
            Available sections:
                feat - New features
                fix - Fixes
                chore - Other changes
                docs - Documentation
                style - Styling
                refactor - Refactoring
                test - Tests
                perf - Performance
                ci - Configure CI
                build - Change build system
                other - Other

            TEXT;
        $this->resetGetenv(false, false);
        $this->resetGetcwd();
        $this->resetFileExists(false);
        $this->resetFWrite();

        ob_start();
        $exitCode = (new Application())->run(['script.php', '--list']);
        $output = ob_get_clean();
        self::assertSame(1, self::$mockGetEnvCount, 'Getenv should be called once');
        self::assertSame($expectedVariableName, self::$mockGetEnvVariableName, 'Wrong variable name');
        self::assertSame(1, self::$mockGetCwdCount, 'Getcwd should be called once');

        self::assertSame($expectedOutput, $output, 'Wrong output');
        self::assertSame(0, $exitCode, 'Wrong exit code');
    }

    public function testAppException(): void
    {
        $expectedOutput = 'Error: Test Exception' . PHP_EOL;
        $this->resetGetenv(false, true);
        $this->resetGetcwd();
        $this->resetFWrite();

        $exitCode = (new Application())->run(['script.php', '--list']);
        self::assertSame(1, self::$mockGetEnvCount, 'Getenv should be called once');
        self::assertSame(0, self::$mockGetCwdCount, 'Getcwd should not be called');

        self::assertSame($expectedOutput, self::$mockFWriteOutput, 'Wrong output');
        self::assertSame(500, $exitCode, 'Wrong exit code');
    }

    /**
     * Config file exists, but wrong content.
     */
    public function testAppWrongConfigFile(): void
    {
        $expectedOutput = 'Error: Invalid configuration file.' . PHP_EOL;

        $this->resetGetenv($this->wrongConfigPath, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        $exitCode = (new Application())->run(['script.php', '--list']);
        self::assertSame($expectedOutput, self::$mockFWriteOutput, 'Wrong output');
        self::assertSame(50, $exitCode, 'Wrong exit code');
    }

    public function testAppListFromConfig(): void
    {
        $expectedOutput = <<<'TEXT'
            Available sections:
                add - Added
                upd - Changed
                other - Other

            TEXT;
        $this->resetGetenv($this->configPath, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        ob_start();
        $exitCode = (new Application())->run(['script.php', '--list']);
        $output = ob_get_clean();
        self::assertSame($expectedOutput, $output, 'Wrong output');
        self::assertSame(0, $exitCode, 'Wrong exit code');
    }

    public function testAppListFromConfigWidthScopes(): void
    {
        $expectedOutput = <<<'TEXT'
            Available sections:
                add - Added
                upd - Changed
                other - Other

            Available scopes:
                api - API
                front - Frontend

            TEXT;
        $this->resetGetenv($this->configWithScopes, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        ob_start();
        $exitCode = (new Application())->run(['script.php', '--list']);
        $output = ob_get_clean();

        self::assertSame($expectedOutput, $output, 'Wrong output');
        self::assertSame(0, $exitCode, 'Wrong exit code');
    }

    public function testAppHelp(): void
    {
        $expectedOutput = 'Vasoft Semantic Version Increment
run vs-version-increment [keys] [type]
Keys:
   --list    Show list of sections
   --debug   Enable debug mode
   --help    Display this help message
Type:
   major|minor|patch   Updates version according to the passed type
';
        $this->resetGetenv($this->configPath, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        ob_start();
        $exitCode = (new Application())->run(['script.php', '--help']);
        $output = ob_get_clean();

        self::assertSame(0, $exitCode, 'Wrong exit code');
        self::assertSame($expectedOutput, $output);
    }

    public function testAppExecutedDebug(): void
    {
        $expectedOutput = '# 5.0.1 (' . date('Y-m-d') . ')

### Added
- Some Feature

### Changed
- Some Changes

';
        $this->resetGetenv($this->configPath, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        ob_start();
        $exitCode = (new Application())->run(['script.php', '--debug']);
        $output = ob_get_clean();
        self::assertSame(0, $exitCode, 'Wrong exit code');
        self::assertSame($expectedOutput, $output);
        self::assertSame('', self::$mockFWriteOutput, 'Wrong output');
    }

    public function testAppExecutedNormal(): void
    {
        $expectedOutput = '# 5.0.1 (' . date('Y-m-d') . ')

### Added
- Some Feature

### Changed
- Some Changes

';
        $this->resetGetenv($this->configPath, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        $this->resetIsWritable(true);
        $this->resetFilePutContents();
        $this->resetFileGetContents();
        ob_start();
        $exitCode = (new Application())->run(['script.php', '']);
        ob_get_clean();
        self::assertSame('', self::$mockFWriteOutput, 'Wrong output');
        self::assertSame(0, $exitCode, 'Wrong exit code');
        self::assertSame($expectedOutput, self::$mockFilePutContentsContents, 'Wrong CHANGELOG output');
    }

    public function testAppExecutedMajor(): void
    {
        $expectedOutput = '# 6.0.0 (' . date('Y-m-d') . ')

### Added
- Some Feature

### Changed
- Some Changes

';
        $this->resetGetenv($this->configPath, false);
        $this->resetGetcwd();
        $this->resetFileExists(true);
        $this->resetFWrite();
        $this->resetIsWritable(true);
        $this->resetFilePutContents();
        $this->resetFileGetContents();
        ob_start();
        $exitCode = (new Application())->run(['script.php', 'major']);
        ob_get_clean();
        self::assertSame('', self::$mockFWriteOutput, 'Wrong output');
        self::assertSame(0, $exitCode, 'Wrong exit code');
        self::assertSame($expectedOutput, self::$mockFilePutContentsContents, 'Wrong CHANGELOG output');
    }

    public function resetGetenv(false|string $mockGetEnvResult, bool $mockGetEnvThrowException): void
    {
        self::$mockGetEnvCount = 0;
        self::$mockGetEnvResult = $mockGetEnvResult;
        self::$mockGetEnvVariableName = '';
        self::$mockGetEnvThrowException = $mockGetEnvThrowException;
    }

    public function resetGetcwd(): void
    {
        self::$mockGetCwdCount = 0;
    }

    public function resetFileExists(bool $mockFileExistsResult): void
    {
        self::$mockFileExistsCount = 0;
        self::$mockFileExistsResult = $mockFileExistsResult;
    }

    public function resetFWrite(): void
    {
        self::$mockFWriteCount = 0;
        self::$mockFWriteOutput = '';
    }

    public function resetIsWritable(bool $mockIsWritableResult): void
    {
        self::$mockIsWritableCount = 0;
        self::$mockIsWritableFileName = '';
        self::$mockIsWritableResult = $mockIsWritableResult;
    }

    public function resetFilePutContents(): void
    {
        self::$mockFilePutContentsCount = 0;
        self::$mockFilePutContentsFileName = '';
        self::$mockFilePutContentsContents = '';
    }

    public function resetFileGetContents(): void
    {
        self::$mockFileGetContentsCount = 0;
        self::$mockFileGetContentsFileName = '';
    }
}
