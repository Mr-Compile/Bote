<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case Earned = 'Earned';
    case Redeemed = 'Redeemed';
    case Refunded = 'Refunded';
}
