# QueryBuilder
Fast, lightweight, and simple SQL query builder that does not depend on any third-party library besides PDO to execute the queries in a safe way. The syntax is inspired by Laravel Query Builder.

# Important
Do not use it in production as it is still in beta and many public APIs might change.
## Features

- Internal bindings manager, so you do not have to worry about binding your values.
- Support adding multiple and nested conditions to the Where and the Join clauses.
- Support Pagination.
## Installation
The recommended way to install the QueryBuilder is through [Composer](http://getcomposer.org). 
```sh
composer require abdulelahragih/querybuilder
```

## Usage
To start using it you have to first create an instance of the QueryBuilder:
```php
$pdo = # your pdo connection
$qb = new \Abdulelahragih\QueryBuilder\QueryBuilder($pdo)
# Now you can start using it 
$result = $qb->table('users')
   ->select('id', 'username', 'phone_number', 'gender')
   ->where('role_id', '=', 1)
   ->get() # This will return the result as an array
```
## TODOs
- [ ] Support Update, Delete, Insert, and Creating Schemas
- [ ] Add pluck method
- [ ] Add support for sub-queries inside the Where and Join clauses
- [ ] Implement a Collection class and make it the return type of get()
- [ ] Add a `returning` method to the query allowing you to return columns of inserted/updated row(s)
- [ ] Add support for different types of databases and refactor code, so it is easy to do so
- [ ] Add support for Transactions

## Contribution
Any contribution to make this project better is welcome
