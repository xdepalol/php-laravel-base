<?php

namespace App\Enums;

enum EnrollmentStatus: int
{
    case ENROLLED = 1;
    case DROPPED = 2;
    case PASSED = 3;
    case FAILED = 4;

    // Mètode opcional per obtenir el text en català per a la UI
    public function label(): string
    {
        return match($this) {
            self::ENROLLED => 'Matriculat',
            self::DROPPED => 'Baixa',
            self::PASSED => 'Apte',
            self::FAILED => 'No Apte',
        };
    }
}