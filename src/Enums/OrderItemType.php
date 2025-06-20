<?php

namespace Gets\QliroApi\Enums;

enum OrderItemType: string
{
    case Product = 'Product';
    case Shipping = 'Shipping';
    case Fee = 'Fee';
    case Discount = 'Discount';
}
