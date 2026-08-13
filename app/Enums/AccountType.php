<?php

namespace App\Enums;

enum AccountType: string
{
    case BANK = 'bank';
    case CASH = 'cash';
    case CREDIT_CARD = 'credit_card';
    case SAVINGS = 'savings';
    case INVESTMENT = 'investment';
    case OTHER = 'other';
}
