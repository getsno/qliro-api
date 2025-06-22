<?php

namespace Gets\QliroApi\Enums;

enum OrderStatus: string
{
    case New = 'New';
    case Completed = 'Completed';
    case PartiallyCompleted = 'PartiallyCompleted';
    case Cancelled = 'Cancelled';

    case PartiallyRefunded = 'PartiallyRefunded';
    case Refunded = 'Refunded';
}
