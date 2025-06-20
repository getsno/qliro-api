# Qliro API Integration Design

## Overview

This document outlines the design for integrating with the Qliro API, which is split into two main components:
1. Merchant API - For merchant-specific operations
2. Admin API - For administrative operations

## Design Considerations

When designing an API client, there are several approaches to consider:

1. **Monolithic Client**: All API methods are implemented directly in the main client class.
   - Pros: Simple, everything in one place
   - Cons: Can become large and unwieldy, harder to maintain, less separation of concerns

2. **Service-based Client**: The main client is a thin wrapper that provides access to service objects for different API areas.
   - Pros: Better separation of concerns, more maintainable, easier to extend
   - Cons: Slightly more complex architecture

3. **Resource-based Client**: Similar to service-based, but organized around resources rather than services.
   - Pros: Maps well to REST APIs, intuitive organization
   - Cons: May not map well to all API designs

## Recommended Design

For the Qliro API integration, I recommend a **service-based client** approach with the following structure:

```
src/
├── Api/
│   ├── Config.php                  # Configuration class (existing)
│   ├── QliroClient.php             # Main client class (existing)
│   ├── Services/                   # Services directory
│   │   ├── AbstractService.php     # Base service class
│   │   ├── Merchant/               # Merchant API services
│   │   │   ├── OrderService.php    # Order-related operations
│   │   │   ├── PaymentService.php  # Payment-related operations
│   │   │   └── ...                 # Other merchant services
│   │   └── Admin/                  # Admin API services
│   │       ├── UserService.php     # User management operations
│   │       ├── ReportService.php   # Reporting operations
│   │       └── ...                 # Other admin services
│   └── Models/                     # Data models
│       ├── Order.php               # Order model
│       ├── Payment.php             # Payment model
│       └── ...                     # Other models
└── Exceptions/
    ├── QliroException.php          # Base exception class (existing)
    └── ...                         # Other exception classes
```

## Implementation Approach

1. **QliroClient**: The main entry point that provides access to services.
   - Handles authentication, configuration, and HTTP requests
   - Provides methods to access different services

2. **AbstractService**: Base class for all services.
   - Holds a reference to the QliroClient
   - Provides common functionality for services

3. **Service Classes**: Implement specific API operations.
   - Organized by API area (Merchant/Admin) and functionality
   - Use the QliroClient for HTTP requests

4. **Model Classes**: Represent data structures used by the API.
   - Provide type safety and validation
   - Can be serialized to/from JSON

## Usage Example

```php
// Create a config with API credentials
$config = new Config('your_api_key', 'your_api_secret', 'dev');

// Create the main client
$client = new QliroClient($config);

// Access merchant services
$order = $client->merchant()->order()->getOrder('order-123');
$payment = $client->merchant()->payment()->createPayment($orderData);

// Access admin services
$users = $client->admin()->user()->listUsers();
$report = $client->admin()->report()->generateReport($criteria);
```

This design provides a clean, intuitive API that separates concerns and is easy to extend as the Qliro API evolves.
