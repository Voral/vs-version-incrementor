<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

trait MockTrait
{
    use PHPMock;

    private bool $initialized = false;

    protected static int $mockFileGetContentsCount = 0;
    protected static array $mockFileGetContentsResult = [];
    protected static array $mockFileGetContentsParam = [];
    protected static int $mockIsWritableCount = 0;
    protected static array $mockIsWritableResult = [];
    protected static array $mockIsWritableParam = [];
    protected static int $mockFileExistsCount = 0;
    protected static array $mockFileExistsResult = [];
    protected static array $mockFileExistsParam = [];
    protected static int $mockFilePutContentsCount = 0;
    protected static array $mockFilePutContentsParamPath = [];
    protected static array $mockFilePutContentsParamContent = [];

    protected function initMocks(): void
    {
        if (!$this->initialized) {
            $mockFileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
            $mockFileGetContents->expects(TestCase::any())->willReturnCallback(
                static function (string $path): false|string {
                    self::$mockFileGetContentsParam[] = $path;
                    ++self::$mockFileGetContentsCount;

                    return self::$mockFileGetContentsResult[$path];
                },
            );
            $mockFilePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
            $mockFilePutContents->expects(TestCase::any())->willReturnCallback(
                static function (string $path, string $content): false|int {
                    self::$mockFilePutContentsParamPath[] = $path;
                    self::$mockFilePutContentsParamContent[] = $content;
                    ++self::$mockFilePutContentsCount;

                    return strlen($content);
                },
            );
            $mockIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
            $mockIsWritable->expects(TestCase::any())->willReturnCallback(
                static function (string $path): bool {
                    self::$mockIsWritableParam[] = $path;
                    ++self::$mockIsWritableCount;

                    return self::$mockIsWritableResult[$path];
                },
            );
            $mockFileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
            $mockFileExists->expects(TestCase::any())->willReturnCallback(
                static function (string $path): bool {
                    self::$mockFileExistsParam[] = $path;
                    ++self::$mockFileExistsCount;

                    return self::$mockFileExistsResult[$path];
                },
            );
            $this->initialized = true;
        }
    }

    protected function clearFileGetContents(array $result): void
    {
        self::$mockFileGetContentsCount = 0;
        self::$mockFileGetContentsResult = $result;
        self::$mockFileGetContentsParam = [];
    }

    protected function clearIsWritable(array $result): void
    {
        self::$mockIsWritableCount = 0;
        self::$mockIsWritableResult = $result;
        self::$mockIsWritableParam = [];
    }

    protected function clearFileExists(array $result): void
    {
        self::$mockFileExistsCount = 0;
        self::$mockFileExistsResult = $result;
        self::$mockFileExistsParam = [];
    }

    protected function clearFilePutContents(): void
    {
        self::$mockFilePutContentsCount = 0;
        self::$mockFilePutContentsParamPath = [];
        self::$mockFilePutContentsParamContent = [];
    }
}
