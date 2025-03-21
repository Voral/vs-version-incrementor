<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Contract\GetExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;
use Vasoft\VersionIncrement\SectionRules\SectionRuleInterface;

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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'doc(extremal): Some Example',
            'feat(extremal): Some Example',
            'feat!: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $updater = new SemanticVersionUpdater('', new Config(), gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'doc(extremal): Some Example',
            'feat(extremal): Some Example',
            'feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $updater = new SemanticVersionUpdater('/test', new Config(), gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $updater = new SemanticVersionUpdater('', new Config(), gitExecutor: $gitExecutor);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'doc(extremal): Some Example',
            'feat(extremal): Some Example',
            'feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $updater = new SemanticVersionUpdater('', new Config(), gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $updater = new SemanticVersionUpdater('', new Config(), gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'doc(extremal): Some Example',
            'feat(extremal): Some Example',
            'feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setMasterBranch('main');
        $updater = new SemanticVersionUpdater('', $config, gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::once())->method('status')->willReturn([
            'M  file',
            '?? untracked file',
        ]);
        $gitExecutor->expects(self::never())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setMasterBranch('main');
        $updater = new SemanticVersionUpdater('', $config, gitExecutor: $gitExecutor);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::once())->method('status')->willReturn([
            'M  file',
            '?? untracked file',
        ]);
        $gitExecutor->expects(self::never())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setMasterBranch('main');
        $config->setIgnoreUntrackedFiles(true);
        $updater = new SemanticVersionUpdater('', $config, gitExecutor: $gitExecutor);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'doc(extremal): Some Example',
            'feat(extremal): Some Example',
            'feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('status')->willReturn(['?? untracked file']);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setIgnoreUntrackedFiles(true);
        $updater = new SemanticVersionUpdater('/test', $config, gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::never())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('status')->willReturn([]);
        $gitExecutor->expects(self::never())->method('addFile');
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');

        $updater = new SemanticVersionUpdater('', new Config(), gitExecutor: $gitExecutor);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'doc(extremal): Some Example',
            'feat(extremal): Some Example',
            'feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('addFile');
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');

        $config = new Config();
        $config->setMajorTypes(['feat', 'doc']);
        $config->setReleaseScope('');
        $updater = new SemanticVersionUpdater('/test', $config, gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
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

        $gitExecutor = self::createMock(GetExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn(
            ['docs(extremal): Some Example'],
        );
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::never())->method('addFile');
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');

        $config = new Config();
        $config->setMinorTypes(['docs']);
        $config->setReleaseScope('rel');
        $updater = new SemanticVersionUpdater('/test', $config, gitExecutor: $gitExecutor);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    public function testSectionRulesPriority(): void
    {
        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Added Example
- Added Feature
- Some Example

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
                            'docs(extremal): Some Example',
                            'docs(extremal): Added Example',
                            'add: Added Feature',
                            'feat: Some Example',
                        ],
                        default => [],
                    };
                },
            );
        $config = new Config();
        $config->addSectionRule('feat', new ExampleRule1());
        $config->addSectionRule('feat', new ExampleRule2());
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        ob_get_clean();
        self::assertSame($textChangelogExpected, $textChangelog);
    }
}

class ExampleRule1 implements SectionRuleInterface
{
    public function __invoke(string $type, string $scope, array $flags, string $comment): bool
    {
        return 'add' === $type;
    }
}

class ExampleRule2 implements SectionRuleInterface
{
    public function __invoke(string $type, string $scope, array $flags, string $comment): bool
    {
        return str_starts_with(strtolower($comment), 'added');
    }
}
