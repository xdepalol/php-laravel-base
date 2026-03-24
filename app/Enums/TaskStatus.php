<?php

namespace App\Enums;

enum TaskStatus: int
{
    case TODO = 0;
    case DOING = 1;
    case DONE = 2;
    case CANCELLED = 3;
}
