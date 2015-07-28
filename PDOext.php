<?php
class PDOext extends PDO {
  function __construct($dsn, $username=null, $password=null, $options=array()) {
    if(is_array($dsn)) {
      $_dsn = array();

      if(!array_key_exists('type', $dsn)) {
	trigger_error("PDOext::__construct(): type not specified", E_USER_ERROR);
      }

      if($dsn['type'] == "sqlite") {
	if(array_key_exists('path', $dsn))
	  $_dsn[] = $dsn['path'];
      }

      if(array_key_exists('dbname', $dsn))
	$_dsn[] = "dbname={$dsn['dbname']}";

      if(array_key_exists('host', $dsn))
	$_dsn[] = "host={$dsn['host']}";

      if(array_key_exists('unix_socket', $dsn))
	$_dsn[] = "unix_socket={$dsn['unix_socket']}";

      if(array_key_exists('charset', $dsn))
	$_dsn[] = "charset={$dsn['charset']}";

      $_dsn = "{$dsn['type']}:" . implode(";", $_dsn);

      if(!array_key_exists('username', $dsn))
	$dsn['username'] = null;

      if(!array_key_exists('password', $dsn))
	$dsn['password'] = null;

      parent::__construct($_dsn, $dsn['username'], $dsn['password']);
    }
    else {
      parent::__construct($dsn, $username, $password, $options);
    }
  }

  function quoteIdent($str) {
    switch($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'sqlite':
      case 'pgsql':
        return '"' . strtr($str, array('"' => '""')) . '"';
      case 'mysql':
        return '`' . strtr($str, array('`' => '``')) . '`';
      default:
        throw new Exception('PDOext::quoteIdent(): do not know how to quote identifiers with this database type');
    }
  }

  function disableForeignKeyChecks() {
    switch($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'sqlite':
	$this->query("pragma foreign_keys=off;");
	return;
      case 'mysql':
	$this->query("set foreign_key_checks=0;");
	return;
      default:
        throw new Exception('disableForeignKeyChecks:: do not know how to handle this database type');
    }
  }

  function enableForeignKeyChecks() {
    switch($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'sqlite':
	$this->query("pragma foreign_keys=on;");
	return;
      case 'mysql':
	$this->query("set foreign_key_checks=1;");
	return;
      default:
        throw new Exception('enableForeignKeyChecks:: do not know how to handle this database type');
    }
  }
}
