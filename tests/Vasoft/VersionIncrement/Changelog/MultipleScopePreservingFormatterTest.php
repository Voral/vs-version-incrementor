<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Changelog;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Changelog\Interpreter\SinglePreservedScopeInterpreter;
use Vasoft\VersionIncrement\Commits\Commit;
use Vasoft\VersionIncrement\Commits\CommitCollection;
use Vasoft\VersionIncrement\Commits\Section;
use Vasoft\VersionIncrement\Config;
use Vasoft\VersionIncrement\SectionRules\DefaultRule;
use Vasoft\VersionIncrement\Contract\ScopeInterpreterInterface;

/**
 * @internal
 *
 * @coversDefaultClass \Vasoft\VersionIncrement\Changelog\MultipleScopePreservingFormatter
 */
final class MultipleScopePreservingFormatterTest extends TestCase
{
    private static ?Config $config = null;
    private static ?CommitCollection $commitCollection = null;
    /** @var array<ScopeInterpreterInterface> */
    private static array $interpreters = [];
    /** @var array<string,Section> */
    private static array $sections = [];

    public static function setUpBeforeClass(): void
    {
        self::$config = new Config();
        self::$config->addScope('test', 'Testing');
        self::$config->addScope('api', 'REST');
        self::$sections = [
            'feat' => new Section('feat', 'Features', false, [new DefaultRule('feat')], false, false, self::$config),
        ];
        self::$interpreters = [
            'api',
            'order',
            new SinglePreservedScopeInterpreter(['dev', 'test'], self::$config, '%s'),
        ];
        self::$commitCollection = new CommitCollection(self::$sections, self::$sections['feat']);
        self::$commitCollection->add(new Commit('feat(dev): title 1', 'feat', 'title 1', false, 'dev'));
        self::$commitCollection->add(new Commit('feat(dev|test): title 2', 'feat', 'title 2', false, 'dev|test'));
        self::$commitCollection->add(new Commit('feat(hidden): title 3', 'feat', 'title 3', false, 'hidden'));
        self::$commitCollection->add(new Commit('feat(api): title 4', 'feat', 'title 4', false, 'api'));
        self::$commitCollection->add(new Commit('feat(order): title 5', 'feat', 'title 5', false, 'order'));
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass(): void
    {
        self::$config = null;
        self::$commitCollection = null;
        self::$interpreters = [];
        self::$sections = [];
        parent::tearDownAfterClass();
    }

    public function testCustomSrcSeparator(): void
    {
        $sections = [
            'feat' => new Section('feat', 'Features', false, [new DefaultRule('feat')], false, false, self::$config),
        ];
        $commitCollection = new CommitCollection($sections, $sections['feat']);
        $commitCollection->add(new Commit('feat(dev): title 1', 'feat', 'title 1', false, 'dev'));
        $commitCollection->add(new Commit('feat(dev#test): title 2', 'feat', 'title 2', false, 'dev#test'));

        $expect = '# v1.0.0 (' . date('Y-m-d') . ')

### Features
- dev title 1
- dev|Testing title 2

';

        $formatter = new MultipleScopePreservingFormatter(self::$interpreters, srcSeparator: '#');
        $formatter->setConfig(self::$config);
        self::assertSame($expect, $formatter($commitCollection, 'v1.0.0'));
    }

    public function testDefault(): void
    {
        $expect = '# v1.0.0 (' . date('Y-m-d') . ')

### Features
- dev title 1
- dev|Testing title 2
- title 3
- REST title 4
- order title 5

';

        $formatter = new MultipleScopePreservingFormatter(self::$interpreters);
        $formatter->setConfig(self::$config);
        self::assertSame($expect, $formatter(self::$commitCollection, 'v1.0.0'));
    }

    public function testCustomDstSeparator(): void
    {
        $expect = '# v1.0.0 (' . date('Y-m-d') . ')

### Features
- dev title 1
- dev,Testing title 2
- title 3
- REST title 4
- order title 5

';

        $formatter = new MultipleScopePreservingFormatter(self::$interpreters, dstSeparator: ',');
        $formatter->setConfig(self::$config);
        self::assertSame($expect, $formatter(self::$commitCollection, 'v1.0.0'));
    }

    public function testCustomOverallTemplate(): void
    {
        $expect = '# v1.0.0 (' . date('Y-m-d') . ')

### Features
- dev >>> title 1
- dev|Testing >>> title 2
- title 3
- REST >>> title 4
- order >>> title 5

';
        $formatter = new MultipleScopePreservingFormatter(self::$interpreters, overallTemplate: '%s >>> ');
        $formatter->setConfig(self::$config);
        self::assertSame($expect, $formatter(self::$commitCollection, 'v1.0.0'));
    }
}
