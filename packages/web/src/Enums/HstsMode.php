<?php

namespace Froxlor\Web\Enums;

enum HstsMode: int
{
    case None = 0;
    case Sub = 1;
    case Preload = 2;
    case SubAndPreload = 3;
}
