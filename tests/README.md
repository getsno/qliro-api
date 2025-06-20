# Qliro API Tests

This directory contains tests for the Qliro API client.

## Running Tests

To run the tests, navigate to the package root directory and run:

```bash
vendor/bin/phpunit
```

## Test Structure

The tests are organized into the following directories:

- `Unit/`: Contains unit tests that don't make actual API calls
  - `ConfigTest.php`: Tests for the Config class
  - `QliroApiTest.php`: Tests for the QliroApi class, including tests for the Saloon implementation of the API client
  - `QliroExceptionTest.php`: Tests for the QliroException class
- `Integration/`: Contains integration tests that may make actual API calls
  - `MerchantApiTest.php`: Tests for the Merchant API, including tests with actual API calls to the dev environment

Additionally, there are some utility files:

- `QliroApiTestCase.php`: Base test case class that provides helper methods for all tests
- `TestConfig.php`: Utility class for loading API credentials

## Test Approach

Most tests use PHPUnit and Saloon's MockClient to simulate HTTP responses without making actual HTTP requests. This allows for testing both successful responses and error handling.

Some tests, like those in `MerchantApiTest.php`, can make actual API calls to the dev environment to verify that the integration works correctly. These tests require valid API credentials.

## Running Tests with Actual API Credentials

To run tests that make actual API calls, you need to provide your API credentials. There are two ways to do this:

1. **Environment Variables**:
   ```bash
   QLIRO_API_KEY=your_api_key QLIRO_API_SECRET=your_api_secret vendor/bin/phpunit
   ```

2. **Configuration File**:
   - Copy `config.php.example` to `config.php`
   - Update `config.php` with your API credentials
   - Run the tests normally

If you want to skip tests that make actual API calls, you can set the `SKIP_ACTUAL_API_CALLS` environment variable:

```bash
SKIP_ACTUAL_API_CALLS=true vendor/bin/phpunit
```

## Adding New Tests

When adding new tests, follow these guidelines:

1. Decide whether your test is a unit test (no actual API calls) or an integration test (may make actual API calls)
2. Create a new test class in the appropriate directory (`Unit/` or `Integration/`)
3. Use the correct namespace:
   - For unit tests: `namespace Gets\QliroApi\Tests\Unit;`
   - For integration tests: `namespace Gets\QliroApi\Tests\Integration;`
4. Extend `QliroApiTestCase` instead of `PHPUnit\Framework\TestCase`:
   ```php
   use Gets\QliroApi\Tests\QliroApiTestCase;

   class YourTest extends QliroApiTestCase
   ```
5. Use Saloon's MockClient to simulate HTTP responses for unit tests
6. Use reflection to access private methods if necessary
7. Test both successful responses and error handling

### Adding Tests with Actual API Calls

If you need to add tests that make actual API calls:

1. Place your test in the `Integration/` directory
2. Use the `skipIfNoActualApiCalls()` helper method to skip the test when needed:
   ```php
   public function testSomethingWithActualApiCalls(): void
   {
       $this->skipIfNoActualApiCalls();

       // Test code that makes actual API calls
   }
   ```

3. Use the `getApiConfig()` helper method to get a Config object with API credentials:
   ```php
   $config = $this->getApiConfig('dev');
   ```

4. Add clear documentation in the test method about the required credentials.
