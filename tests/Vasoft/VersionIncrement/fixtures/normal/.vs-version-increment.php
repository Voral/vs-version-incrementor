<?php

declare(strict_types=1);

use Vasoft\VersionIncrement\Config;

include_once __DIR__ . '/VasoftTestGitExecutor.php';
return (new Config())
    ->setEnabledComposerVersioning(false)
    ->setVcsExecutor(new VasoftTestGitExecutor())
    ->setSections([
        'add' => [
            'title' => 'Added',
            'order' => 10,
            'hidden' => false,
        ],
        'upd' => [
            'title' => 'Changed',
            'order' => 20,
            'hidden' => false,
        ],
    ]);
