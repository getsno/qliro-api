<?php

namespace Gets\QliroApi\Enums;

enum PaymentTransactionStatus: string
{
    case Created = 'Created';
    case InProcess = 'InProcess';
    case UserInteractionRequired = 'UserInteractionRequired';
    case OnHold = 'OnHold';
    case Success = 'Success';
    case Error = 'Error';
    case Cancelled = 'Cancelled';
}
