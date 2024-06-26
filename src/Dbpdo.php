<?php
namespace Nzn1\PhpPdoClass;

require_once 'vendor/autoload.php';

/**
 * Simple PHP PDO Class
 * @author Miks Zvirbulis (twitter.com/MiksZvirbulis)
 * @version 1.1
 * 1.0 - First version launched. Allows access to one database and a few regular functions have been created.
 * 1.1 - Added a constructor which allows multiple databases to be called on different variables.
 * @author NZN https://github.com/nzn1/PHP-PDO-Class
 * 1.2 - charset support, improved debugging (show query, params, file, line), "result" method for unbuffered query, return insert id or num rows depending on query, lastQuery method, "sql_escape" utility function.
 */

class Dbpdo {
  # Database host address, defined in construction.
  protected $host;
  # Username for authentication, defined in construction.
  protected $username;
  # Password for authentication, defined in construction.
  protected $password;
  # Database name, defined in construction.
  protected $database;

  # Connection variable. DO NOT CHANGE!
  protected $connection;

  # @bool default for this is to be left to FALSE, please. This determines the connection state.
  public $connected = false;
  public $charset = null;

  # @bool this controls if the errors are displayed. By default, this is set to true.
  private $errors = true;
  private $lastquery = '';
  private $lastparameters = '';

  function __construct($db_host, $db_username, $db_password, $db_database, $charset = 'utf8mb4') {
    global $c;
    try {
      $this->host = $db_host;
      $this->username = $db_username;
      $this->password = $db_password;
      $this->database = $db_database;
      $this->connected = true;
      $this->charset = $charset;

      $this->connection = new \PDO("mysql:host=$this->host;dbname=$this->database;charset=$this->charset", $this->username, $this->password);
      $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
      $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }
    catch(PDOException $e) {
      $this->connected = false;
      if ($this->errors === true) {
        return $this->error($e->getMessage(), '', '');
      } else {
        return false;
      }
    }
  }

  function __destruct() {
    $this->connected = false;
    $this->connection = null;
  }

  public function debug($error, $query, $parameters) {
    $error = $error ? ['DB error:' => $error] : [];

    return '<pre style="direction: ltr; text-align: left; background: white">'
      . print_r(array_merge(
      $error,
      [
        'query:' => $query,
        'parameters:' => $parameters,
        'file:' => debug_backtrace()[1]['file'],
        'line:' => debug_backtrace()[1]['line']
      ]), 1)
      . '</pre>';
  }

  public function error($error, $query, $parameters) {
    echo $this->debug($error, $query, $parameters);
    die;
  }

  public function result($query, $parameters = []) {
    if ($this->connected === true) {
      try {
        $this->lastquery = $query;
        $this->lastparameters = $parameters;
        $query = $this->connection->prepare($query);
        $query->execute($parameters);
        return $query;
      }
      catch(PDOException $e) {
        if ($this->errors === true) {
          return $this->error($e->getMessage(), $query, $parameters);
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  public function fetch($query, $parameters = []) {
    if ($this->connected === true) {
      try {
        $this->lastquery = $query;
        $this->lastparameters = $parameters;
        $query = $this->connection->prepare($query);
        $query->execute($parameters);
        return $query->fetch();
      }
      catch(PDOException $e) {
        if ($this->errors === true) {
          return $this->error($e->getMessage(), $query, $parameters);
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  public function fetchAll($query, $parameters = []) {
    if ($this->connected === true) {
      try {
        $this->lastquery = $query;
        $this->lastparameters = $parameters;
        $query = $this->connection->prepare($query);
        $query->execute($parameters);
        return $query->fetchAll();
      }
      catch(PDOException $e) {
        if ($this->errors === true) {
          return $this->error($e->getMessage(), $query, $parameters);
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  public function count($query, $parameters = []) {
    if ($this->connected === true) {
      try {
        $this->lastquery = $query;
        $this->lastparameters = $parameters;
        $query = $this->connection->prepare($query);
        $query->execute($parameters);
        return $query->rowCount();
      }
      catch(PDOException $e) {
        if ($this->errors === true) {
          return $this->error($e->getMessage(), $query, $parameters);
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  public function execute($querystring, $parameters = []) {
    if ($this->connected === true) {
      try {
        $this->lastquery = $querystring;
        $this->lastparameters = $parameters;
        $query = $this->connection->prepare($querystring);
        $query->execute($parameters);
        $insert_query = preg_match('~INSERT~i', ltrim($querystring));
        return $insert_query ? $this->connection->lastInsertId() : $query->rowCount();
      }
      catch(PDOException $e) {
        if ($this->errors === true) {
          return $this->error($e->getMessage(), $querystring, $parameters);
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  public function tableExists($table) {
    if ($this->connected === true) {
      try {
        $query = $this->count("SHOW TABLES LIKE '$table'");
        $this->lastquery = $query;
        $this->lastparameters = $parameters;
        return ($query > 0) ? true : false;
      }
      catch(PDOException $e) {
        if ($this->errors === true) {
          return $this->error($e->getMessage(), $query, $parameters);
        } else {
          return false;
        }
      }
    } else {
      return false;
    }
  }

  public function lastQuery() {
    return $this->debug(null, $this->lastquery, $this->lastparameters);
  }
}

if (!function_exists('sql_escape')) {
  function sql_escape($value) {
      $return = '';

      for($i = 0; $i < strlen($value); ++$i) {
        $char = $value[$i];
        $ord = ord($char);
        if($char !== "'" && $char !== "\"" && $char !== '\\' && $ord >= 32 && $ord <= 126) {
          $return .= $char;
        } else {
          $return .= '\\x' . dechex($ord);
        }
      }

      return $return;
  }
}
