<?php

namespace App\Enums;

enum CardType: string
{
    case Credit = 'credit';
    case Debit = 'debit';
    case Unknown = 'unknown';
}
