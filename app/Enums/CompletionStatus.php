<?php

namespace App\Enums;

enum CompletionStatus: string
{
    case Pending = 'pending';
    case Complete = 'complete';
}
