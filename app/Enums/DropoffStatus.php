<?php

declare(strict_types=1);

namespace App\Enums;

enum DropoffStatus: string
{
    case Pending = 'Pending';
    case Verified = 'Verified';
    case Completed = 'Completed';
    case Rejected = 'Rejected';
}
