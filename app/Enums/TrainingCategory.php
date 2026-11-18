<?php

namespace App\Enums;

enum TrainingCategory: string
{
    case Opening = 'opening';
    case Middlegame = 'middlegame';
    case Endgame = 'endgame';
    case Tactics = 'tactics';
    case Strategy = 'strategy';
}