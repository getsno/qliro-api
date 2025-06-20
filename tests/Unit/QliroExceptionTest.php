<?php

namespace Gets\QliroApi\Tests\Unit;

use Gets\QliroApi\Exceptions\AuthorizationException;
use Gets\QliroApi\Exceptions\InvalidInputException;
use Gets\QliroApi\Exceptions\MerchantUrlNotSetException;
use Gets\QliroApi\Exceptions\QliroException;
use Gets\QliroApi\Tests\QliroApiTestCase;
use Saloon\Http\Response;

class QliroExceptionTest extends QliroApiTestCase
{
    public function testFromResponseWith401Status(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(401);
        $response->method('body')->willReturn('');
        $exception = QliroException::fromResponse($response);
        $this->assertInstanceOf(AuthorizationException::class, $exception);
        $this->assertStringContainsString('Check auth credentials', $exception->getMessage());
    }

    public function testFromResponseWithInvalidJson(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(400);
        $response->method('body')->willReturn('Not a JSON');
        $exception = QliroException::fromResponse($response);
        $this->assertStringContainsString('Invalid or unexpected response format', $exception->getMessage());
    }

    public function testFromResponseWithMissingErrorCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(400);
        $response->method('body')->willReturn('{"message": "Error message"}');
        $exception = QliroException::fromResponse($response);
        $this->assertStringContainsString('Invalid or unexpected response format', $exception->getMessage());
    }

    public function testFromResponseWithInvalidInputErrorCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(400);
        $response->method('body')->willReturn('{"ErrorCode": "INVALID_INPUT", "ErrorMessage": "Invalid input", "ErrorReference": "REF123"}');
        $exception = QliroException::fromResponse($response);
        $this->assertInstanceOf(InvalidInputException::class, $exception);

        $this->assertStringContainsString('Invalid input', $exception->getMessage());
        $this->assertStringContainsString('REF123', $exception->getMessage());
        $this->assertStringContainsString('INVALID_INPUT', $exception->getMessage());

        $this->assertEquals(422, $exception->getCode());
    }

    public function testFromResponseWithMerchantUrlNotSetErrorCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(400);
        $response->method('body')->willReturn('{"ErrorCode": "MERCHANT_URL_NOT_SET", "ErrorMessage": "Merchant URL not set", "ErrorReference": "REF456"}');
        $exception = QliroException::fromResponse($response);
        $this->assertInstanceOf(MerchantUrlNotSetException::class, $exception);

        $this->assertStringContainsString('Merchant URL not set', $exception->getMessage());
        $this->assertStringContainsString('REF456', $exception->getMessage());
        $this->assertStringContainsString('MERCHANT_URL_NOT_SET', $exception->getMessage());

        $this->assertEquals(422, $exception->getCode());
    }

    public function testFromResponseWithUnauthorizedErrorCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(403);
        $response->method('body')->willReturn('{"ErrorCode": "UNAUTHORIZED", "ErrorMessage": "Unauthorized", "ErrorReference": "REF789"}');
        $exception = QliroException::fromResponse($response);
        $this->assertInstanceOf(AuthorizationException::class, $exception);

        $this->assertStringContainsString('Unauthorized', $exception->getMessage());
        $this->assertStringContainsString('REF789', $exception->getMessage());
        $this->assertStringContainsString('UNAUTHORIZED', $exception->getMessage());

        $this->assertEquals(403, $exception->getCode());
    }

    public function testFromResponseWithForbiddenErrorCode(): void
    {
        $response = $this->createMock(Response::class);
        $response->method('status')->willReturn(403);
        $response->method('body')->willReturn('{"ErrorCode": "FORBIDDEN", "ErrorMessage": "Forbidden", "ErrorReference": "REF101"}');
        $exception = QliroException::fromResponse($response);

        $this->assertInstanceOf(AuthorizationException::class, $exception);

        $this->assertStringContainsString('Forbidden', $exception->getMessage());
        $this->assertStringContainsString('REF101', $exception->getMessage());
        $this->assertStringContainsString('FORBIDDEN', $exception->getMessage());

        $this->assertEquals(403, $exception->getCode());
    }

    public function testFromResponseWithUnknownErrorCode(): void
    {
        $response = $this->createMock(Response::class);

        $response->method('status')->willReturn(400);
        $response->method('body')->willReturn('{"ErrorCode": "UNKNOWN_ERROR", "ErrorMessage": "Unknown error", "ErrorReference": "REF202"}');
        $exception = QliroException::fromResponse($response);

        $this->assertStringContainsString('Unknown error', $exception->getMessage());
        $this->assertStringContainsString('REF202', $exception->getMessage());
        $this->assertStringContainsString('UNKNOWN_ERROR', $exception->getMessage());

        $this->assertEquals(0, $exception->getCode());
    }

    public function testFromResponseWithPreviousException(): void
    {
        $previousException = new \Exception('Previous exception', 123);

        $response = $this->createMock(Response::class);

        $response->method('status')->willReturn(400);
        $response->method('body')->willReturn('{"ErrorCode": "UNKNOWN_ERROR", "ErrorMessage": "Unknown error", "ErrorReference": "REF303"}');
        $exception = QliroException::fromResponse($response, $previousException);
        // Assert that the exception message contains the expected text
        $this->assertStringContainsString('Unknown error', $exception->getMessage());
        $this->assertEquals(123, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }
}
