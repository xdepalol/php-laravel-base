<?php

namespace App\Enums;

enum ActivityType: int {
    case GENERAL = 0;
    case PROJECT = 1;
    case LAB_PRACTICE = 2;
    case EXAM = 3;
    case TECH_DOCUMENT = 4;
}