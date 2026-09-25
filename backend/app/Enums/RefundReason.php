<?php

namespace App\Enums;

enum RefundReason: string
{
    case Damaged = 'damaged';
    case WrongItem = 'wrong_item';
    case ChangedMind = 'changed_mind';
    case NotReceived = 'not_received';
    case Other = 'other';

    public function isProductFault(): bool
    {
        return in_array($this, [self::Damaged, self::WrongItem], true);
    }
}
