<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) 2017 - 2021 JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 *
 * This file if a part of FloatPHP Framework
 */

namespace FloatPHP\Kernel;

use FloatPHP\Classes\Connection\Db;
use FloatPHP\Interfaces\Kernel\OrmQueryInterface;
use FloatPHP\Interfaces\Kernel\OrmInterface;
use FloatPHP\Classes\Filesystem\TypeCheck;
use FloatPHP\Classes\Filesystem\Logger;
use \PDO;
use \PDOException;

class Orm extends BaseOptions implements OrmInterface
{
	/**
	 * @access protected
	 * @var object $db
	 * @var object $data
	 */
	protected $db;
	protected $data;

	/**
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name, $value)
	{
		if ( strtolower($name) === $this->key ) {
			$this->data[$this->key] = $value;
		} else {
			$this->data[$name] = $value;
		}
	}

	/**
	 * @param string $name
	 * @return object|null
	 */
	public function __get($name)
	{
		if ( TypeCheck::isArray($this->data) ) {
			if ( array_key_exists($name,$this->data) ) {
				return $this->data[$name];
			}
		}
		return null;
	}
	
	/**
	 * Init Db object
	 *
	 * @access public
	 * @param array $data
	 * @return void
	 */
	public function init($data = [])
	{
		// Init configuration
		$this->initConfig();
		// Init db configuration
		$this->db = new Db(
			$this->getDatabaseAccess(), 
			new Logger($this->getLoggerPath(), 
			'database'
		));
		// Set data
		$this->data = $data;
	}

	/**
	 * Select Query
	 *
	 * @access public
	 * @param OrmQueryInterface $data
	 * @return mixed
	 */
	public function select(OrmQueryInterface $data)
	{
		extract($data->query);
		$sql = "SELECT `{$column}` FROM `{$table}` {$where} {$orderby} {$limit};";
		return $this->query($sql,$isSingle,$isRow);
	}

	/**
	 * Custom Query
	 *
	 * @access public
	 * @param string $sql
	 * @param array $params
	 * @param array $args
	 * @return mixed
	 */
	public function query($sql, $params = null, $args = [])
	{
		$isSingle  = isset($args['isSingle']) ? $args['isSingle'] : false;
		$isColumn  = isset($args['isColumn']) ? $args['isColumn'] : false;
		$isRow     = isset($args['isRow']) ? $args['isRow'] : false;
		$fetchMode = isset($args['fetchMode']) ? $args['fetchMode'] : null;

		if ( $isSingle ) {
			return $this->db->single($sql,$params);

		} elseif ($isColumn) {
			return $this->db->column($sql,$params);

		} elseif ($isRow) {
			return $this->db->row($sql,$params,$fetchMode);
		}
		return $this->db->query($sql,$params);
	}

	/**
	 * Save query
	 *
	 * @access public
	 * @param string $id
	 * @return array|null
	 */
	public function save($id = '0')
	{
		$this->data[$this->key] = (empty($this->data[$this->key])) 
		? $id : $this->data[$this->key];
		$field = '';
		$columns = array_keys($this->data);
		foreach($columns as $column) {
			if ( $column !== $this->key ) {
				$field .= "{$column} = :{$column},";
			}
		}
		$field = substr_replace($field,'',-1);
		if ( count($columns) > 1 ) {
			$sql = "UPDATE `{$this->table}` SET `{$field}` WHERE `{$this->key}` = :{$this->key};";
			if ( $id === '0' && $this->data[$this->key] === '0' ) {
				unset($this->data[$this->key]);
				$sql = "UPDATE `{$this->table}` SET `{$field}`;";
			}
			return $this->execute($sql);
		}
		return null;
	}

	/**
	 * @access public
	 * @param void
	 * @return array
	 */
	public function create()
	{
		$bind = $this->data;
		if ( !empty($bind) ) {
			$fields = array_keys($bind);
			$field = [implode(',',$fields),":" . implode(',:',$fields)];
			$sql = "INSERT INTO `{$this->table}` ({$field[0]}) VALUES ({$field[1]});";
		}
		else {
			$sql = "INSERT INTO `{$this->table}` () VALUES ();";
		}
		return $this->execute($sql);
	}

	/**
	 * @access public
	 * @param string|int $id
	 * @return array
	 */
	public function delete($id = '0')
	{
		$id = empty($this->data[$this->key]) ? $id : $this->data[$this->key];
		if ( !empty($id) ) {
			$sql = "DELETE FROM `{$this->table}` WHERE `{$this->key}` = :{$this->key} LIMIT 1;";
		}
		return $this->execute($sql,[$this->key => $id]);
	}

