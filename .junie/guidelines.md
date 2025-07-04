# Qliro API Client Developer Guidelines

## Project Overview
The Qliro API client is a PHP library that provides integration with Qliro's payment and order management services. It supports both the Merchant API (for order and payment operations) and Admin API (for user management and reporting).

## Tech Stack
- PHP 8.1+
- GuzzleHTTP 7.0+ for HTTP requests
- PHPUnit 10.0+ for testing
- Mockery 1.5+ for mocking in tests

## Directory Structure
```
qliro-api/
├── src/                      # Source code
│   ├── Api/                  # API implementation
│   │   ├── Config.php        # Configuration class
│   │   ├── QliroApi.php      # Main client class
│   │   ├── QliroConnector.php # API connector
│   │   ├── Requests/         # API request classes
│   │   ├── Resources/        # API resource classes
│   │   ├── Responses/        # API response classes
│   │   └── Services/         # Service implementations
│   │       ├── Merchant/     # Merchant API services
│   │       └── Admin/        # Admin API services
│   ├── Builders/             # DTO builders
│   ├── Dtos/                 # Data Transfer Objects
│   ├── Enums/                # Enumerations
│   ├── Exceptions/           # Exception classes
│   ├── Models/               # Domain models
│   ├── Services/             # Service classes
│   └── Traits/               # Shared traits
├── tests/                    # Test files
│   ├── Unit/                 # Unit tests (no API calls)
│   ├── Integration/          # Integration tests (may make API calls)
│   ├── QliroApiTestCase.php  # Base test case class
│   └── TestConfig.php        # Test configuration utilities
├── .junie/                   # Developer guidelines
├── composer.json             # Composer dependencies
├── phpunit.xml               # PHPUnit configuration
├── README.md                 # Project documentation
```

## Running Tests
To run all tests:
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

## Development Best Practices

### Code Organization
- Follow the service-based client approach as outlined in DESIGN.md
- Keep services focused on specific API areas (Merchant or Admin)
- Use models to represent data structures when appropriate

### Data Transfer Objects (DTOs)
- Use public properties instead of private properties with getters/setters
- DTOs should be simple data containers without complex logic
- Include a `toArray()` method to convert the DTO to an array for API requests
- Only include methods that provide additional functionality beyond simple property access
- Initialize properties with sensible default values when appropriate
- Use type hints for all properties to ensure type safety

### Adding New Features
1. Identify which service the feature belongs to
2. Implement the feature in the appropriate service class
3. Add unit tests in the `tests/Unit/` directory
4. Add integration tests in the `tests/Integration/` directory if needed

### Adding New Tests
- Unit tests should use Guzzle's MockHandler to simulate HTTP responses
- Integration tests can make actual API calls but should use `skipIfNoActualApiCalls()`
- Extend `QliroApiTestCase` instead of PHPUnit's TestCase
- Test both successful responses and error handling

### Error Handling
- Use appropriate exception classes from the Exceptions directory
- Catch and handle HTTP errors appropriately
- Provide meaningful error messages

### Code Comments
- Comments should be used only to describe why something is done, not how code works
- In most cases, no comments are needed for self-explanatory code
- Comments for generated code should be used only if necessary
- Focus on writing clear, self-documenting code rather than relying on comments

## Common Tasks
- **Creating a client**: `$client = new QliroApi($config);`
- **Getting an order**: `$order = $client->admin()->orders()->getOrderByMerchantReference($orderRef)->order;`
- **Capturing items**: 
  ```php
  $captures = new \Gets\QliroApi\Models\OrderCaptures();
  $captures->add('item-ref-1', 99, 1); // merchantReference, pricePerItem, quantity
  $dto = $order->buildCaptureDto($captures);
  $result = $client->admin()->orders()->markItemsAsShipped($dto)->dto;
  ```
- **Returning items**: 
  ```php
  $returns = new \Gets\QliroApi\Models\OrderReturns();
  $returns->add('item-ref-1', 75, 1); // merchantReference, pricePerItem, quantity
  $dto = $order->buildReturnDto($returns);
  $result = $client->admin()->orders()->returnItems($dto)->dto;
  ```
- **Updating items**: 
  ```php
  $updates = new \Gets\QliroApi\Models\OrderChanges();
  $updates->decrease('item-ref-1', 99, 1); // merchantReference, pricePerItem, quantity
  $dto = $order->buildUpdateDto($updates);
  $result = $client->admin()->orders()->updateItems($dto)->dto;
  ```
- **Retrying transactions**: 
  ```php
  $retryTransactions = new \Gets\QliroApi\Services\TransactionRetryService($client);
  $retryResults = $retryTransactions->processFailedTransactions($orderRef, $result->PaymentTransactions);
  ```

## Additional Resources
- See README.md for usage examples
- See tests/README.md for detailed testing information
