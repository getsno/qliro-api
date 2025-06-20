<?php

namespace Gets\QliroApi\Exceptions;

use Throwable;
use Saloon\Http\Response;

class QliroException extends \Exception
{

    private static array $errorCodeMapping = [
        ErrorCode::InvalidInput->value          => [
            'exception' => InvalidInputException::class,
            'status'    => 422,
        ],
        ErrorCode::MerchantUrlNotSet->value     => [
            'exception' => MerchantUrlNotSetException::class,
            'status'    => 422,
        ],
        ErrorCode::OperationNotSupported->value => [
            'exception' => OperationNotSupportedException::class,
            'status'    => 400,
        ],
        ErrorCode::OrderHasBeenCancelled->value => [
            'exception' => OrderHasBeenCancelledException::class,
            'status'    => 400,
        ],
        ErrorCode::Unauthorized->value          => [
            'exception' => AuthorizationException::class,
            'status'    => 403,
        ],
        ErrorCode::Forbidden->value             => [
            'exception' => AuthorizationException::class,
            'status'    => 403,
        ],
        ErrorCode::InvalidPaymentType->value    => [
            'exception' => InvalidPaymentTypeException::class,
            'status'    => 400,
        ],
        ErrorCode::InvalidItem->value         => [
            'exception' => InvalidItemException::class,
            'status'    => 400,
        ],
        ErrorCode::PaymentReferenceIsIncorrect->value => [
            'exception' => PaymentReferenceIsIncorrectException::class,
            'status'    => 400,
        ],
        ErrorCode::InvalidRequestTotalAmount->value => [
            'exception' => InvalidRequestTotalAmountException::class,
            'status'    => 400,
        ],
        ErrorCode::OrderNotFound->value => [
            'exception' => OrderNotFoundExceprion::class,
            'status'    => 404,
        ]
    ];

    public static function fromResponse(Response $response, ?Throwable $previousException = null): QliroException
    {
        $responseContent = $response->body();
        $code = $previousException ? $previousException->getCode() : 0;

        if ($response->status() === 401) {
            return new AuthorizationException(
                "Check auth credentials, cannot authenticate.,",
                $code,
                $previousException
            );
        }
        // Try to decode the response content as JSON
        $content = json_decode($responseContent);

        // If the response is not valid JSON or doesn't have the expected structure,
        // return a generic exception with the raw response content
        if (json_last_error() !== JSON_ERROR_NONE || !isset($content->ErrorCode)) {
            return new self(
                "Invalid or unexpected response format: " . $responseContent,
                $code,
                $previousException
            );
        }

        $errorCode = $content->ErrorCode;
        $errorMessage = $content->ErrorMessage ?? 'No error message provided';
        $errorReference = $content->ErrorReference ?? 'No error reference provided';
        $fullErrorMessage = $errorMessage . '; Error ref: ' . $errorReference . '; Error code: ' . $errorCode;
        return self::createExceptionFromMapping($errorCode, $fullErrorMessage, $code, $previousException);
    }

    private static function createExceptionFromMapping(
        string     $errorCode,
        string     $message,
        int        $code,
        ?Throwable $previousException
    ): QliroException
    {
        if (isset(self::$errorCodeMapping[$errorCode])) {
            $mapping = self::$errorCodeMapping[$errorCode];
            $exceptionClass = $mapping['exception'];
            $statusCode = $mapping['status'];
            return new $exceptionClass($message, $statusCode, $previousException);
        }
        return new self($message, $code, $previousException);
    }

}
