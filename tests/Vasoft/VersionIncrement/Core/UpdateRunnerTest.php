<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Core;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\Contract\VcsExecutorInterface;
use Vasoft\VersionIncrement\MockTrait;

include_once __DIR__ . '/../MockTrait.php';

/**
 * @coversDefaultClass \Vasoft\VersionIncrement\Core\UpdateRunner
 *
 * @internal
 */
final class UpdateRunnerTest extends TestCase
{
    use MockTrait;

    public function testModeNoCommit(): void
    {
        $expectedOutput = <<<'TEXT'
            Version 2.3.0 is ready for release.
            To complete the process, commit your changes and add a Git tag:
                git commit -m "chore(release): v2.3.0"
                git tag v2.3.0

            TEXT;

        $this->initMocks();
        $this->clearFileExists([
            '/test/composer.json' => true,
            '/test/CHANGELOG.md' => true,
        ]);
        $this->clearFilePutContents();
        $this->clearFileGetContents([
            '/test/composer.json' => json_encode(
                ['version' => '2.2.0', 'name' => 'test'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            ),
            '/test/CHANGELOG.md' => '',
        ]);
        $this->clearIsWritable([
            '/test/composer.json' => true,
            '/test/CHANGELOG.md' => true,
        ]);

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
        $runner = new UpdateRunner('/test', $config);
        ob_start();
        $runner->handle(['--no-commit']);
        $output = ob_get_clean();
        self::assertSame(2, self::$mockFilePutContentsCount, 'count of calls file_put_contents');
        self::assertSame($expectedOutput, $output);
    }
}
