# HelpDesk Kemlu - Testing Strategy

## Overview

This document outlines the testing strategy for the HelpDesk Kemlu system. The tests are designed to ensure code quality, catch bugs early, and support future development.

## Test Structure

```
tests/
├── TestCase.php                  # Enhanced base test case
├── DatabaseTestCase.php          # Base for database tests
├── Helpers/                      # Test helper classes
│   ├── AuthHelper.php           # Authentication utilities
│   └── TicketHelper.php         # Ticket creation utilities
└── Unit/                         # Unit tests
    └── Models/                  # Model tests
        ├── UserTest.php
        ├── TicketTest.php
        ├── TeknisiTest.php
        ├── AdminHelpdeskTest.php
        ├── NotificationTest.php
        └── AplikasiTest.php
```

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Unit Tests Only

```bash
php artisan test --testsuite=Unit
```

### Run Specific Test File

```bash
php artisan test tests/Unit/Models/UserTest.php
```

### Run with Coverage

```bash
php artisan test --coverage
```

## Test Infrastructure

### Base Test Cases

**TestCase.php** - Enhanced base test case with:

-   Authentication helpers for all 4 roles (`actingAsUser()`, `actingAsAdminHelpdesk()`, etc.)
-   Custom assertion methods
-   Utility methods for test data creation

**DatabaseTestCase.php** - For database tests:

-   Uses `RefreshDatabase` trait
-   Ensures clean database state for each test
-   Helper methods for seeding test data

### Test Helpers

**AuthHelper** - Quick user creation for all roles:

```php
AuthHelper::createUser();
AuthHelper::createAdminHelpdesk();
AuthHelper::createTeknisi();
AuthHelper::createAdminAplikasi();
```

**TicketHelper** - Create tickets in various states:

```php
TicketHelper::createOpenTicket();
TicketHelper::createAssignedTicket($teknisi);
TicketHelper::createResolvedTicket();
```

## Factories

### Available Factories

-   **TicketFactory** - With states: `open()`, `assigned()`, `inProgress()`, `resolved()`, `closed()`, `urgent()`
-   **TicketCommentFactory** - With states: `fromUser()`, `fromTeknisi()`, `internal()`
-   **NotificationFactory** - With states: `read()`, `unread()`, `forUser()`, `forTeknisi()`

### Usage Example

```php
// Create an urgent, open ticket
$ticket = Ticket::factory()->urgent()->open()->create();

// Create a resolved ticket assigned to a teknisi
$teknisi = Teknisi::factory()->create();
$ticket = Ticket::factory()->resolved()->create([
    'assigned_teknisi_nip' => $teknisi->nip,
]);
```

## Writing Tests

### Unit Test Example

```php
<?php

namespace Tests\Unit\Models;

use Tests\DatabaseTestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends DatabaseTestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_user()
    {
        $user = User::factory()->create([
            'nip' => '199012345678',
            'name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('users', [
            'nip' => '199012345678',
            'name' => 'John Doe',
        ]);
    }
}
```

## Current Test Coverage

### Unit Tests - Models (30+ tests)

-   ✅ UserTest (10 tests)
-   ✅ TicketTest (8 tests)
-   ✅ TeknisiTest (3 tests)
-   ✅ AdminHelpdeskTest (3 tests)
-   ✅ NotificationTest (3 tests)
-   ✅ AplikasiTest (3 tests)

## Best Practices

1. **Keep tests isolated** - Each test should be independent
2. **Use descriptive names** - Test names should explain what they test
3. **Follow AAA pattern** - Arrange, Act, Assert
4. **Use factories** - Don't create data manually
5. **Clean up** - Use RefreshDatabase to ensure clean state
6. **Test one thing** - Each test should verify one behavior

## Troubleshooting

### Common Issues

#### Database Connection Errors

**Solution**: Ensure `phpunit.xml` is configured correctly:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

#### Factory Not Found

**Solution**: Create the factory file in `database/factories/` and ensure it's properly namespaced.

#### RefreshDatabase Not Working

**Solution**: Ensure you're using the trait:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends DatabaseTestCase
{
    use RefreshDatabase;
}
```

## Continuous Integration

Tests should run automatically on every push. Example GitHub Actions workflow:

```yaml
name: Tests
on: [push, pull_request]
jobs:
    test:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v2
            - name: Install Dependencies
              run: composer install
            - name: Run Tests
              run: php artisan test
```

## Extending Tests

To add more tests:

1. Create test file in appropriate directory
2. Extend `DatabaseTestCase` for database tests
3. Use `RefreshDatabase` trait
4. Use factories for test data
5. Follow naming convention: `it_does_something` or `test_does_something`

## Summary

The testing infrastructure provides:

-   ✅ Clean test architecture
-   ✅ Comprehensive factories
-   ✅ Helper utilities
-   ✅ 30+ unit tests for models
-   ✅ Easy to extend

This foundation supports reliable, maintainable testing for the HelpDesk Kemlu system.
