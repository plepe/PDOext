PDOext
======
PDO (PHP Database Objects) extended - adds a few missing functions

Functions
=========
__construct($connection, $options)
---------------------
Alternatively to specifying a DSN, username and password as parameters you can specify connection parameters as array:

```php
array(
  'type' => 'mysql',
  'dbname' => 'database',
)
```

Optional connection parameters:
```php
  'username' => 'USER',
  'password' => 'PASSWORD',
  'host' => 'localhost',
  'port' => 3333, // MYSQL, PGSQL
  'unix_socket' => '/path/to/socket', // MYSQL
  'path' => '/path/to/db', // SQLite
```

quoteIdent($str)
----------------
PDOext::quoteIdent() quotes the input string, so it can be used as identifier in a database query.

Example:
```php
$column = 'data';
$db->query("select " . $db->quoteIdent($column) . " from my_table where id="  . $db_quote($id));
```

disableForeignKeyChecks()
-------------------------
Disables foreign key checks.

enableForeignKeyChecks()
-------------------------
(Re-)enables foreign key checks.

