<?php

namespace App\Enum;

enum GameStatus: string
{
    case PLAYING = 'playing';
    case GAMEOVER = 'game-over';
    case VICTORY = 'victory';
}
