<?php

namespace Gets\QliroApi\Enums;

enum OrderChangeType: string
{
    case Delete = 'Delete';
    case Decrease = 'Decrease';
    case Replace = 'Replace';
}
