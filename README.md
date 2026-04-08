# QueryBuilder
Fast, lightweight, and simple SQL query builder that does not depend on any third-party library besides PDO to execute the queries in a safe way. The syntax is inspired by Laravel Query Builder.

## Features

- Automatic parameter binding via an internal bindings manager.
- Comprehensive WHERE builder: nested groups, `IN/NOT IN`, `LIKE/NOT LIKE`, `NULL/NOT NULL`, `BETWEEN/NOT BETWEEN`.
- Rich JOINs: `INNER`, `LEFT`, `RIGHT`, and `FULL` (only on dialects that support it, e.g., PostgreSQL) with nested conditions and `on` helpers.
- Pagination: `paginate` (length-aware) and `simplePaginate` (no total count) with page name support.
- Dialect-aware SQL generation with automatic detection (MySQL & Postgres for now).
- Inserts, updates, deletes with safety checks (no UPDATE/DELETE without WHERE).
- Upserts with dialect-specific behavior.
- Convenience helpers: `first`, `pluck`, `distinct`, `orderBy`, `limit`, `offset`, `raw` expressions, `objectConverter`.
- Results as a fluent `Collection` and paginator objects.

## Installation
Install via Composer:
```sh
composer require abdulelahragih/querybuilder
```

## Getting Started
Create a builder from a `PDO` instance. Dialect is auto-detected from `PDO::ATTR_DRIVER_NAME` (`pgsql` → Postgres; otherwise MySQL):
```php
use Abdulelahragih\QueryBuilder\QueryBuilder;

$pdo = /* your PDO connection */;
$qb = new QueryBuilder($pdo);

$rows = $qb->table('users')
    ->select('id', 'username', 'phone_number', 'gender')
    ->where('role_id', '=', 1)
    ->join('images', 'images.user_id', '=', 'users.id')
    ->orderBy('id', 'DESC')
    ->limit(10)
    ->get(); // returns Collection
```

You can also use the thin `DB` wrapper (instance-based) or the `DBSingleton` for a static facade if you prefer:
```php
use Abdulelahragih\QueryBuilder\DB;
use Abdulelahragih\QueryBuilder\DBSingleton;

// Instance wrapper
$db = new DB($pdo);
$users = $db->table('users')->select('id')->get();

// Static facade
DBSingleton::init($pdo);
$firstId = DBSingleton::table('users')->first('id');
```

## Pagination
Length-aware pagination returns totals and page info:
```php
$p = $qb->table('users')
    ->orderBy('id', 'DESC')
    ->paginate($page, $perPage, pageName: 'page');
// $p is LengthAwarePaginator (items + total, pages, prev/next)
```

Simple pagination is lighter weight:
```php
$p = $qb->table('users')->simplePaginate($page, $perPage);
// $p is SimplePaginator (items + prev/next)
```

## Inserts and Upserts
- Insert single or multiple rows:
```php
$qb->table('users')->insert(['id' => 100, 'name' => 'John']);
$qb->table('users')->insert([
    ['id' => 100, 'name' => 'John'],
    ['id' => 101, 'name' => 'Jane'],
]);
```

- Upsert with a unified API across dialects:
```php
use Abdulelahragih\QueryBuilder\Grammar\Expression;

// Update non-unique columns automatically when conflicts occur
$qb->table('users')->upsert(
    ['id' => 100, 'name' => 'John', 'age' => 26],
    uniqueBy: ['id'],
);

// Explicit assignments (MySQL uses VALUES(), Postgres uses EXCLUDED)
$qb->table('users')->upsert(
    ['id' => 100, 'name' => 'John', 'age' => 26],
    uniqueBy: ['id'],
    updateOnDuplicate: [
        'name',                   // infer from column name
        'updated_at' => Expression::make('NOW()'), // raw expression
    ],
);
```

- Do-nothing on conflict (Postgres):
```php
$qb->table('users')->insertOrIgnore(['id' => 100, 'name' => 'John'], uniqueColumns: ['id']);
```

- Insert and get the generated id (Postgres supports RETURNING, MySQL uses lastInsertId):
```php
$id = $qb->table('users')->insertGetId(['name' => 'John']);
// Optionally specify id column name
$id = $qb->table('users')->insertGetId(['name' => 'Jane'], 'user_id');
```

