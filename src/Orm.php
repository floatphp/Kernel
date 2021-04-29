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

class Orm extends BaseOptions implements OrmInterface
{
	/**
	 * @access public
	 * @var object $data
	 */
	public $data;

	/**
	 * @access protected
	 * @var object $db
	 * @var string $type
	 */
	protected $db;

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
		if ( is_array($this->data) ) {
			if ( array_key_exists($name,$this->data) ) {
				return $this->data[$name];
			}
		}
		return null;
	}
	
	/**
	 * Init Db object
	 *
	 * @access protected
	 * @param array $data
	 * @return void
	 */
	protected function init($data = [])
	{
		// Init configuration
		$this->initConfig();
		// Init db configuration
		$this->db = new Db($this->getDatabaseAccess());
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
	 * @return int
	 */
	public function query($sql, $isSingle = false, $isRow = false)
	{
		if ( $isSingle ) {
			return $this->db->single($sql);

		} elseif ($isRow) {
			$result = $this->db->query($sql);
			return array_shift($result);
		}
		return $this->db->query($sql);
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
		$fieldVal = '';
		$columns = array_keys($this->data);
		foreach($columns as $column) {
			if ($column !== $this->key) {
				$fieldVal .= $column . " = :". $column . ",";
			}
		}
		$fieldVal = substr_replace($fieldVal , '', -1);
		if ( count($columns) > 1 ) {
			$sql = "UPDATE " . $this->table .  " SET " . $fieldVal . " WHERE " . $this->key . "= :" . $this->key;
			if ($id === '0' && $this->data[$this->key] === '0' ) {
				unset($this->data[$this->key]);
				$sql = "UPDATE " . $this->table .  " SET " . $fieldVal;
			}
			return $this->execute($sql);
		}
		return null;
	}

	/**
	 * @param void
	 * @return array
	 */
	public function create()
	{
		$bind = $this->data;
		if( !empty($bind) ) {
			$fields = array_keys($bind);
			$fieldVal = [implode(",",$fields),":" . implode(",:",$fields)];
			$sql = "INSERT INTO ".$this->table." (".$fieldVal[0].") VALUES (".$fieldVal[1].")";
		}
		else {
			$sql = "INSERT INTO ".$this->table." () VALUES ()";
		}
		return $this->execute($sql);
	}

	/**
	 * @param string|int $id
	 * @return array
	 */
	public function delete($id = '0')
	{
		$id = empty($this->data[$this->key])
		? $id : $this->data[$this->key];
		if ( !empty($id) ) {
			$sql = "DELETE FROM " . $this->table . " WHERE " . $this->key . "= :" . $this->key. " LIMIT 1" ;
		}
		return $this->execute($sql, [$this->key=>$id]);
	}

	/**
	 * @param string $id
	 * @return void
	 */
	public function find($id = '')
	{
		$id = empty($this->data[$this->key])
		? $id : $this->data[$this->key];

		if ( !empty($id) ) {
			$sql = "SELECT * FROM " . $this->table ." WHERE " . $this->key . "= :" . $this->key . " LIMIT 1";
			$result = $this->db->row($sql, array($this->key=>$id));
			$this->data = ($result != false)
			? $result : null;
		}
	}

	/**
	 * @param array $fields
	 * @param array $sort
	 * @return array
	 */
	public function search($fields = [], $sort = [])
	{
		$bind = empty($fields) ? $this->data : $fields;
		$sql = "SELECT * FROM " . $this->table;
		if ( !empty($bind) ) {
			$fieldVal = [];
			$columns = array_keys($bind);
			foreach($columns as $column) {
				$fieldVal [] = $column . " = :". $column;
			}
			$sql .= " WHERE " . implode(" AND ", $fieldVal);
		}
		if ( !empty($sort) ) {
			$sortvals = [];
			foreach ($sort as $key => $value) {
				$sortvals[] = $key . " " . $value;
			}
			$sql .= " ORDER BY " . implode(", ", $sortvals);
		}
		return $this->execute($sql);
	}

	/**
	 * @param void
	 * @return array
	 */
	public function all($row = false)
	{
		if ($row) {
			$r = $this->db->query("SELECT * FROM " . $this->table);
			return array_shift($r);
		}
		else return $this->db->query("SELECT * FROM " . $this->table);
	}
	
	/**
	 * @param string $field
	 * @return int|null
	 */
	public function min($field)
	{
		if ($field) {
			return $this->db->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
		}
		return null;
	}

	/**
	 * @param string $field
	 * @return int|null
	 */
	public function max($field)
	{
		if ($field) {
			return $this->db->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
		}
		return null;
	}

	/**
	 * @param string $field
	 * @return int|null
	 */
	public function avg($field)
	{
		if ($field) {
			return $this->db->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
		}
		return null;
	}

	/**
	 * @param string $field
	 * @return int|null
	 */
	public function sum($field)
	{
		if ($field) {
			return $this->db->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
		}
		return null;
	}

	/**
	 * @param string $field
	 * @return int|null
	 */
	public function count($field = '*', $data = null)
	{
		if ( isset($data) ) {
			$this->db->bind('data',$data);
			return $this->db->single("SELECT count(" . $this->key . ")" . " FROM " . $this->table . " WHERE " . $field . '= :data');
		} else {
			return $this->db->single("SELECT count(" . $this->key . ")" . " FROM " . $this->table);
		}
	}

	/**
	 * @param string $sql
	 * @param string $array
	 * @return int|null
	 */
	private function execute($sql, $array = false)
	{
		if ( $array ) {
			$result = $this->db->query($sql, $array);
		} else {
			$result = $this->db->query($sql, $this->data);
		}
		// Empty bind
		$this->data = [];
		return $result;
	}

	/**
	 * Delete All Query
	 *
	 * @access public
	 * @param string $table
	 * @return int
	 */
	public function deleteAll($table)
	{
		$sql = "DELETE FROM {$table}";
		return $this->db->query($sql);
	}
}
