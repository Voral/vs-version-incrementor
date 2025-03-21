<?php

declare(strict_types=1);

use Vasoft\VersionIncrement\Config;

/*
 * Example configuration for integrating Version Incrementor with Keep a Changelog.
 *
 * This configuration maps commit types to changelog sections as defined by the Keep a Changelog standard:
 * - Added: New features or functionality.
 * - Changed: Changes in existing functionality.
 * - Deprecated: Features that will be removed in the future.
 * - Removed: Features that have been removed.
 * - Fixed: Bug fixes.
 * - Security: Security-related changes.
 *
 * Each section is ordered for proper display in the changelog.
 *
 * To use this configuration in your project:
 * 1. Copy this file to the root of your project.
 * 2. Rename it to `.vs-version-increment.php`.
 * 3. Adjust the settings as needed to fit your project's requirements.
 *
 * @see https://keepachangelog.com/
 */
return (new Config())
    ->setSections([
        'added' => [
            'title' => 'Added',
            'order' => 10,
            'hidden' => false,
        ],
        'changed' => [
            'title' => 'Changed',
            'order' => 20,
            'hidden' => false,
        ],
        'deprecated' => [
            'title' => 'Deprecated',
            'order' => 30,
            'hidden' => false,
        ],
        'removed' => [
            'title' => 'Removed',
            'order' => 40,
            'hidden' => false,
        ],
        'fixed' => [
            'title' => 'Fixed',
            'order' => 50,
            'hidden' => false,
        ],
        'security' => [
            'title' => 'Security',
            'order' => 60,
            'hidden' => false,
        ],
    ]);