## Updates and Deletes
Both operations require a WHERE clause for safety and return the affected rows count:
```php
$updated = $qb->table('users')->where('id', '=', 1)->update(['name' => 'Sam']);
$deleted = $qb->table('users')->whereIn('id', [1, 2])->delete();
```

## WHERE and JOIN Builders
- WHERE helpers: `where`, `orWhere`, `whereIn`, `whereNotIn`, `whereLike`, `whereNotLike`, `whereNull`, `whereNotNull`, `whereBetween`, `whereNotBetween`, nested closures.
- JOIN helpers: `join`, `leftJoin`, `rightJoin`, `fullJoin`, plus nested `where`/`orWhere` inside join closures and `on`/`orOn` for column comparisons.

Examples (WHERE):
```php
// Basic where and multiple conditions (AND)
$qb->table('users')
   ->where('role_id', '=', 1)
   ->where('status', '=', 'active')
   ->toSql();

// OR conditions
$qb->table('users')
   ->where('role_id', '=', 1)
   ->orWhere('role_id', '=', 2)
   ->toSql();

// Nested groups
$qb->table('users')
   ->where('id', '=', 1)
   ->orWhere(function ($w) {
       $w->where('id', '=', 2)
         ->where(function ($inner) {
             $inner->where('name', '=', 'Sam')
                   ->orWhere('name', '=', 'John');
         });
   })
   ->toSql();

// IN / NOT IN
$qb->table('users')
   ->whereIn('id', [1, 2, 3])
   ->whereNotIn('status', ['banned'])
   ->toSql();

// LIKE / NOT LIKE
$qb->table('users')
   ->whereLike('name', '%Sam%')
   ->whereNotLike('email', '%@spam.com')
   ->toSql();

// NULL / NOT NULL
$qb->table('users')
   ->whereNull('deleted_at')
   ->whereNotNull('email')
   ->toSql();

// BETWEEN / NOT BETWEEN
$qb->table('orders')
   ->whereBetween('amount', 10, 100)
   ->whereNotBetween('discount', 20, 30)
   ->toSql();
```

Examples (JOIN):
```php
// Simple inner join with column-to-column comparison
$qb->table('users')
   ->join('images', 'images.user_id', '=', 'users.id')
   ->toSql();

// LEFT / RIGHT / FULL joins
$qb->table('users')
   ->leftJoin('orders', 'orders.user_id', '=', 'users.id')
   ->rightJoin('profiles', 'profiles.user_id', '=', 'users.id')
   ->toSql();

// Join with additional filters and nested groups
$qb->table('users')
   ->join('images', function ($j) {
       $j->on('images.user_id', '=', 'users.id');
       $j->orWhere('images.user_id', '=', 1);
       $j->where(function ($nested) {
           $nested->where('images.user_id', '=', 3);
           $nested->orWhere('images.id', '=', 1);
       });
   })
   ->toSql();

// Using orOn for alternative column matches
$qb->table('users')
   ->join('orders', function ($j) {
       $j->on('orders.user_id', '=', 'users.id')
         ->orOn('orders.alt_user_id', '=', 'users.id');
   })
   ->toSql();

// Multiple joins chained
$qb->table('users u')
   ->join('orders o', 'o.user_id', '=', 'u.id')
   ->join('comments c', 'c.user_id', '=', 'u.id')
   ->select('u.id', 'o.amount')
   ->toSql();
```

## Object Conversion
Map rows to custom objects after fetching:
```php
$users = $qb->table('users')
    ->objectConverter(fn(array $row) => (object) $row)
    ->get();
```

## Transactions (Using the `DB` Wrapper)
Let the builder handle transactions:
```php
use Abdulelahragih\QueryBuilder\DB;

$db = new DB($pdo);
$db->transaction(function () use ($db) {
    $db->table('users')->insert(['id' => 200, 'name' => 'Txn']);
});
```
Or manually:
```php
use Abdulelahragih\QueryBuilder\DB;

$db = new DB($pdo);
$db->beginTransaction();
try {
    $db->table('users')->insert(['id' => 200, 'name' => 'Txn']);
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
}
```

## Notes
- Update/Delete without a WHERE clause throws an exception by default.
- `insertGetId` expects a single row payload.
- Feature set varies slightly by dialect (e.g., Postgres `RETURNING`, `ON CONFLICT ... DO NOTHING`).

## Contribution
Contributions are welcome!
