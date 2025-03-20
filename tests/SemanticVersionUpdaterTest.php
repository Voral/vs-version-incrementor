<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;

/**
 * @coversDefaultClass \Vasoft\VersionIncrement\SemanticVersionUpdater
 *
 * @internal
 */
final class SemanticVersionUpdaterTest extends TestCase
{
    use PHPMock;

    public function testUnknownParamValue(): void
    {
        $this->expectException(IncorrectChangeTypeException::class);
        $this->expectExceptionMessage('Invalid change type: unknown. Available types are: major, minor, patch.');
        $this->expectExceptionCode(70);
        $updater = new SemanticVersionUpdater('', new Config(), 'unknown');
        $updater->updateVersion();
    }

    public function testCommandError(): void
    {
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null): void {
                    $returnCode = 1;
                    $output = ['any error text'];
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $updater = new SemanticVersionUpdater('', new Config());
        $this->expectException(GitCommandException::class);
        $this->expectExceptionMessage('Error executing Git command: git rev-parse --abbrev-ref HEAD');
        $this->expectExceptionCode(60);
        $updater->updateVersion();
    }

    public function testMajorFlag(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '3.0.0';

        $textChangelog = '';
        $textChangelogExpected = '# 3.0.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat!: Some Example',
                        ],
                        default => [],
                    };
                },
            );

        $updater = new SemanticVersionUpdater('', new Config());
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame('git rev-parse --abbrev-ref HEAD 2>&1', $commands[0]);
        self::assertSame('git status --porcelain 2>&1', $commands[1]);
        self::assertSame('git tag --sort=-creatordate 2>&1', $commands[2]);
        self::assertSame('git log v2.2.0..HEAD --pretty=format:%s 2>&1', $commands[3]);
        self::assertSame("git commit -am 'chore(release): v3.0.0' 2>&1", $commands[4]);
        self::assertSame('git tag v3.0.0 2>&1', $commands[5]);
        self::assertSame("Release 3.0.0 successfully created!\n", $output);
    }

    public function testUpdateVersion(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.3.0';

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/test/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/test/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/test/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [

        ];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );

        $updater = new SemanticVersionUpdater('/test', new Config());
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("git commit -am 'chore(release): v2.3.0' 2>&1", $commands[4]);
        self::assertSame('git tag v2.3.0 2>&1', $commands[5]);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    public function testNoChanges(): void
    {
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/CHANGELOG.md' => false,
                        default => true,
                    };
                },
            );

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [

        ];
        $exec
            ->expects(self::exactly(4))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [],
                        default => [],
                    };
                },
            );

        $updater = new SemanticVersionUpdater('', new Config());
        $this->expectException(ChangesNotFoundException::class);
        $this->expectExceptionMessage('Changes not found in repository from previous release');
        $this->expectExceptionCode(40);
        $updater->updateVersion();
    }

    public function testNoTagsYet(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '1.1.0';

        $textChangelog = '';
        $textChangelogExpected = '# 1.1.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git log --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );

        $updater = new SemanticVersionUpdater('', new Config());
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame('git tag --sort=-creatordate 2>&1', $commands[2]);
        self::assertSame('git log --pretty=format:%s 2>&1', $commands[3]);
        self::assertSame("git commit -am 'chore(release): v1.1.0' 2>&1", $commands[4]);
        self::assertSame('git tag v1.1.0 2>&1', $commands[5]);
        self::assertSame("Release 1.1.0 successfully created!\n", $output);
    }

    public function testDefaultSectionAndPatch(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.2.1';

        $textChangelog = '';
        $textChangelogExpected = '# 2.2.1 (' . date('Y-m-d') . ')

### Other
- Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => ['Some Example'],
                        default => [],
                    };
                },
            );

        $updater = new SemanticVersionUpdater('', new Config());
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame('git tag --sort=-creatordate 2>&1', $commands[2]);
        self::assertSame('git log v2.2.0..HEAD --pretty=format:%s 2>&1', $commands[3]);
        self::assertSame("git commit -am 'chore(release): v2.2.1' 2>&1", $commands[4]);
        self::assertSame('git tag v2.2.1 2>&1', $commands[5]);
        self::assertSame("Release 2.2.1 successfully created!\n", $output);
    }

    public function testComposerFileNotFound(): void
    {
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturnCallback(static fn(string $fileName) => '/composer.json' !== $fileName);

        $updater = new SemanticVersionUpdater('', new Config());
        $this->expectException(ComposerException::class);
        $this->expectExceptionMessage('Invalid composer.json file. Please check your composer.json file.');
        $this->expectExceptionCode(10);

        $updater->updateVersion();
    }

    public function testJsonSyntaxError(): void
    {
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => 'wrongJson',
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $updater = new SemanticVersionUpdater('', new Config());
        $this->expectException(ComposerException::class);
        $this->expectExceptionMessage('JSON: Syntax error');
        $this->expectExceptionCode(10);

        $updater->updateVersion();
    }

    public function testOtherMainBranch(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.3.0';

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [

        ];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['main'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );
        $config = new Config();
        $config->setMasterBranch('main');
        $updater = new SemanticVersionUpdater('', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("git commit -am 'chore(release): v2.3.0' 2>&1", $commands[4]);
        self::assertSame('git tag v2.3.0 2>&1', $commands[5]);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    public function testUncommitted(): void
    {
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['main'],
                        'git status --porcelain 2>&1' => ['main'],
                        default => [],
                    };
                },
            );
        $config = new Config();
        $config->setMasterBranch('main');
        $updater = new SemanticVersionUpdater('', $config);
        $this->expectException(UncommittedException::class);
        $this->expectExceptionMessage('There are uncommitted changes in the repository.');
        $this->expectExceptionCode(30);
        $updater->updateVersion();
    }

    public function testUncommittedUntracked(): void
    {
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['main'],
                        'git status --porcelain 2>&1' => [
                            'M  file',
                            '?? untracked file',
                        ],
                        default => [],
                    };
                },
            );
        $config = new Config();
        $config->setMasterBranch('main');
        $config->setIgnoreUntrackedFiles(true);
        $updater = new SemanticVersionUpdater('', $config);
        $this->expectException(UncommittedException::class);
        $this->expectExceptionMessage('There are uncommitted changes in the repository.');
        $this->expectExceptionCode(30);
        $updater->updateVersion();
    }

    public function testUncommittedUntrackedOnly(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.3.0';

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/test/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/test/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/test/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [

        ];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git status --porcelain 2>&1' => ['?? untracked file'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );

        $config = new Config();
        $config->setIgnoreUntrackedFiles(true);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("git commit -am 'chore(release): v2.3.0' 2>&1", $commands[4]);
        self::assertSame('git tag v2.3.0 2>&1', $commands[5]);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    public function testOtherMainBranchError(): void
    {
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturn(
                json_encode(
                    ['version' => '2.2.0', 'name' => 'test'],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                ),
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null): void {
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['main'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );
        $updater = new SemanticVersionUpdater('', new Config());
        $this->expectException(BranchException::class);
        $this->expectExceptionMessage('You are not on the target branch. Current branch is "main", target is "master"');
        $this->expectExceptionCode(20);
        $updater->updateVersion();
    }

    public function testUpdateVersionAutoMajor(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '3.0.0';

        $textChangelog = '';
        $textChangelogExpected = '# 3.0.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/test/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/test/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/test/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/test/CHANGELOG.md' => false,
                        default => true,
                    };
                },
            );

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [];
        $exec
            ->expects(self::exactly(7))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'doc(extremal): Some Example',
                            'feat(extremal): Some Example',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );
        $config = new Config();
        $config->setMajorTypes(['feat', 'doc']);
        $config->setReleaseScope('');
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame('git add CHANGELOG.md 2>&1', $commands[4]);
        self::assertSame("git commit -am 'chore: v3.0.0' 2>&1", $commands[5]);
        self::assertSame('git tag v3.0.0 2>&1', $commands[6]);
        self::assertSame("Release 3.0.0 successfully created!\n", $output);
    }

    public function testUpdateVersionAutoMinor(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.3.0';

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### Documentation
- Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/test/composer.json' === $fileName) {
                        $composerJson = json_decode($contents, true);
                        $versionAfter = $composerJson['version'];
                    } elseif ('/test/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::exactly(2))
            ->willReturnCallback(
                static function (string $fileName) {
                    return match ($fileName) {
                        '/test/composer.json' => json_encode(
                            ['version' => '2.2.0', 'name' => 'test'],
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                        ),
                        default => '',
                    };
                },
            );
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $commands = [];
        $exec
            ->expects(self::exactly(6))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$commands): void {
                    $commands[] = $command;
                    $returnCode = 0;
                    $output = match ($command) {
                        'git rev-parse --abbrev-ref HEAD 2>&1' => ['master'],
                        'git tag --sort=-creatordate 2>&1' => ['v2.2.0', 'v2.1.1', 'v2.1.0', 'v2.0.0', 'v1.0.1'],
                        'git log v2.2.0..HEAD --pretty=format:%s 2>&1' => [
                            'docs(extremal): Some Example',
                        ],
                        default => [],
                    };
                },
            );
        $config = new Config();
        $config->setMinorTypes(['docs']);
        $config->setReleaseScope('rel');
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("git commit -am 'chore(rel): v2.3.0' 2>&1", $commands[4]);
        self::assertSame('git tag v2.3.0 2>&1', $commands[5]);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }
}
