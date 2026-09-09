# Run Instructions: QueryBuilder

## 1. Overview

**QueryBuilder** (`abdulelahragih/querybuilder`) is a fast, lightweight, zero-dependency SQL query builder for PHP 8.1+. The syntax is inspired by the Laravel Query Builder, providing an expressive and fluent interface for building SQL statements while relying strictly on native PHP PDO (`ext-pdo`) for secure, parameterized query execution.

### Architectural Highlights & Role
- **Type**: PHP Library / Composer Package (not a standalone web server or daemon).
- **Core Engine**: `Abdulelahragih\QueryBuilder\QueryBuilder` handles statement composition, binding parameters, and dispatching execution to PDO.
- **Dialect Abstraction**: Includes built-in support for dialect-specific syntax for **MySQL** (`MySqlDialect`) and **PostgreSQL** (`PostgresDialect`), with automatic dialect detection based on the active `PDO::ATTR_DRIVER_NAME`.
- **Grammar & Statements**: Modular clause abstractions (`SelectStatement`, `InsertStatement`, `UpdateStatement`, `DeleteStatement`, `WhereClause`, `JoinClause`, `OnConflictClause`).
- **Data Collections**: Query results are returned wrapped in custom fluent `Collection` objects (`Abdulelahragih\QueryBuilder\Data\Collection`) with helper methods (`map`, `filter`, `pluck`, `chunk`, `sorted`, etc.).
- **Pagination Support**: Provides both length-aware (`LengthAwarePaginator`) and lightweight simple pagination (`SimplePaginator`).
- **Facades & Helpers**: Includes an instance-based wrapper (`DB`) and a static facade (`DBSingleton`) with automatic transaction management.
- **Safety First**: Implements safeguards such as forbidding `UPDATE` or `DELETE` operations without a `WHERE` clause unless explicitly configured, throwing `QueryBuilderException`.

---

## 2. Prerequisites

### Runtime & Core Tools
- **PHP**: Version `^8.1` (tested with PHP 8.1 up to PHP 8.3+)
  - Verify with: `php -v`
- **PHP Extensions**:
  - `pdo` (Required core extension for runtime execution and parameter binding)
  - `pdo_sqlite` (Required for running the local PHPUnit test suite with in-memory SQLite)
  - `pdo_mysql` / `pdo_pgsql` (Optional runtime drivers when connecting consuming applications to MySQL or PostgreSQL)
  - Verify with: `php -m | grep -i pdo`
- **Composer**: Composer v2.x
  - Verify with: `composer -V`

### Database / External Services
- **Local Development & Testing**: No external database service (MySQL, PostgreSQL, etc.) is required to run the development test suite. Tests execute against an in-memory SQLite instance (`sqlite::memory:`).
- **Target Databases (Production / Staging / Integration)**:
  - MySQL 5.7+ / 8.0+
  - PostgreSQL 12+

---

## 3. Environment & Configuration

Because **QueryBuilder** is an embeddable PHP library rather than a standalone application, it does not rely on `.env` files or external secret managers.

### Configuration Model
- Database connections are configured programmatically at runtime by the consuming host application by passing an initialized `\PDO` instance to `new QueryBuilder($pdo)` or `DBSingleton::init($pdo)`.
- Test configuration is governed by [`phpunit.xml`](phpunit.xml), which defines the test suite paths, source code coverage directories, and sets `PHPUNIT_TEST_SUITE=1`.

---

## 4. Installation & Setup

### For Local Development and Contributing

1. **Clone the repository and enter the directory**:
   ```bash
   git clone <repo-url> QueryBuilder
   cd QueryBuilder
   ```

2. **Validate Composer file integrity**:
   ```bash
   composer validate
   ```

3. **Install development dependencies**:
   ```bash
   composer install
   ```

### For Consuming Projects

To use this library inside another PHP project, add it via Composer:

```bash
composer require abdulelahragih/querybuilder
```

---

## 5. Running the Application

As a library, QueryBuilder does not expose an HTTP server or long-running daemon. It is consumed within PHP scripts, CLI commands, or web applications.

### Quick Verification via PHP CLI

You can verify that the library and Composer autoloading work properly using an interactive or one-line PHP script with an in-memory SQLite database:

```bash
php -r '
require __DIR__ . "/vendor/autoload.php";

use Abdulelahragih\QueryBuilder\QueryBuilder;

// Create an in-memory SQLite PDO instance
$pdo = new PDO("sqlite::memory:");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create sample schema
$pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT);");

// Initialize QueryBuilder
$qb = new QueryBuilder($pdo);

// Insert records
$qb->table("users")->insert([
    ["id" => 1, "name" => "Alice"],
    ["id" => 2, "name" => "Bob"]
]);

// Query records
$users = $qb->table("users")->where("id", "=", 1)->get();
echo "Found user: " . $users->first()["name"] . PHP_EOL;
'
```