	/**
	 * @access public
	 * @param string $id
	 * @return void
	 */
	public function find($id = '')
	{
		$id = empty($this->data[$this->key]) ? $id : $this->data[$this->key];
		if ( !empty($id) ) {
			$sql = "SELECT * FROM `{$this->table}` WHERE `{$this->key}` = :{$this->key} LIMIT 1";
			$result = $this->db->row($sql,[$this->key => $id]);
			$this->data = ($result != false) ? $result : null;
		}
	}

	/**
	 * @access public
	 * @param array $fields
	 * @param array $sort
	 * @return array
	 */
	public function search($fields = [], $sort = [])
	{
		$bind = empty($fields) ? $this->data : $fields;
		$sql = "SELECT * FROM `{$this->table}`";
		if ( !empty($bind) ) {
			$field = [];
			$columns = array_keys($bind);
			foreach($columns as $column) {
				$field [] = "{$column} = :{$column}";
			}
			$sql .= " WHERE " . implode(" AND ",$field);
		}
		if ( !empty($sort) ) {
			$sortvals = [];
			foreach ($sort as $key => $value) {
				$sortvals[] = "{$key} {$value}";
			}
			$sql .= " ORDER BY " . implode(", ", $sortvals);
		}
		return $this->execute($sql);
	}

	/**
	 * @access public
	 * @param bool $isRow
	 * @return array
	 */
	public function all($isRow = false)
	{
		$sql = "SELECT * FROM `{$this->table}`;";
		if ( $isRow ) {
			return $this->db->row($sql);
		}
		return $this->db->query($sql);
	}
	
	/**
	 * @access public
	 * @param string $field
	 * @return int|null
	 */
	public function min($field)
	{
		if ( $field ) {
			return $this->db->single("SELECT min({$field}) FROM `{$this->table}`;");
		}
		return null;
	}

	/**
	 * @access public
	 * @param string $field
	 * @return int|null
	 */
	public function max($field)
	{
		if ( $field ) {
			return $this->db->single("SELECT max({$field}) FROM `{$this->table}`;");
		}
		return null;
	}

	/**
	 * @access public
	 * @param string $field
	 * @return int|null
	 */
	public function avg($field)
	{
		if ( $field ) {
			return $this->db->single("SELECT avg({$field}) FROM `{$this->table}`;");
		}
		return null;
	}

	/**
	 * @access public
	 * @param string $field
	 * @return int|null
	 */
	public function sum($field)
	{
		if ( $field ) {
			return $this->db->single("SELECT sum({$field}) FROM `{$this->table}`;");
		}
		return null;
	}

	/**
	 * @access public
	 * @param string $field
	 * @return int|null
	 */
	public function count($field = '*', $data = null)
	{
		if ( isset($data) ) {
			$this->db->bind('data',$data);
			return $this->db->single("SELECT count({$this->key}) FROM `{$this->table}` WHERE `{$field}` = :data;");
		} else {
			return $this->db->single("SELECT count({$this->key}) FROM `{$this->table}`;");
		}
		return null;
	}

	/**
	 * Delete all from table
	 *
	 * @access public
	 * @param string $table
	 * @return int
	 */
	public function deleteAll($table)
	{
		$sql = "DELETE FROM `{$table}`;";
		return $this->db->query($sql);
	}

	/**
	 * Create application database
	 *
	 * @access public
	 * @param void
	 * @return bool
	 */
	public function createDatabase()
	{
		$default = $this->getDatabaseAccess();
		$root = $this->getDatabaseRootAccess();
	    try {
	    	$dsn = "mysql:host={$default['host']};port={$default['port']};charset=utf8mb4";
	        $pdo = new PDO($dsn,$root['user'],$root['pswd'],[
	        	PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$default['charset']}"
	        ]);
	        $sql = "CREATE DATABASE IF NOT EXISTS `{$default['db']}`;";
	        $query = $pdo->prepare($sql);
	        $query->execute();
	        $query = $pdo->prepare("ALTER DATABASE `{$default['db']}` COLLATE utf8_general_ci;");
	        $query->execute();
	    }
	    catch (PDOException $e) {
	        die("ERROR : {$e->getMessage()}");
	    }
	}

	/**
	 * @access private
	 * @param string $sql
	 * @param string $isArray
	 * @return int|null
	 */
	private function execute($sql, $isArray = false)
	{
		if ( $isArray ) {
			$result = $this->db->query($sql,$isArray);
		} else {
			$result = $this->db->query($sql,$this->data);
		}
		// Empty bind
		$this->data = [];
		return $result;
	}
}
