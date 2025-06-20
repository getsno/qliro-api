# Qliro API Client

A PHP client for the Qliro API, built using the [Saloon PHP package](https://github.com/saloonphp/saloon).

## Installation

```bash
composer require getsno/qliro-api
```

## Usage

### Basic Usage

```php
use Gets\QliroApi\Api\Config;
use Gets\QliroApi\Saloon\QliroApi;

// Create a configuration
$config = new Config('your-api-key', 'your-api-secret', 'dev'); // Use 'prod' for production

// Create the API client
$qliroApi = new QliroApi($config);

// Use the client
$order = $qliroApi->merchantOrders()->getOrder('order-123');
```

### Merchant API

The Merchant API provides methods for managing orders:

```php
// Get an order by ID
$order = $qliroApi->merchantOrders()->getOrder('order-123');

// Get an order by merchant reference
$order = $qliroApi->merchantOrders()->getOrderByMerchantReference('ref-123');

// Create a new order
$orderData = [
    'MerchantApiKey' => 'your-api-key', // Optional, will be added automatically if not provided
    // ... other order data
];
$newOrder = $qliroApi->merchantOrders()->createOrder($orderData);

// Update an existing order
$orderData = [
    // ... order data to update
];
$updated = $qliroApi->merchantOrders()->updateOrder('order-123', $orderData);
```

### Admin API

The Admin API provides methods for administrative operations:

```php
// Get an order by ID
$order = $qliroApi->adminOrders()->getOrder('order-123');

// Cancel an order
$result = $qliroApi->adminOrders()->cancelOrder('order-123');

// Add items to an invoice
$itemsData = [
    // ... items data
];
$result = $qliroApi->adminOrders()->addOrderItems($itemsData);
```

### Working with Responses

All API methods return a Saloon `Response` object, which provides methods for working with the response:

```php
// Get the response as an object
$order = $qliroApi->merchantOrders()->getOrder('order-123')->json();

// Get the response status code
$statusCode = $qliroApi->merchantOrders()->getOrder('order-123')->status();

// Check if the request was successful
$isSuccessful = $qliroApi->merchantOrders()->getOrder('order-123')->successful();
```

## Error Handling

The client throws exceptions for API errors. You can catch these exceptions to handle errors:

```php
use Gets\QliroApi\Exceptions\QliroException;

try {
    $order = $qliroApi->merchantOrders()->getOrder('non-existent-order');
} catch (QliroException $e) {
    // Handle the error
    echo $e->getMessage();
}
```

## Development

### Running Tests

```bash
vendor/bin/phpunit
```

## License

MIT
