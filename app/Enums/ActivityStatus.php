<?php

namespace App\Enums;

enum ActivityStatus: int {
    case DRAFT = 0;
    case PUBLISHED = 1;
    case CLOSED = 2;
}