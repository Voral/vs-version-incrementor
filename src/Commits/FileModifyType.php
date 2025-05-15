<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Commits;

enum FileModifyType: string
{
    case ADD = 'A';
    case MODIFY = 'M';
    case DELETE = 'D';
    case RENAME = 'R';
    case COPY = 'C';
}
