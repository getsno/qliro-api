<?php

namespace Gets\QliroApi\Enums;

enum DeclineReason: string
{
    case OutOfStock = 'OutOfStock';
    case PostalCodeIsNotSupported = 'PostalCodeIsNotSupported';
    case ShippingIsNotSupportedForPostalCode = 'ShippingIsNotSupportedForPostalCode';
    case CashOnDeliveryIsNotSupportedForShippingMethod = 'CashOnDeliveryIsNotSupportedForShippingMethod';
    case IdentityNotVerified = 'IdentityNotVerified';
    case Other = 'Other';
}
