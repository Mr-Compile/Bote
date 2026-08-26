<?php

declare(strict_types=1);

namespace App\Enums;

enum PickupStatus: string
{
    case Pending = 'Pending';
    case Scheduled = 'Scheduled';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
