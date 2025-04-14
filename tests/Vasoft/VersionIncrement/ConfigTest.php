<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use PHPUnit\Framework\TestCase;
use Vasoft\VersionIncrement\Commits\ShortParser;
use Vasoft\VersionIncrement\Contract\CommitParserInterface;
use Vasoft\VersionIncrement\Contract\TagFormatterInterface;
use Vasoft\VersionIncrement\Exceptions\UnknownPropertyException;

/**
 * @coversDefaultClass \Vasoft\VersionIncrement\Config
 *
 * @internal
 */
final class ConfigTest extends TestCase
{
    public function testReleaseSection(): void
    {
        $config = new Config();
        self::assertSame('chore', $config->getReleaseSection(), 'Default value release section must be chore');
        $config->setReleaseSection('unknown');
        self::assertSame(
            'other',
            $config->getReleaseSection(),
            'Release section must be other, because section not exists',
        );
        $config->setReleaseSection('docs');
        self::assertSame('docs', $config->getReleaseSection(), 'Release section must be docs');
    }

    public function testGetDefaultSectionIndex(): void
    {
        $config = new Config();
        $expected = [
            'feat' => [],
            'fix' => [],
            'chore' => [],
            'docs' => [],
            'style' => [],
            'refactor' => [],
            'test' => [],
            'perf' => [],
            'ci' => [],
            'build' => [],
            'other' => [],
        ];
        $actual = $config->getSectionIndex();
        self::assertSame($expected, $actual);
        self::assertSame(array_keys($expected), array_keys($actual));
    }

    public function testReplaceSection(): void
    {
        $config = new Config();
        self::assertSame('New features', $config->getSectionTitle('feat'));
        self::assertFalse($config->isSectionHidden('feat'));
        $config->setSection('feat', 'Replaced title', 25, true);
        $expected = [
            'fix' => [],
            'feat' => [],
            'chore' => [],
            'docs' => [],
            'style' => [],
            'refactor' => [],
            'test' => [],
            'perf' => [],
            'ci' => [],
            'build' => [],
            'other' => [],
        ];
        $actual = $config->getSectionIndex();
        self::assertSame($expected, $actual);
        self::assertSame(implode(',', array_keys($expected)), implode(',', array_keys($actual)));
        self::assertSame('Replaced title', $config->getSectionTitle('feat'));
        self::assertTrue($config->isSectionHidden('feat'));
    }

    public function testSetSections(): void
    {
        $config = new Config();
        $config->setSections([
            'feat' => [
                'title' => 'New features title',
                'order' => 2000,
                'hidden' => true,
            ],
            'build' => [
                'title' => 'New build',
                'order' => 1000,
                'hidden' => true,
            ],
        ]);
        $expected = [
            'build' => [],
            'feat' => [],
            'other' => [],
        ];
        $actual = $config->getSectionIndex();
        self::assertSame($expected, $actual);
        self::assertSame(array_keys($expected), array_keys($actual));
        self::assertSame('New features title', $config->getSectionTitle('feat'));
        self::assertTrue($config->isSectionHidden('feat'));
        self::assertSame('New build', $config->getSectionTitle('build'));
        self::assertTrue($config->isSectionHidden('build'));
        self::assertSame('Other', $config->getSectionTitle('other'));
        self::assertFalse($config->isSectionHidden('other'));
    }

    public function testGetDefaultSectionDescriptions(): void
    {
        $config = new Config();
        $expected = [
            'feat - New features',
            'fix - Fixes',
            'chore - Other changes',
            'docs - Documentation',
            'style - Styling',
            'refactor - Refactoring',
            'test - Tests',
            'perf - Performance',
            'ci - Configure CI',
            'build - Change build system',
            'other - Other',
        ];
        $actual = $config->getSectionDescriptions();
        self::assertSame($expected, $actual);
    }

    public function testGetDefaultSectionDescriptionsCustom(): void
    {
        $config = new Config();
        $config->setSection('fix', 'Bug fixes');
        $expected = [
            'feat - New features',
            'fix - Bug fixes',
            'chore - Other changes',
            'docs - Documentation',
            'style - Styling',
            'refactor - Refactoring',
            'test - Tests',
            'perf - Performance',
            'ci - Configure CI',
            'build - Change build system',
            'other - Other',
        ];
        $actual = $config->getSectionDescriptions();
        self::assertSame($expected, $actual);
    }

    public function testGetCommitParserReturnsShortParserByDefault(): void
    {
        $parser = (new Config())->getCommitParser();
        self::assertInstanceOf(ShortParser::class, $parser, 'Default parse can be ShortParser');
    }

    public function testSetCommitParserStoresAndInitializesParser(): void
    {
        $config = new Config();
        $parser = self::createMock(CommitParserInterface::class);
        $parser->expects(self::once())
            ->method('setConfig')
            ->with($config);
        $config->setCommitParser($parser);
        self::assertSame($parser, $config->getCommitParser());
    }

    public function testGetTagFormatterReturnsDefault(): void
    {
        $formatter = (new Config())->getTagFormatter();
        self::assertInstanceOf(Tag\DefaultFormatter::class, $formatter, 'Default parse can be ShortParser');
    }

    public function testSetTagFormatterAndInitializes(): void
    {
        $config = new Config();
        $formatter = self::createMock(TagFormatterInterface::class);
        $formatter->expects(self::once())
            ->method('setConfig')
            ->with($config);
        $config->setTagFormatter($formatter);
        self::assertSame($formatter, $config->getTagFormatter());
    }

    public function testEnabledComposerVersioning(): void
    {
        $config = new Config();
        self::assertTrue($config->isEnabledComposerVersioning(), 'Composer versioning is true by default');
        $config->setEnabledComposerVersioning(false);
        self::assertFalse($config->isEnabledComposerVersioning(), 'Composer versioning can be disabled');
    }

    /**
     * @dataProvider provideUserPropertySavedCases
     */
    public function testUserPropertySaved(mixed $value, string $description): void
    {
        $config = new Config();
        $config->set('test', $value);
        self::assertSame($value, $config->get('test'), $description);
    }

    public static function provideUserPropertySavedCases(): iterable
    {
        return [
            [1, 'Integer value'],
            [1.1, 'Float value'],
            ['string', 'String value'],
            [[123, '56'], 'Array value'],
            [new \stdClass(), 'Object value'],
        ];
    }

    public function testUserPropertyException(): void
    {
        $config = new Config();
        self::expectException(UnknownPropertyException::class);
        self::expectExceptionMessage('Unknown property: test');
        $config->get('test');
    }
}
