<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
