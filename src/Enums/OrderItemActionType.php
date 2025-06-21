<?php

namespace Gets\QliroApi\Enums;

enum OrderItemActionType: string
{
    case Create = 'Create';
    case Reserve = 'Reserve';
    case Ship = 'Ship';
    case Return = 'Return';
    case Release = 'Release';
}
