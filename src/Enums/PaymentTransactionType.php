<?php

namespace Gets\QliroApi\Enums;

enum PaymentTransactionType: string
{
    case Preauthorization = "Preauthorization";
    case Debit = "Debit";
    case Credit = "Credit";
    case Capture = "Capture";
    case Reversal = "Reversal";
    case Refund = "Refund";
    case UpdateInvoice = "UpdateInvoice";
    case Registration = "Registration";
}
