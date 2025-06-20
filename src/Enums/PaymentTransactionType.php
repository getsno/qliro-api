<?php

namespace Gets\QliroApi\Enums;

enum PaymentTransactionType: string
{
    case Preauthorization = 'Preauthorization';
    case Reversal = 'Reversal';
}
