# QueryBuilder
Fast, lightweight, and simple SQL query builder that does not depend on any third-party library besides PDO to execute the queries in a safe way. The syntax is inspired by Laravel Query Builder.

## Features

- Internal bindings manager, so you do not have to worry about binding your values.
- Support adding multiple and nested conditions to the Where and the Join clauses.
- Support Pagination.
- Dialect-aware SQL generation.
## Installation
The recommended way to install the QueryBuilder is through [Composer](http://getcomposer.org). 
```sh
composer require abdulelahragih/querybuilder
```

## Basic Usage
To start using it you have to first create an instance of the QueryBuilder:
```php
$pdo = # your pdo connection
$qb = new \Abdulelahragih\QueryBuilder\QueryBuilder($pdo)
# Now you can start using it 
$result = $qb->table('users')
   ->select('id', 'username', 'phone_number', 'gender')
   ->where('role_id', '=', 1)
   ->join('images', 'images.user_id', '=', 'users.id')
   ->get();
```

## Select with pagination
You can either use the `paginate` method or the `simplePaginate` method. 

`paginate` will return a `LengthAwarePaginator` instance which contains the total number of items, the current page, the number of items per page, the total number of pages, and the number of next and previous pages. <br>
```php
$paginator = $qb->table('users')
   ->select('id', 'username', 'phone_number', 'gender')
   ->where('role_id', '=', 1)
   ->paginate($page, $limit);
```
`simplePaginate` will return a `Paginator` instance which contains the current page, the number of items per page, and the number of next and previous pages. <br>
```php
$paginator = $qb->table('users')
   ->select('id', 'username', 'phone_number', 'gender')
   ->where('role_id', '=', 1)
   ->simplePaginate($page, $limit);
```

## PostgreSQL upserts

When you work against PostgreSQL you can configure the builder with the `PostgresDialect` and use the fluent helpers to generate `ON CONFLICT` clauses:

```php
use Abdulelahragih\QueryBuilder\Grammar\Dialects\PostgresDialect;
use Abdulelahragih\QueryBuilder\Grammar\Expression;

$qb = new \Abdulelahragih\QueryBuilder\QueryBuilder($pdo, new PostgresDialect());

// DO NOTHING
$qb->table('users')
   ->onConflictDoNothing('id')
   ->insert(['id' => 42, 'name' => 'Douglas']);

// DO UPDATE
$qb->table('users')
   ->onConflictDoUpdate('id', ['name' => Expression::make('EXCLUDED.name')])
   ->insert(['id' => 42, 'name' => 'Douglas']);
```

MySQL continues to support `ON DUPLICATE KEY UPDATE` through the same `upsert` API.

When you omit the dialect, the builder inspects `PDO::ATTR_DRIVER_NAME` and automatically selects `PostgresDialect` for `pgsql` connections, falling back to MySQL-style quoting for everything else.

## Nested Where
You can add nested conditions to the Where clause by passing a closure to the `where` method. <br>
```php
$result = $qb->table('users')
   ->select('id', 'username', 'phone_number', 'gender')
   ->where(function ($builder) {
       $builder->where('role_id', '=', 1)
           ->orWhere('role_id', '=', 2); 
   })
   ->get();
```
## Nested Join
You can add nested conditions to the Join clause by passing a closure to the `join` method. <br>
```php
$result = $qb->table('users')
    ->join('images', function (JoinClauseBuilder $builder) {
        $builder->on('images.user_id', '=', 'users.id');
        // you can use all where variants here
        $builder->where('images.user_id', '=', 1);
    })
    ->get();
```
## TODOs
- [x] ~~Support Update, Delete, Insert~~
- [ ] Support Creating Schemas
- [x] ~~Add pluck method~~
- [ ] Add support for sub-queries inside the Where and Join clauses
- [x] ~~Implement a Collection class and make it the return type of get()~~
- [ ] Add a `returning` method to the query allowing you to return columns of inserted/updated row(s)
- [ ] Add support for different types of databases and refactor code, so it is easy to do so
- [x] ~~Add support for Transactions~~

## Contribution
Any contribution to make this project better is welcome
