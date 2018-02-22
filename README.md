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

Additional options:
```php
'debug' => 0 or 1 or 2
```

* debug: when debug is 0 don't generate debug messages; when debug is 1 print a HTML comment with all DB queries; when debug is 2 generate a debug message for each DB query (see modulekit-base:messages:messages_debug()).

quoteIdent($str)
----------------
PDOext::quoteIdent() quotes the input string, so it can be used as identifier in a database query.

Example:
```php
$column = 'data';
$db->query("select " . $db->quoteIdent($column) . " from my_table where id="  . $db_quote($id));
```

quote($str, [$parameter_type])
------------------------------
Like PDO::quote(), but returns NULL when $str === null.

disableForeignKeyChecks()
-------------------------
Disables foreign key checks.

enableForeignKeyChecks()
-------------------------
(Re-)enables foreign key checks.

tableExists($id)
---------------
Checks if the given table exists and returns true if it does. False, if it doesn't.

tables()
--------
Returns the list of tables in the current database, e.g.
```json
[ 'users', 'messages' ]
```

columns($table)
---------------
Returns the list of columns for the table in the current database, with the column id as keys and meta data as values, e.g.:
```json
{
  "id": {
    "type": "integer" OR "bigint(20) unsigned",
    "notnull": true/false,
    "default": null/"0",
    "key": true|PRI|false,
    "extra": "auto_increment"
  },
  "...."
}
```
