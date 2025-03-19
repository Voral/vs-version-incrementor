<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement;

use PHPUnit\Framework\TestCase;

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
}
