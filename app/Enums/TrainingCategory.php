<?php

namespace App\Enums;

enum TrainingCategory: string
{
    case Opening = 'opening';
    case Middlegame = 'middlegame';
    case Endgame = 'endgame';
    case Tactics = 'tactics';
    case Strategy = 'strategy';
    
    public function label(): string
    {
        return match ($this->value) {
            'opening' => 'Apertura',
            'middlegame' => 'Medio juego',
            'endgame' => 'Final',
            'tactics' => 'Táctica',
            'strategy' => 'Estrategia',
        };
    }
}
