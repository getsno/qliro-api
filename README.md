# Qliro API Client

A PHP client for the Qliro API, built using the [Saloon PHP package](https://github.com/saloonphp/saloon).

## Project Overview

The Qliro API client is a PHP library that provides integration with Qliro's payment and order management services. It supports both the Merchant API (for order and payment operations) and Admin API (for user management and reporting).

## Tech Stack

- PHP 8.1+
- GuzzleHTTP 7.0+ for HTTP requests
- PHPUnit 10.0+ for testing
- Mockery 1.5+ for mocking in tests
- Saloon PHP package for API interactions

## Installation
Add repo to composer.json:

    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/getsno/qliro-api.git"
        }
    ]
```bash
composer require getsno/qliro-api
```

## Usage

### Basic Usage

```php
use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Api\QliroApi;

// Create a configuration
$config = new Config('your-api-key', 'your-api-secret', 'dev'); // Use 'prod' for production

// Create the API client
$client = new QliroApi($config);

// Use the client
$order = $client->admin()->orders()->getOrderByMerchantReference('order-ref-123')->order;
```

### Admin API

The Admin API provides methods for administrative operations:

```php
// Get an order by merchant reference
$orderRef = 'order-ref-123';
$order = $client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;

// Get an order by ID
$orderId = 123;
$order = $client->admin()->orders()->getOrder($orderId)->order;

// Cancel an order
$result = $client->admin()->orders()->cancelOrder($orderId)->dto;

// Mark items as shipped (capture)
$orderRef = 'order-ref-123';
$order = $client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
$captures = new \Gets\QliroApi\Models\OrderCaptures();
$captures->add('item-ref-1', 99, 1); // merchantReference, pricePerItem, quantity
$captures->add('item-ref-2', 200, 1);
$dto = $order->buildCaptureDto($captures);
$result = $client->admin()->orders()->markItemsAsShipped($dto)->dto;

// Return items (refund)
$orderRef = 'order-ref-123';
$order = $client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
$returns = new \Gets\QliroApi\Models\OrderReturns();
$returns->add('item-ref-1', 75, 1); // merchantReference, pricePerItem, quantity
$returns->add('item-ref-2', 75, 1);
$dto = $order->buildReturnDto($returns);
$result = $client->admin()->orders()->returnItems($dto)->dto;

// Update order items
$orderRef = 'order-ref-123';
$order = $client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;
$updates = new \Gets\QliroApi\Models\OrderChanges();
$updates->decrease('item-ref-1', 99, 1); // merchantReference, pricePerItem, quantity
$updates->decrease('item-ref-2', 25, 1);
$dto = $order->buildUpdateDto($updates);
if (empty($dto->Updates)) {
    $result = $client->admin()->orders()->cancelOrder($dto->OrderId)->dto;
} else {
    $result = $client->admin()->orders()->updateItems($dto)->dto;
}

// Retry failed transactions
$retryTransactions = new \Gets\QliroApi\Services\TransactionRetryService($client);
$retryResults = $retryTransactions->processFailedTransactions($orderRef, $result->PaymentTransactions);
```

### Merchant API

The Merchant API provides methods for managing orders:

```php
// Get an order by ID
$orderId = 'order-123';
$order = $client->merchant()->orders()->getOrder($orderId);

// Get an order by merchant reference
$merchantRef = 'ref-123';
$order = $client->merchant()->orders()->getOrderByMerchantReference($merchantRef);

// Create a new order
$orderData = [
    // ... order data
];
$newOrder = $client->merchant()->orders()->createOrder($orderData);

// Update an existing order
$orderId = 'order-123';
$orderData = [
    // ... order data to update
];
$updated = $client->merchant()->orders()->updateOrder($orderId, $orderData);
```

### Working with Order Models

The API returns Order models that provide helpful methods for working with orders:

```php
// Get order details
$orderRef = 'order-ref-123';
$order = $client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;

// Access order properties
$orderId = $order->orderId();
$merchantRef = $order->merchantReference();
$country = $order->country();
$currency = $order->currency();

// Access order amounts
$originalAmount = $order->amountOriginal();
$capturedAmount = $order->amountCaptured();
$refundedAmount = $order->amountRefunded();
$cancelledAmount = $order->amountCancelled();
$remainingAmount = $order->amountRemaining();
$totalAmount = $order->amountTotal();

// Access order items
$currentItems = $order->itemsCurrent();
$reservedItems = $order->itemsReserved();
$cancelledItems = $order->itemsCancelled();
$refundedItems = $order->itemsRefunded();
$capturedItems = $order->itemsCaptured();
$eligibleForRefundItems = $order->itemsEligableForRefund();
```

## Error Handling

The client throws exceptions for API errors. You can catch these exceptions to handle errors:

```php
use Gets\QliroApi\Exceptions\QliroException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

try {
    $order = $client->admin()->orders()->getOrderByMerchantReference('non-existent-order');
} catch (QliroException $e) {
    // Handle Qliro-specific errors
    echo $e->getMessage();
} catch (RequestException $e) {
    // Handle request errors (e.g., 4xx responses)
    echo $e->getMessage();
} catch (FatalRequestException $e) {
    // Handle fatal request errors (e.g., connection issues)
    echo $e->getMessage();
}
```

## Transaction Retry Service

The library includes a service for retrying failed transactions:

```php
use Gets\QliroApi\Services\TransactionRetryService;

// Create the retry service
$retryService = new TransactionRetryService($client);

// Configure the retry service (optional)
$retryService->setMaxRetries(5);
$retryService->setBackoffEnabled(true);

// Process failed transactions
try {
    $results = $retryService->processFailedTransactions($orderRef, $paymentTransactions);

    // $results contains information about each retry attempt
    foreach ($results as $result) {
        echo "Transaction {$result['transaction_id']}: {$result['status']}\n";
    }
} catch (QliroException $e) {
    // Handle retry failures
    echo "Retry failed: " . $e->getMessage();
}
```

## Development

### Running Tests

```bash
vendor/bin/phpunit
```

To skip tests that make actual API calls:

```bash
SKIP_ACTUAL_API_CALLS=true vendor/bin/phpunit
```

To run tests with actual API credentials:
1. Copy `tests/config.php.example` to `tests/config.php`
2. Update `config.php` with your API credentials
3. Run tests normally

Alternatively, provide credentials via environment variables:
```bash
QLIRO_API_KEY=your_api_key QLIRO_API_SECRET=your_api_secret vendor/bin/phpunit
```

### Mock Client for Testing

You can use Saloon's mock client for testing:

```php
use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Api\QliroApi;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

// Create a mock client
$mockClient = new MockClient([
    // Mock responses for specific requests
    '*' => MockResponse::make(['status' => 'success'], 200)
]);

// Create the API client with the mock
$config = new Config('test-key', 'test-secret', 'dev');
$client = new QliroApi($config);
$client->withMockClient($mockClient);

// Now all requests will use the mock client
$response = $client->admin()->orders()->getOrderByMerchantReference('order-ref-123');
```

## License

MIT
