<?php

namespace Gets\QliroApi\Exceptions;

enum ErrorCode: string
{
    case InvalidInput = 'INVALID_INPUT';
    case MerchantUrlNotSet = 'MERCHANT_URL_NOT_SET';
    case OperationNotSupported = 'OPERATION_NOT_SUPPORTED';
    case OrderHasBeenCancelled = 'ORDER_HAS_BEEN_CANCELLED';
    case Unauthorized = 'UNAUTHORIZED';
    case Forbidden = 'FORBIDDEN';
    case InvalidPaymentType = 'INVALID_PAYMENT_TYPE';
    case InvalidItem = 'INVALID_ITEM';
    case PaymentReferenceIsIncorrect = 'PAYMENT_REFERENCE_IS_INCORRECT';
    case InvalidRequestTotalAmount = 'INVALID_REQUEST_TOTAL_AMOUNT';
    case OrderNotFound = 'ORDER_NOT_FOUND';
}
