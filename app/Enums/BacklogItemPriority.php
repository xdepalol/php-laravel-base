<?php

namespace App\Enums;

enum BacklogItemPriority: int
{
    case LOW = 1;
    case MEDIUM = 2;
    case HIGH = 3;
    case CRITICAL = 4;
}
