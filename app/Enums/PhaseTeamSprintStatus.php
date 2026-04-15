<?php

namespace App\Enums;

enum PhaseTeamSprintStatus: int
{
    case ASSIGNING_TASKS = 0;
    case DEVELOPING = 1;
    case REVISING = 2;
    case RETROSPECTIVE = 3;
    case FINISHED = 4;

    /**
     * Siguiente estado permitido (un solo paso; desde FINISHED vuelve a empezar el ciclo del sprint en la misma fase).
     */
    public function next(): PhaseTeamSprintStatus
    {
        return match ($this) {
            self::FINISHED => self::ASSIGNING_TASKS,
            self::ASSIGNING_TASKS => self::DEVELOPING,
            self::DEVELOPING => self::REVISING,
            self::REVISING => self::RETROSPECTIVE,
            self::RETROSPECTIVE => self::FINISHED,
        };
    }
}
