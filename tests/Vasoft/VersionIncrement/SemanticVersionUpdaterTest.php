<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Changelog\ScopePreservingFormatter;
use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Contract\SectionRuleInterface;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\Exceptions\BranchException;
use Vasoft\VersionIncrement\Exceptions\ChangelogException;
use Vasoft\VersionIncrement\Exceptions\ChangesNotFoundException;
use Vasoft\VersionIncrement\Exceptions\ComposerException;
use Vasoft\VersionIncrement\Exceptions\IncorrectChangeTypeException;
use Vasoft\VersionIncrement\Exceptions\UncommittedException;
use Vasoft\VersionIncrement\SectionRules\BreakingRule;

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g11 doc(extremal): Some Example',
            'c3d4e5f6g12 feat(extremal): Some Example',
            'c3d4e5f6g13 feat!: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 3.0.0 successfully created!\n", $output);
    }

    public function testMajorFlagWidthBreakingSection(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '3.0.0';

        $textChangelog = '';
        $textChangelogExpected = '# 3.0.0 (' . date('Y-m-d') . ')

### BREAKING CHANGES
- Some Example in doc
- Some Example

### New features
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g11 doc(extremal)!: Some Example in doc',
            'c3d4e5f6g12 feat(extremal): Some Example',
            'c3d4e5f6g13 feat!: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setSection('breaking', 'BREAKING CHANGES', 0);
        $config->addSectionRule('breaking', new BreakingRule());
        $config->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('', $config);

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example',
            'c3d4e5f6g3 feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(1))
            ->willReturn(true);
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

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example',
            'c3d4e5f6g3 feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame($versionAfterExpected, $versionAfter);
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 2.2.1 successfully created!\n", $output);
    }

    public function testComposerFileNotFound(): void
    {
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::never());
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::once())
            ->willReturnCallback(static fn(string $fileName) => '/composer.json' !== $fileName);
        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('addFile');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('setVersionTag');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('', $config);
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::once())
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $fileExists
            ->expects(self::once())
            ->willReturnCallback(static fn(string $fileName) => '/composer.json' !== $fileName);
        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('addFile');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('setVersionTag');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('', $config);
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example',
            'c3d4e5f6g3 feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setMasterBranch('main');
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
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
        $fileGetContents->expects(self::never());
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable->expects(self::never());
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists->expects(self::never());

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::once())->method('status')->willReturn([
            'M  file',
            '?? untracked file',
        ]);
        $gitExecutor->expects(self::never())->method('getLastTag');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag');
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setMasterBranch('main');
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
        $this->expectException(UncommittedException::class);
        $this->expectExceptionMessage('There are uncommitted changes in the repository.');
        $this->expectExceptionCode(30);
        $updater->updateVersion();
    }

    public function testUncommittedUntrackedIgnore(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.3.0';

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
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
        $fileGetContents->expects(self::exactly(2))->willReturn(true);
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable->expects(self::exactly(2))->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists->expects(self::exactly(2))->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::once())->method('status')->willReturn([
            '?? untracked file',
        ]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.1.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g13 feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setMasterBranch('main');
        $config->setIgnoreUntrackedFiles(true);
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);

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
        $fileGetContents->expects(self::never());
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable->expects(self::never());
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists->expects(self::never());

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('main');
        $gitExecutor->expects(self::never())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('status')->willReturn([]);
        $gitExecutor->expects(self::never())->method('addFile');
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('', $config);
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
- dev: Some Example for development

