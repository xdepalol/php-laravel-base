<?php

namespace App\Enums;

enum BacklogItemStatus: int
{
    case BACKLOG = 0;
    case IN_PROGRESS = 1;
    case DONE = 2;
    case CANCELLED = 3;
}
