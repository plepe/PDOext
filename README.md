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
