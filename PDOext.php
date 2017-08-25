<?php
class PDOext extends PDO {
  function __construct($dsn, $username=null, $password=null, $options=array()) {
    $this->time_init = microtime(true);

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

      if($username === null)
        $this->options = array();
      else
        $this->options = $username;

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

  function query() {
    if(array_key_exists('debug', $this->options) && ($this->options['debug'])) {
      $time_start = microtime(true);
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
