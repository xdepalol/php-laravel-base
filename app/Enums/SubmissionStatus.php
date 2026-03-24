<?php

namespace App\Enums;

enum SubmissionStatus: int
{
    case PENDING = 0;
    case SUBMITTED = 1;
    case GRADED = 2;
}
