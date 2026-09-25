<?php

namespace App\Enums;

enum PolicyRule: string
{
    case OwnershipUnverified = 'R1';
    case AlreadyRefundedOrPending = 'R2';
    case NotDelivered = 'R3';
    case FinalSale = 'R4';
    case FinalSaleProductFault = 'R4a';
    case OutsideRefundWindow = 'R5';
    case HighValue = 'R6';
    case Suspicious = 'R7';
    case LowConfidence = 'R8';
    case Eligible = 'R9';

    public function description(): string
    {
        return match ($this) {
            self::OwnershipUnverified => 'Order could not be verified against the email provided.',
            self::AlreadyRefundedOrPending => 'This item has already been refunded or has a request in progress.',
            self::NotDelivered => 'The order has not been delivered yet.',
            self::FinalSale => 'Final sale items are not eligible for refunds.',
            self::FinalSaleProductFault => 'Final sale item reported damaged or incorrect requires human review.',
            self::OutsideRefundWindow => 'The refund window has passed.',
            self::HighValue => 'Refunds above the auto-approval limit require human review.',
            self::Suspicious => 'The request shows risk signals and requires human review.',
            self::LowConfidence => 'The request could not be classified with enough confidence.',
            self::Eligible => 'The request meets the refund policy.',
        };
    }
}
