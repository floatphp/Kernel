<?php
/**
 * @author    : JIHAD SINNAOUR
 * @package   : FloatPHP
 * @subpackage: Kernel Component
 * @version   : 1.0.0
 * @category  : PHP framework
 * @copyright : (c) JIHAD SINNAOUR <mail@jihadsinnaour.com>
 * @link      : https://www.floatphp.com
 * @license   : MIT License
 */

namespace floatphp\Kernel;

use App\System\Classes\Connection\Db;

class Model
{
	protected $db;
	public $data;

	public function __construct($data = [])
	{
		$this->db = new Db();
		$this->data = $data;
	}

	public function __set($name,$value)
	{
		if(strtolower($name) === $this->key) {
			$this->data[$this->key] = $value;
		}
		else {
			$this->data[$name] = $value;
		}
	}

	public function __get($name)
	{	
		if(is_array($this->data)) {
			
			if(array_key_exists($name,$this->data)) {
				return $this->data[$name];
			}
		}

		return null;
	}
	/**
	* @todo fix query on null
	*/
	protected function getLastInsertId()
	{
		// return $this->db->lastInsertId();
	}

	public function join($table,$column = NULL)
	{
		$sql = "SELECT * FROM $this->table ";

		if ( is_array($table) && is_null($column) ) {

			
			$key = key($table);
			$tab = $table[$key];

			$sql .= "LEFT JOIN $tab ON $this->table.$key = $tab.$key ";

			foreach ( $table as $tab => $key ) {

				return $tab;
				$sql .= "LEFT JOIN $tab ON $this->table.$column = $table.$column";
			}

		}elseif( !is_array($table) && !is_null($column) ) {
			$sql .= "LEFT JOIN $table ON $this->table.$column = $table.$column";
		}
		
		return $this->execute($sql);
	}
	
	public function where($column,$value)
	{
		$this->db->bind('value',$value);
		$sql = "SELECT * FROM $this->table WHERE $this->table.$column = :value";
		return $this->execute($sql);
	}

	public function relation($table)
	{
		$this->db->bind('id',$this->data[$this->key]);
		$sql = "SELECT * FROM $table WHERE $table.$this->key = :id";
		return $this->execute($sql);
	}

	public function save($id = "0")
	{
		$this->data[$this->key] = (empty($this->data[$this->key])) ? $id : $this->data[$this->key];

		$fieldsvals = '';
		$columns = array_keys($this->data);

		foreach($columns as $column)
		{
			if($column !== $this->key)
			$fieldsvals .= $column . " = :". $column . ",";
		}

		$fieldsvals = substr_replace($fieldsvals , '', -1);

		if(count($columns) > 1 ) {

			$sql = "UPDATE " . $this->table .  " SET " . $fieldsvals . " WHERE " . $this->key . "= :" . $this->key;
			if($id === "0" && $this->data[$this->key] === "0") { 
				unset($this->data[$this->key]);
				$sql = "UPDATE " . $this->table .  " SET " . $fieldsvals;
			}

			return $this->execute($sql);
		}

		return null;
	}

	public function create()
	{
		$bindings   	= $this->data;

		if(!empty($bindings)) {
			$fields     =  array_keys($bindings);
			$fieldsvals =  array(implode(",",$fields),":" . implode(",:",$fields));
			$sql 		= "INSERT INTO ".$this->table." (".$fieldsvals[0].") VALUES (".$fieldsvals[1].")";
		}
		else {
			$sql 		= "INSERT INTO ".$this->table." () VALUES ()";
		}

		return $this->execute($sql);
	}

	public function delete($id = "")
	{
		$id = (empty($this->data[$this->key])) ? $id : $this->data[$this->key];

		if(!empty($id)) {
			$sql = "DELETE FROM " . $this->table . " WHERE " . $this->key . "= :" . $this->key. " LIMIT 1" ;
		}

		return $this->execute($sql, array($this->key=>$id));
	}

	public function find($id = "")
	{
		$id = (empty($this->data[$this->key])) ? $id : $this->data[$this->key];

		if(!empty($id)) {
			
			$sql = "SELECT * FROM " . $this->table ." WHERE " . $this->key . "= :" . $this->key . " LIMIT 1";	
			
			$result = $this->db->row($sql, array($this->key=>$id));
			$this->data = ($result != false) ? $result : null;
		}
	}
	/*
	* LIKE, >, <, >=, <= not supported
	*/
	public function search($fields = array(), $sort = array())
	{
		$bindings = empty($fields) ? $this->data : $fields;

		$sql = "SELECT * FROM " . $this->table;

		if (!empty($bindings)) {
			
			$fieldsvals = array();
			$columns = array_keys($bindings);
			foreach($columns as $column) {
				$fieldsvals [] = $column . " = :". $column;
			}
			$sql .= " WHERE " . implode(" AND ", $fieldsvals);
		}
		
		if (!empty($sort)) {
			$sortvals = array();
			foreach ($sort as $key => $value) {
				$sortvals[] = $key . " " . $value;
			}
			$sql .= " ORDER BY " . implode(", ", $sortvals);
		}
		return $this->execute($sql);
	}

	public function all()
	{
		return $this->db->query("SELECT * FROM " . $this->table);
	}
	
	public function min($field)
	{
		if($field)
		return $this->db->single("SELECT min(" . $field . ")" . " FROM " . $this->table);
	}

	public function max($field)
	{
		if($field)
		return $this->db->single("SELECT max(" . $field . ")" . " FROM " . $this->table);
	}

	public function avg($field)
	{
		if($field)
		return $this->db->single("SELECT avg(" . $field . ")" . " FROM " . $this->table);
	}

	public function sum($field)
	{
		if($field)
		return $this->db->single("SELECT sum(" . $field . ")" . " FROM " . $this->table);
	}

	public function count($field,$data = null)
	{
		if (isset($data)) {

			$this->db->bind('data',$data);
			return $this->db->single("SELECT count(" . $this->key . ")" . " FROM " . $this->table . " WHERE " . $field . '= :data');

		}else{
			return $this->db->single("SELECT count(" . $this->key . ")" . " FROM " . $this->table);
		}
	}	
	
	private function execute($sql, $array = null) 
	{
		if($array !== null)
		{
			// Get result with the DB object
			$result =  $this->db->query($sql, $array);	
		}
		else {
			// Get result with the DB object
			$result =  $this->db->query($sql, $this->data);	
		}
		
		// Empty bindings
		$this->data = array();

		return $result;
	}
}
