<?php
class PDOext extends PDO {
  function __construct($dsn, $username=null, $password=null, $options=array()) {
    parent::__construct($dsn, $username, $password, $options);
  }
}
