<?php

namespace App\Enum;

enum MoveResult: string
{
    case OK = "ok";
    case ERROR = "error";
    case GAMEOVER = 'game-over';
    case VICTORY = 'victory';
}
