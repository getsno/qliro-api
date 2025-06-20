<?php

namespace Gets\QliroApi\Enums;

enum CustomerCheckoutStatus: string
{
    case Success= 'Success';
    case Completed = 'Completed';
    case OnHold = 'OnHold';
    case InProcess = 'InProcess';
}