### Usage Examples in Application Code

#### 1. Basic Querying with `QueryBuilder`
```php
use Abdulelahragih\QueryBuilder\QueryBuilder;

$pdo = new PDO("mysql:host=127.0.0.1;dbname=app_db;charset=utf8mb4", "user", "password");
$qb = new QueryBuilder($pdo);

// Select query
$users = $qb->table("users")
    ->select("id", "name", "email")
    ->where("status", "=", "active")
    ->orderBy("created_at", "DESC")
    ->limit(10)
    ->get(); // Returns Abdulelahragih\QueryBuilder\Data\Collection
```

#### 2. Using the Static Facade (`DBSingleton`)
```php
use Abdulelahragih\QueryBuilder\DBSingleton;

DBSingleton::init($pdo);

$activeCount = DBSingleton::table("users")
    ->where("active", "=", 1)
    ->count();

$user = DBSingleton::table("users")->where("id", "=", 10)->first();
```

#### 3. Upsert Operations (Dialect-Aware)
```php
use Abdulelahragih\QueryBuilder\Grammar\Expression;

// Upsert supports MySQL ON DUPLICATE KEY UPDATE and PostgreSQL ON CONFLICT DO UPDATE
$qb->table("users")->upsert(
    ["id" => 100, "name" => "John", "age" => 26],
    uniqueBy: ["id"],
    updateOnDuplicate: [
        "name",
        "updated_at" => Expression::make("NOW()"),
    ]
);
```

#### 4. Pagination
```php
// Length-aware paginator (calculates total count and pages)
$paginator = $qb->table("users")
    ->where("role", "=", "member")
    ->paginate(page: 1, perPage: 15);

$total = $paginator->total();
$items = $paginator->items();
$hasMore = $paginator->hasMorePages();
```

---

## 6. Testing & Verification

### Running Automated Unit Tests

QueryBuilder comes with a comprehensive PHPUnit test suite validating SQL compilation, dialect nuances, edge cases, collections, pagination, and error handling.

1. **Run standard test suite**:
   ```bash
   composer test
   ```

2. **Run all tests (via Composer alias)**:
   ```bash
   composer test:all
   ```

3. **Run directly using PHPUnit binary**:
   ```bash
   ./vendor/bin/phpunit
   ```

4. **Run a specific test suite or test case**:
   ```bash
   ./vendor/bin/phpunit tests/MysqlDialectTest.php
   ./vendor/bin/phpunit tests/PostgresQueryBuilderTest.php
   ./vendor/bin/phpunit --filter testSimpleSelect
   ```

### Expected Output
All 205+ tests should pass with 0 failures and 0 errors:
```text
OK (205 tests, 365 assertions)
```

---

## 7. Troubleshooting & FAQ

### 1. `Class "PDO" not found` or driver missing
- **Cause**: PHP was compiled or installed without PDO extensions enabled.
- **Resolution**:
  - macOS (Homebrew): `brew install php` (includes default PDO extensions).
  - Ubuntu/Debian: `sudo apt-get install php-pdo php-sqlite3 php-mysql php-pgsql`
  - Ensure extension lines (`extension=pdo`, `extension=pdo_sqlite`) are uncommented in `php.ini`.

### 2. `QueryBuilderException: UPDATE or DELETE without WHERE clause is not allowed`
- **Cause**: An `update()` or `delete()` operation was invoked without defining condition clauses. This is an intentional safeguard to prevent accidental data truncation.
- **Resolution**: Ensure you chain a `.where(...)` condition before calling `update()` or `delete()`.

### 3. `InvalidArgumentException: Upsert is not supported by this dialect`
- **Cause**: Using an unsupported database dialect with `upsert()` or `insertOrIgnore()`.
- **Resolution**: Upserts currently support MySQL (`MySqlDialect`) and PostgreSQL (`PostgresDialect`). Ensure your PDO connection driver is either `mysql` or `pgsql`, or supply the dialect explicitly:
  ```php
  use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;

  $qb = new QueryBuilder($pdo, new PostgresDialect());
  ```

### 4. Tests fail with cache error
- **Cause**: PHPUnit cache directory write permission issues.
- **Resolution**: Run `composer test` which passes `--do-not-cache-result`, or delete the `.phpunit.cache` directory:
  ```bash
  rm -rf .phpunit.cache .phpunit.result.cache
  ```
