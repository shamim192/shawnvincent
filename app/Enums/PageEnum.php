<?php

namespace App\Enums;

enum PageEnum: string
{
    const AUTH  = 'login';
    case HOME   = 'home';
    case MUSIC   = 'music';
    case COMMON = 'common';
}
