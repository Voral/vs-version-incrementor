<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Commits\FileModifyType;
use Vasoft\VersionIncrement\Commits\ModifiedFile;
use Vasoft\VersionIncrement\Exceptions\GitCommandException;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\GitExecutor
 */
final class GitExecutorTest extends TestCase
{
    use PHPMock;

    public function testCommit(): void
    {
        $lastCommand = '';
        $expectedCommand = "git commit -am 'commit message' 2>&1";
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$lastCommand): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = [];
                },
            );
        $executor = new GitExecutor();
        $executor->commit('commit message');
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testSetVersionTag(): void
    {
        $lastCommand = '';
        $expectedCommand = 'git tag v14.0.1 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$lastCommand): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = [];
                },
            );
        $executor = new GitExecutor();
        $executor->setVersionTag('14.0.1');
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testAddFile(): void
    {
        $lastCommand = '';
        $expectedCommand = 'git add example.php 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$lastCommand): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = [];
                },
            );
        $executor = new GitExecutor();
        $executor->addFile('example.php');
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testGetLastTag(): void
    {
        $commandOutput = [
            'v18.1.0',
            'v18.0.0',
            'v17.1.0',
        ];
        $lastCommand = '';
        $expectedCommand = 'git tag --sort=-creatordate 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        self::assertSame('v18.1.0', $executor->getLastTag());
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testGetLastTagNoTags(): void
    {
        $commandOutput = [];
        $lastCommand = '';
        $expectedCommand = 'git tag --sort=-creatordate 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        self::assertNull($executor->getLastTag());
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testGetCommitsSinceLastTagNoTag(): void
    {
        $commandOutput = [
            'chore: Quality badges added',
            'build: Configure version increment',
        ];
        $lastCommand = '';
        $expectedCommand = 'git log --pretty=format:"%H %s" 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        $commits = $executor->getCommitsSinceLastTag(null);
        self::assertSame($commandOutput, $commits);
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testGetCommitsSinceLastTag(): void
    {
        $commandOutput = [
            'chore: Quality badges added',
            'fix: Change type in composer.json',
        ];
        $lastCommand = '';
        $expectedCommand = 'git log v2.0.0..HEAD --pretty=format:"%H %s" 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        $commits = $executor->getCommitsSinceLastTag('v2.0.0');
        self::assertSame($commandOutput, $commits);
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testStatus(): void
    {
        $commandOutput = [
            'M composer.json',
            'AM src/Contract/GetExecutorInterface.php',
            'AM src/GitExecutor.php',
            'M src/SemanticVersionUpdater.php',
            'AM tests/GitExecutorTest.php',
        ];

        $lastCommand = '';
        $expectedCommand = 'git status --porcelain 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        self::assertSame($commandOutput, $executor->status());
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testFilesSinceTag(): void
    {
        $lastCommand = '';
        $expectedCommand = 'git diff --name-status v1.3.0... 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (&$lastCommand): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = [
                        "A\tfile1.php",
                        "D\tfile2.php",
                        "M\tfile3.php",
                        "R\tfile4.php\tfile5.php",
                        "R78\tfile14.php\tfile15.php",
                        "C\tfile6.php\tfile7.php",
                        "U\tfile99.php",
                    ];
                },
            );
        $executor = new GitExecutor();
        $result = $executor->getFilesSinceTag('v1.3.0');

        self::assertCount(6, $result);

        self::assertInstanceOf(ModifiedFile::class, $result[2]);

        self::assertSame(FileModifyType::ADD, $result[0]->type);
        self::assertSame('file1.php', $result[0]->path);
        self::assertSame('', $result[0]->destination);

        self::assertSame(FileModifyType::DELETE, $result[1]->type);
        self::assertSame('file2.php', $result[1]->path);
        self::assertSame('', $result[1]->destination);

        self::assertSame(FileModifyType::MODIFY, $result[2]->type);
        self::assertSame('file3.php', $result[2]->path);
        self::assertSame('', $result[2]->destination);

        self::assertSame(FileModifyType::RENAME, $result[3]->type);
        self::assertSame('file4.php', $result[3]->path);
        self::assertSame('file5.php', $result[3]->destination);

        self::assertSame(FileModifyType::RENAME, $result[4]->type);
        self::assertSame('file14.php', $result[4]->path);
        self::assertSame('file15.php', $result[4]->destination);

        self::assertSame(FileModifyType::COPY, $result[5]->type);
        self::assertSame('file6.php', $result[5]->path);
        self::assertSame('file7.php', $result[5]->destination);

        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testGetCurrentBranch(): void
    {
        $commandOutput = [
            'feature',
        ];

        $lastCommand = '';
        $expectedCommand = 'git rev-parse --abbrev-ref HEAD 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        self::assertSame($commandOutput[0], $executor->getCurrentBranch());
        self::assertSame($expectedCommand, $lastCommand);
    }

    public function testErrorCommand(): void
    {
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null): void {
                    $returnCode = 1;
                    $output = ['Error message'];
                },
            );
        $executor = new GitExecutor();
        $this->expectException(GitCommandException::class);
        $this->expectExceptionMessage("Error executing Git command: git rev-parse --abbrev-ref HEAD\nError message");
        $this->expectExceptionCode(60);
        $executor->getCurrentBranch();
    }

    public function testGetCommitDescription(): void
    {
        $commandOutput = [
            'chore: Quality badges added',
            'fix: Change type in composer.json',
        ];
        $lastCommand = '';
        $expectedCommand = 'git log -1 --pretty=format:%b 12ab21123da3dd 2>&1';
        $exec = $this->getFunctionMock(__NAMESPACE__, 'exec');
        $exec
            ->expects(self::exactly(1))
            ->willReturnCallback(
                static function (string $command, &$output = null, ?int &$returnCode = null) use (
                    &$lastCommand,
                    $commandOutput
                ): void {
                    $lastCommand = $command;
                    $returnCode = 0;
                    $output = $commandOutput;
                },
            );
        $executor = new GitExecutor();
        $description = $executor->getCommitDescription('12ab21123da3dd');
        self::assertSame($commandOutput, $description);
        self::assertSame($expectedCommand, $lastCommand);
    }
}