### Other changes
- deprecated: Deprecated notice

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(1))
            ->willReturn(true);
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

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g11 doc(extremal): Some Example',
            'c3d4e5f6g12 feat(extremal): Some Example',
            'c3d4e5f6g13 feat: Some Example',
            'c3d4e5f6g14 feat(dev): Some Example for development',
            'c3d4e5f6g14 chore(deprecated): Deprecated notice',
        ]);
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('addFile');
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');

        $config = new Config();
        $config->setMajorTypes(['feat', 'doc']);
        $config->setChangelogFormatter(new ScopePreservingFormatter(['dev', 'deprecated']));
        $config->setReleaseScope('');
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn(
            ['c3d4e5f6g1 docs(extremal): Some Example'],
        );
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::never())->method('addFile');
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');

        $config = new Config();
        $config->setMinorTypes(['docs']);
        $config->setReleaseScope('rel');
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    /** checked */
    public function testSectionRulesPriority(): void
    {
        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Added Example
- Some Feature
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g11 docs(extremal): Some Example',
            'c3d4e5f6g12 docs(extremal): Added Example',
            'c3d4e5f6g14 add: Some Feature',
            'c3d4e5f6g13 feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->addSectionRule('feat', new ExampleRule1());
        $config->addSectionRule('feat', new ExampleRule2());
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        ob_get_clean();
        self::assertSame($textChangelogExpected, $textChangelog);
    }

    public function testAggregateNotSetType(): void
    {
        $versionAfter = '';
        $versionAfterExpected = '2.3.0';

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example feat1
- Some Example feat2

### Other
- doc(extremal): Some Example documents
- aggregate: Some Example 1
- aggregate: Some Example 2

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example documents',
            'c3d4e5f6g4 aggregate: Some Example 1',
            'c3d4e5f6g2 feat(extremal): Some Example feat1',
            'c3d4e5f6g5 aggregate: Some Example 2',
            'c3d4e5f6g3 feat: Some Example feat2',
        ]);
        $gitExecutor->expects(self::never())->method('getCommitDescription');
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    public function testAggregateCustom(): void
    {
        $hashes = [];
        $versionAfter = '';
        $versionAfterExpected = '3.0.0';

        $textChangelog = '';
        $textChangelogExpected = '# 3.0.0 (' . date('Y-m-d') . ')

### New features
- Some Example aggregated
- Some Example feat1
- Some Example aggregated 2
- Added feature
- Some Example feat2

### Documentation
- Some docs changes aggregated
- Some docs changes aggregated 2

### Other
- doc(extremal): Some Example documents
- unknown: Unknown type in agregate

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example documents',
            'c3d4e5f6g4 custom: Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example feat1',
            'c3d4e5f6g5 custom: Some Example',
            'c3d4e5f6g3 feat: Some Example feat2',
        ]);
        $gitExecutor->expects(self::exactly(2))->method('getCommitDescription')->willReturnCallback(
            static function (string $hash) use (&$hashes): array {
                $hashes[] = $hash;

                return match ($hash) {
                    'c3d4e5f6g4' => [
                        'Any not formatted text',
                        '',
                        '-feat(extremal): Some Example aggregated',
                        'docs!: Some docs changes aggregated',
                        '',
                    ],
                    'c3d4e5f6g5' => [
                        '',
                        'feat: Some Example aggregated 2',
                        '',
                        'docs: Some docs changes aggregated 2',
                        '',
                        'unknown: Unknown type in agregate',
                        '',
                        'add: Added feature',
                    ],
                };
            },
        );
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = (new Config())
            ->addSectionRule('feat', new ExampleRule1())
            ->setAggregateSection('custom')
            ->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter, 'Major flag in aggregate');
        self::assertSame($textChangelogExpected, $textChangelog);
    }

    public function testSquashedDefault(): void
    {
        $hashes = [];

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example feat1
- Some Example feat2

### Documentation
- update README with configuration examples 5
- update README with configuration examples 4

### Other
- doc(extremal): Some Example documents

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example documents',
            'c3d4e5f6g4 Squashed commit of the following:',
            'c3d4e5f6g2 feat(extremal): Some Example feat1',
            'c3d4e5f6g3 feat: Some Example feat2',
        ]);
        $gitExecutor->expects(self::once())->method('getCommitDescription')->willReturnCallback(
            static function (string $hash) use (&$hashes): array {
                $hashes[] = $hash;

                return match ($hash) {
                    'c3d4e5f6g4' => [
                        'commit 2bf0dc5a380f17abc35d15c0f816c636d81cbfd2',
                        'Author: Name Lastname <devemail@email.com>',
                        'Date:   Sun Mar 23 15:20:02 2025 +0300',
                        '',
                        '   docs: update README with configuration examples 5',
                        '',
                        'commit cbae8944201f38a6676a493cf2d9f591ce3c1756',
                        'Author: Name Lastname <devemail@email.com>',
                        'Date:   Sun Mar 23 15:19:55 2025 +0300',
                        '',
                        '   docs: update README with configuration examples 4',
                    ],
                };
            },
        );
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = (new Config())
            ->setProcessDefaultSquashedCommit(true)
            ->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        ob_get_clean();
        self::assertSame($textChangelogExpected, $textChangelog);
    }

    public function testSquashedCustom(): void
    {
        $hashes = [];

        $textChangelog = '';
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example feat1
- Some Example feat2

### Documentation
- update README with configuration examples 5
- update README with configuration examples 4

### Other
- doc(extremal): Some Example documents

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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example documents',
            'c3d4e5f6g4 Squashed commit:',
            'c3d4e5f6g2 feat(extremal): Some Example feat1',
            'c3d4e5f6g3 feat: Some Example feat2',
        ]);
        $gitExecutor->expects(self::once())->method('getCommitDescription')->willReturnCallback(
            static function (string $hash) use (&$hashes): array {
                $hashes[] = $hash;

                return match ($hash) {
                    'c3d4e5f6g4' => [
                        'commit 2bf0dc5a380f17abc35d15c0f816c636d81cbfd2',
                        'Author: Name Lastname <devemail@email.com>',
                        'Date:   Sun Mar 23 15:20:02 2025 +0300',
                        '',
                        '   docs: update README with configuration examples 5',
                        '',
                        'commit cbae8944201f38a6676a493cf2d9f591ce3c1756',
                        'Author: Name Lastname <devemail@email.com>',
                        'Date:   Sun Mar 23 15:19:55 2025 +0300',
                        '',
                        '   docs: update README with configuration examples 4',
                    ],
                };
            },
        );
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = (new Config())
            ->setSquashedCommitMessage('Squashed commit:')
            ->setProcessDefaultSquashedCommit(true)
            ->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        ob_get_clean();
        self::assertSame($textChangelogExpected, $textChangelog);
    }

    public function testHiddenSection(): void
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

        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturn(true);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example',
            'c3d4e5f6g3 feat: Some Example',
            'c3d4e5f6g4 chore: Some Example on chore',
            'c3d4e5f6g4 build: Some Example on build',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = (new Config())
            ->setSection('chore', 'Hidden section', hidden: true)
            ->setSection('build', 'Hidden section', hidden: true)
            ->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame($versionAfterExpected, $versionAfter);
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 2.3.0 successfully created!\n", $output);
    }

    public function testDebug(): void
    {
        $textChangelogExpected = '# 2.3.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents->expects(self::never());
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::once())
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
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(1))
            ->willReturn(true);
        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example',
            'c3d4e5f6g3 feat: Some Example',
        ]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');

        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater
            ->setDebug(true)
            ->updateVersion();
        $output = ob_get_clean();
        self::assertSame($textChangelogExpected, $output);
    }

    /** @checked */
    public function testComposerFileNotWritable(): void
    {
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(1))
            ->willReturnCallback(static fn(string $fileName) => '/test/composer.json' !== $fileName);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(1))
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn(null);
        $gitExecutor->expects(self::never())->method('getCommitsSinceLastTag')->willReturn([]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = new Config();
        $config->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('/test', $config);
        $this->expectException(ComposerException::class);
        $this->expectExceptionMessage('Composer file is not writable.');
        $this->expectExceptionCode(10);
        $updater->updateVersion();
    }

    public function testChangelogFileNotWritable(): void
    {
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::exactly(2))
            ->willReturnCallback(static fn(string $fileName) => '/test/CHANGELOG.md' !== $fileName);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::exactly(2))
            ->willReturn(true);

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
        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(1))
            ->willReturn(null);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v2.2.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g11 doc(extremal): Some Example',
        ]);
        $gitExecutor->expects(self::never())->method('setVersionTag');
        $gitExecutor->expects(self::never())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = new Config();
        $config->setVcsExecutor($gitExecutor);

        $updater = new SemanticVersionUpdater('/test', $config);
        $this->expectException(ChangelogException::class);
        $this->expectExceptionMessage('Changelog file is not writable.');
        $this->expectExceptionCode(80);
        $updater->updateVersion();
    }

    public function testUpdateVersionWithoutComposerJson(): void
    {
        $versionAfter = '';
        $textChangelog = '';
        $textChangelogExpected = '# 4.2.0 (' . date('Y-m-d') . ')

### New features
- Some Example
- Some Example

### Other
- doc(extremal): Some Example

';

        $filePutContents = $this->getFunctionMock(__NAMESPACE__, 'file_put_contents');
        $filePutContents
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $fileName, string $contents) use (&$versionAfter, &$textChangelog): void {
                    if ('/test/composer.json' === $fileName) {
                        $versionAfter = 'wrong';
                    } elseif ('/test/CHANGELOG.md' === $fileName) {
                        $textChangelog = $contents;
                    }
                },
            );
        $fileGetContents = $this->getFunctionMock(__NAMESPACE__, 'file_get_contents');
        $fileGetContents
            ->expects(self::once())
            ->willReturn('');
        $fileIsWritable = $this->getFunctionMock(__NAMESPACE__, 'is_writable');
        $fileIsWritable
            ->expects(self::once())
            ->willReturn(true);

        $fileExists = $this->getFunctionMock(__NAMESPACE__, 'file_exists');
        $fileExists
            ->expects(self::once())
            ->willReturn(true);

        $gitExecutor = self::createMock(VcsExecutorInterface::class);
        $gitExecutor->expects(self::once())->method('getCurrentBranch')->willReturn('master');
        $gitExecutor->expects(self::once())->method('status')->willReturn([]);
        $gitExecutor->expects(self::once())->method('getLastTag')->willReturn('v4.1.0');
        $gitExecutor->expects(self::once())->method('getCommitsSinceLastTag')->willReturn([
            'c3d4e5f6g1 doc(extremal): Some Example',
            'c3d4e5f6g2 feat(extremal): Some Example',
            'c3d4e5f6g3 feat: Some Example',
        ]);
        $gitExecutor->expects(self::once())->method('setVersionTag');
        $gitExecutor->expects(self::once())->method('commit');
        $gitExecutor->expects(self::never())->method('addFile');
        $config = new Config();
        $config->setVcsExecutor($gitExecutor);
        $config->setEnabledComposerVersioning(false);
        $updater = new SemanticVersionUpdater('/test', $config);
        ob_start();
        $updater->updateVersion();
        $output = ob_get_clean();
        self::assertSame('', $versionAfter, 'Composer version was not updated.');
        self::assertSame($textChangelogExpected, $textChangelog);
        self::assertSame("Release 4.2.0 successfully created!\n", $output);
    }
}

class ExampleRule1 implements SectionRuleInterface
{
    public function __invoke(Commit $commit): bool
    {
        return 'add' === $commit->type;
    }
}

class ExampleRule2 implements SectionRuleInterface
{
    public function __invoke(Commit $commit): bool
    {
        return str_starts_with(strtolower($commit->comment), 'added');
    }
}
