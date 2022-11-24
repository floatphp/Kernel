<?php
/**
 * @author     : JIHAD SINNAOUR
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.0.0
 * @category   : PHP framework
 * @copyright  : (c) 2017 - 2022 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://www.floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Interfaces\Kernel\{
    OrmInterface, OrmQueryInterface
};
use FloatPHP\Classes\{
    Connection\Db, 
    Filesystem\TypeCheck, Filesystem\Stringify, Filesystem\Arrayify, Filesystem\Logger
};
use \PDO;
use \PDOException;

class Orm implements OrmInterface
{
	use TraitConfiguration;

	/**
	 * @access protected
	 * @var object $db
	 * @var object $data
	 */
	protected $db;
	protected $data;

	/**
	 * @param array $data
	 * @return void
	 */
	public function __construct($data = [])
	{
		// Init configuration
		$this->initConfig();

		// Init db configuration
		$this->db = new Db(
			$this->getDatabaseAccess(), 
			new Logger("{$this->getLoggerPath()}/database",'database')
		);

		// Set data
		$this->data = $data;
	}
	
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
	 * @return mixed
	 */
	public function __get($name)
	{
		if ( TypeCheck::isArray($this->data) ) {
			if ( Arrayify::hasKey($name,$this->data) ) {
				return $this->data[$name];
			}
		}
		return null;
	}

	/**
	 * Custom Select ORM Query.
	 *
	 * @access public
	 * @param OrmQueryInterface $data
	 * @return mixed
	 */
	public function select(OrmQueryInterface $data)
	{
		extract($data->query);
		if ( $column !== '*' ) {
			$column = `{$column}`;
		}
		$sql  = trim("SELECT $column FROM `{$table}` {$where} {$orderby} {$limit}");
		$sql .= ';';
		return $this->query($sql,[],[
			'isSingle'  => $isSingle,
			'isColumn'  => $isColumn,
			'isRow'     => $isRow,
			'fetchMode' => $fetchMode
		]);
	}

	/**
	 * Custom ORM Query.
	 *
	 * @access public
	 * @param string $sql
	 * @param array $bind
	 * @param array $args
	 * @return mixed
	 */
	public function query($sql, $bind = null, $args = [])
	{
		$isSingle  = isset($args['isSingle'])  ? $args['isSingle']  : false;
		$isColumn  = isset($args['isColumn'])  ? $args['isColumn']  : false;
		$isRow     = isset($args['isRow'])     ? $args['isRow']     : false;
		$fetchMode = isset($args['fetchMode']) ? $args['fetchMode'] : null;

		if ( $isSingle ) {
			return $this->db->single($sql,$bind);

		} elseif ($isColumn) {
			return $this->db->column($sql,$bind);

		} elseif ($isRow) {
			return $this->db->row($sql,$bind,$fetchMode);
		}
		return $this->db->query($sql,$bind);
	}

	/**
	 * Update table object.
	 *
	 * @access public
	 * @param int $id
	 * @return mixed
	 */
	public function save($id = null)
	{
		if ( !$id ) {
			$id = !empty($this->data[$this->key])
			? intval($this->data[$this->key]) : 0;
		}
		$fields = '';
		$columns = Arrayify::keys($this->data);
		foreach($columns as $column) {
			if ( $column !== $this->key ) {
				$fields .= "`{$column}` = :{$column},";
			}
		}
		$fields = Stringify::subreplace($fields,'',-1,1);
		if ( count($columns) > 1 ) {
			$sql = "UPDATE `{$this->table}` SET {$fields} WHERE `{$this->key}` = :{$this->key};";
			if ( $id === 0 ) {
				unset($this->data[$this->key]);
				$sql = "UPDATE `{$this->table}` SET {$fields};";
			}
			return $this->execute($sql);
		}
		return null;
	}

	/**
	 * Create table object.
	 *
	 * @access public
	 * @param void
	 * @return int
	 */
	public function create() : int
	{
		$bind = $this->data;
		if ( !empty($bind) ) {
			$bind = Arrayify::keys($bind);
			$columns = [];
			foreach ($bind as $key => $column) {
				$columns[$key] = "`{$column}`";
			}
			$fields = implode(',',$columns);
			$values = ':' . implode(',:',$bind);
			$sql = "INSERT INTO `{$this->table}` ({$fields}) VALUES ({$values});";
		} else {
			$sql = "INSERT INTO `{$this->table}` () VALUES ();";
		}
		return $this->execute($sql);
	}

	/**
	 * Delete table object.
	 *
	 * @access public
	 * @param int $id
	 * @return int
	 */
	public function delete($id = null) : int
	{
		if ( !$id ) {
			$id = !empty($this->data[$this->key])
			? intval($this->data[$this->key]) : 0;
		}
		$sql = "DELETE FROM `{$this->table}` WHERE `{$this->key}` = :{$this->key} LIMIT 1;";
		$bind = [$this->key => $id];
		return $this->execute($sql,$bind);
	}

	/**
	 * Find table object.
	 *
	 * @access public
	 * @param int $id
	 * @return mixed
	 */
	public function find($id = null)
	{
		if ( !$id ) {
			$id = !empty($this->data[$this->key])
			? intval($this->data[$this->key]) : 0;
		}
		$sql = "SELECT * FROM `{$this->table}` WHERE `{$this->key}` = :{$this->key} LIMIT 1;";
		$bind = [$this->key => $id];
		$result = $this->db->row($sql,$bind);
		return $this->data = ($result) ? $result : null;
	}

	/**
	 * Search table objects by bind.
	 *
	 * @access public
	 * @param array $bind
	 * @param array $sort
	 * @return mixed
	 */
	public function search($bind = [], $sort = [])
	{
		$bind = empty($bind) ? $this->data : $bind;
		$sql = "SELECT * FROM `{$this->table}`";
		if ( !empty($bind) ) {
			$fields = [];
			$columns = Arrayify::keys($bind);
			foreach($columns as $column) {
				$fields [] = "`{$column}` LIKE :{$column}";
			}
			$sql .= " WHERE " . implode(" AND ",$fields);
		}
		if ( !empty($sort) ) {
			$sorted = [];
			foreach ($sort as $key => $value) {
				$sorted[] = "{$key} {$value}";
			}
			$sql .= " ORDER BY " . implode(", ", $sorted);
		}
		return $this->execute($sql,$bind);
	}

	/**
	 * Search table object by bind.
	 *
	 * @access public
	 * @param array $bind
	 * @param array $sort
	 * @return mixed
	 */
	public function searchOne($bind = [], $sort = [])
	{
		$bind = empty($bind) ? $this->data : $bind;
		$sql = "SELECT * FROM `{$this->table}`";
		if ( !empty($bind) ) {
			$fields = [];
			$columns = Arrayify::keys($bind);
			foreach($columns as $column) {
				$fields[] = "`{$column}` LIKE :{$column}";
			}
			$sql .= ' WHERE ' . implode(' AND ',$fields);
		}
		if ( !empty($sort) ) {
			$sorted = [];
			foreach ($sort as $key => $value) {
				$sorted[] = "{$key} {$value}";
			}
			$sql .= ' ORDER BY ' . implode(', ',$sorted);
		}
		$sql .= ' LIMIT 1;';
		$result = $this->db->row($sql,$bind);
		return $this->data = ($result) ? $result : null;
	}

	/**
	 * Get all table onjects.
	 *
	 * @access public
	 * @param void
	 * @return mixed
	 */
	public function all()
	{
		$sql = "SELECT * FROM `{$this->table}`;";
		return $this->db->query($sql);
	}
	
	/**
	 * Get field min.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function min(string $field)
	{
		return $this->db->single("SELECT min({$field}) FROM `{$this->table}`;");
	}

	/**
	 * Get field max.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function max(string $field)
	{
		return $this->db->single("SELECT max({$field}) FROM `{$this->table}`;");
	}

	/**
	 * Get field avg.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function avg(string $field)
	{
		return $this->db->single("SELECT avg({$field}) FROM `{$this->table}`;");
	}

	/**
	 * Get field sum.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function sum(string $field)
	{
		return $this->db->single("SELECT sum({$field}) FROM `{$this->table}`;");
	}

	/**
	 * Count items.
	 *
	 * @access public
	 * @param array $bind
	 * @param string $column
	 * @param bool $distinct
	 * @return mixed
	 */
	public function count($bind = null, $column = null, $distinct = false)
	{
		if ( !$column ) {
			$column = $this->key;
		}
		if ( $distinct ) {
			$column = "DISTINCT {$column}";
		}
		$sql = "SELECT COUNT({$column}) FROM `{$this->table}`";
		if ( TypeCheck::isArray($bind) ) {
			$where = '';
			foreach ($bind as $key => $value) {
				$where .= "`{$key}` LIKE :{$key} AND ";
			}
			$where = rtrim($where,' AND ');
			$sql .= " WHERE {$where}";
		}
		$sql .= ";";
		return $this->db->single($sql,$bind);
	}

	/**
	 * Delete all from table.
	 *
	 * @access public
	 * @param string $table
	 * @return int
	 */
	public function deleteAll($table = '') : int
	{
		if ( empty($table) ) {
			$table = $this->table;
		}
		$sql = "DELETE FROM `{$table}`;";
		return $this->db->query($sql);
	}

	/**
	 * Reset table Id.
	 *
	 * @access public
	 * @param string $table
	 * @return mixed
	 */
	public function resetId($table = '')
	{
		if ( empty($table) ) {
			$table = $this->table;
		}
		$sql = "ALTER TABLE `{$table}` AUTO_INCREMENT = 1;";
		return $this->db->query($sql);
	}

	/**
	 * Set table data.
	 *
	 * @access public
	 * @param array $data
	 * @return object
	 */
	public function setData($data = []) : object
	{
		$this->data = $data;
		return $this;
	}

	/**
	 * Create application database.
	 *
	 * @access public
	 * @param void
	 * @return bool
	 * @throws PDOException
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
	 * Check table.
	 *
	 * @access public
	 * @param string $table
	 * @return bool
	 */
	public function hasTable($table = '')
	{
		$sql = "SHOW TABLES LIKE '{$table}';";
		return (bool)$this->db->query($sql);
	}

	/**
	 * Get tables.
	 *
	 * @access public
	 * @param void
	 * @return array
	 */
	public function getTables() : array
	{
		return (array)$this->db->query('show tables;');
	}
	
	/**
	 * @access private
	 * @param string $sql
	 * @param array $bind
	 * @return mixed
	 */
	private function execute($sql, $bind = null)
	{
		$bind = ($bind) ? $bind : $this->data;
		// Reset data
		$this->data = [];
		return $this->db->query($sql,$bind);
	}
}
