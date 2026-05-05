<?php

namespace App\Enums;

enum AssignmentSource: string
{
    case Self = 'self';
    case Privileged = 'privileged';
}
