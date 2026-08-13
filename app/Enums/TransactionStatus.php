<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case COMPLETED = 'completed';
    case PENDING = 'pending';
}
