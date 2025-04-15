<?php

declare(strict_types=1);

use Vasoft\VersionIncrement\Config;

return (new Config())
    ->setEnabledComposerVersioning(false)
    ->addScope('api', 'API')
    ->addScope('front', 'Frontend')
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
