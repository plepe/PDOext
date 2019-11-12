<?php
class PDOext extends PDO {
  function __construct($dsn, $username=null, $password=null, $options=array()) {
    $this->time_init = microtime(true);

    if(is_array($dsn)) {
      $this->options = $dsn;
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

      parent::__construct($_dsn, $dsn['username'], $dsn['password'], $this->options);
    }
    else {
      $this->options = $options;
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

  function quote($str, $parameter_type=PDO::PARAM_STR) {
    if($str === null)
      return 'NULL';
    if($str === true)
      return 'TRUE';
    if($str === false)
      return 'FALSE';

    return parent::quote($str, $parameter_type);
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

  function tableExists($id) {
    try {
      $res = $this->query("select 1 from " . $this->quoteIdent($id));
    }
    catch (Exception $e) {
      if ($this->errorCode() === '42S02') {
        return false;
      } else {
        throw $e;
      }
    }

    if($res === false)
      return false;

    $res->closeCursor();
    return true;
  }

  function tables () {
    switch($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'sqlite':
        $res = $this->query("SELECT name FROM sqlite_master WHERE type='table'");
        $res = $res->fetchAll();
        return array_map(function ($x) { return $x['name']; }, $res);
      case 'mysql':
        $res = $this->query("SELECT table_name FROM information_schema.tables where table_schema=" . $this->quote($this->options['dbname']));
        $res = $res->fetchAll();
        return array_map(function ($x) { return $x['table_name']; }, $res);
      default:
        throw new Exception('tables(): do not know how to handle this database type');
    }
  }

  /**
   * return list of columns, e.g.:
   * {
   *   "id": {
   *     "type": "integer" OR "bigint(20) unsigned",
   *     "notnull": true/false,
   *     "default": null/"0",
   *     "key": PRI|false,
   *     "extra": "auto_increment"
   *   },
   *   "...."
   * }
   */
  function columns ($table) {
    switch($this->getAttribute(PDO::ATTR_DRIVER_NAME)) {
      case 'sqlite':
        $res = $this->query("PRAGMA table_info(" . $this->quote($table) . ")");

        $result = array();
        while ($elem = $res->fetch()) {
          $result[$elem['name']] = array(
            'type' => $elem['type'],
            'notnull' => $elem['notnull'] === 1,
            'default' => $elem['dflt_value'],
            'key' => $elem['pk'],
          );
        }

        return $result;
      case 'mysql':
        $res = $this->query("SHOW columns FROM " . $this->quoteIdent($table));

        $result = array();
        while ($elem = $res->fetch()) {
          $result[$elem['Field']] = array(
            'type' => $elem['Type'],
            'notnull' => $elem['Null'] === 'NO',
            'default' => $elem['Default'],
            'key' => $elem['Key'],
            'extra' => $elem['Extra'],
          );
        }

        return $result;
      default:
        throw new Exception('columns(): do not know how to handle this database type');
    }
  }

  function query() {
    $time_start = microtime(true);
    if(array_key_exists('debug', $this->options) && ($this->options['debug'])) {
      $span_since_init = sprintf("%.0fms", (microtime(true) - $this->time_init) * 1000.0);
    }

    try {
      $ret = call_user_func_array('parent::query', func_get_args());
    }
    catch(Exception $e) {
      $duration = sprintf("%.1fms", (microtime(true) - $time_start) * 1000.0);
      $qry = func_get_arg(0);
      $error = $e->getMessage();

      if($this->options['debug'] & 1)
	print "<!-- @{$span_since_init} + Δ{$duration} (EXCEPTION: {$error}):\n{$qry} -->\n";
      if($this->options['debug'] & 2)
	messages_debug("@{$span_since_init} + Δ{$duration} (EXCEPTION: {$error}):\n{$qry}", MSG_ERROR);

      throw $e;
    }

    if(array_key_exists('debug', $this->options) && ($this->options['debug'])) {
      $duration = sprintf("%.1fms", (microtime(true) - $time_start) * 1000.0);
      $qry = func_get_arg(0);

      if($this->options['debug'] & 1)
	print "<!-- @{$span_since_init} + Δ{$duration}:\n{$qry} -->\n";
      if($this->options['debug'] & 2)
	messages_debug("@{$span_since_init} + Δ{$duration}:\n{$qry}");
    }

    return $ret;
  }
}
