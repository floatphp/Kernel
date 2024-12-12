<?php
/**
 * @author     : Jakiboy
 * @package    : FloatPHP
 * @subpackage : Kernel Component
 * @version    : 1.4.x
 * @copyright  : (c) 2018 - 2024 Jihad Sinnaour <mail@jihadsinnaour.com>
 * @link       : https://floatphp.com
 * @license    : MIT
 *
 * This file if a part of FloatPHP Framework.
 */

declare(strict_types=1);

namespace FloatPHP\Kernel;

use FloatPHP\Exceptions\Kernel\OrmException;
use FloatPHP\Interfaces\Kernel\OrmInterface;
use \PDO;
use \PDOException;

class Orm implements OrmInterface
{
	use TraitConfiguration,
		\FloatPHP\Helpers\Framework\tr\TraitConnectable;

	/**
	 * @access private
	 * @var array $bind, Binded data
	 * @var array $row, Result row data
	 * @var array $access, Result row data
	 * @var bool $connect, Database connection
	 */
	private $bind = [];
	private $row = [];
	private $access = [];
	private static $connect = true;

	/**
	 * @access protected
	 * @var string $table, Table name
	 * @var string $key, Primary key
	 */
	protected $table;
	protected $key;

	/**
	 * Init database.
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->access = $this->getDbAccess();
		if ( self::$connect ) {
			$log = $this->getLoggerPath('database');
			$this->getDbObject($this->access, $log);
		}
		self::$connect = true;
	}

	/**
	 * Set request bind property.
	 * 
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set(string $name, $value) : void
	{
		$this->bind[$name] = $value;
	}

	/**
	 * Get result row property.
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function __get(string $name) : mixed
	{
		return $this->row[$name] ?? null;
	}

	/**
	 * Bind query data.
	 *
	 * @access public
	 * @param mixed $bind
	 * @param mixed $value
	 * @return object
	 */
	public function bind($bind = [], $value = null) : self
	{
		if ( $this->isType('array', $bind) ) {
			$this->bind = $bind;

		} else {
			$this->bind["{$bind}"] = $value;
		}
		return $this;
	}

	/**
	 * Set working table.
	 *
	 * @access public
	 * @param string $table
	 * @return object
	 */
	public function setTable(string $table) : self
	{
		$this->table = $table;
		return $this;
	}

	/**
	 * Set table key.
	 *
	 * @access public
	 * @param string $key
	 * @return object
	 */
	public function setKey(string $key) : self
	{
		$this->key = $key;
		return $this;
	}

	/**
	 * Create row using data or binded data.
	 *
	 * @access public
	 * @param array $data
	 * @return bool
	 */
	public function create(?array $data = null) : bool
	{
		if ( $data ) $this->bind($data);
		$sql = "{$this->getInsertQuery()};";
		return (bool)$this->execute($sql);
	}

	/**
	 * Read row using Id or binded Id.
	 *
	 * @access public
	 * @param mixed $id
	 * @return array
	 */
	public function read($id = null) : array
	{
		if ( $id ) $this->{$this->key} = $id;
		$sql = "{$this->getSelectQuery()} ";
		$sql .= "{$this->getWhereQuery(single: true)} ";
		$sql .= "{$this->getLimitQuery(limit: 1)};";
		return $this->getRow($sql);
	}

	/**
	 * Read rows.
	 *
	 * @access public
	 * @return mixed
	 */
	public function readAny()
	{
		$sql = "{$this->getSelectQuery()} ";
		$sql .= "{$this->getWhereQuery()};";
		return $this->execute($sql);
	}

	/**
	 * Update row using Id or binded Id.
	 *
	 * @access public
	 * @param mixed $id
	 * @return bool
	 */
	public function update($id = null) : bool
	{
		if ( $id ) $this->{$this->key} = $id;
		$sql = "{$this->getUpdateQuery()} ";
		$sql .= "{$this->getWhereQuery(single: true)};";
		return (bool)$this->execute($sql);
	}

	/**
	 * Update rows using custom where.
	 *
	 * @access public
	 * @param array $where
	 * @return int
	 */
	public function updateAny(array $where = []) : int
	{
		$sql = "{$this->getUpdateQuery()} ";
		$sql .= "{$this->parseWhereQuery($where)};";
		return (int)$this->execute($sql);
	}

	/**
	 * Delete row using Id or binded Id.
	 *
	 * @access public
	 * @param mixed $id
	 * @return bool
	 */
	public function delete($id = null) : bool
	{
		if ( $id ) $this->{$this->key} = $id;
		$sql = "{$this->getDeleteQuery()} ";
		$sql .= "{$this->getWhereQuery(single: true)} ";
		$sql .= "{$this->getLimitQuery(limit: 1)};";
		return (bool)$this->execute($sql);
	}

	/**
	 * Delete rows using binded data.
	 *
	 * @access public
	 * @return int
	 */
	public function deleteAny() : int
	{
		$sql = "{$this->getDeleteQuery()} ";
		$sql .= "{$this->getWhereQuery()};";
		return (int)$this->execute($sql);
	}

	/**
	 * Get last inserted Id.
	 *
	 * @access public
	 * @return int
	 */
	public function lastInsertId() : int
	{
		$this->verify();
		return (int)$this->db->lastInsertId();
	}

	/**
	 * Custom query helper.
	 *
	 * @access public
	 * @param string $sql
	 * @param array $bind
	 * @param string $type
	 * @param int $mode
	 * @return mixed
	 */
	public function query(string $sql, ?array $bind = null, ?string $type = null, ?int $mode = null) : mixed
	{
		if ( $bind ) {
			$this->bind($bind);
		}
		return match ($type) {
			'single' => $this->getSingle($sql),
			'column' => $this->getColumn($sql),
			'row'    => $this->getRow($sql),
			default  => $this->execute($sql),
		};
	}

	/**
	 * Search rows using binded data,
	 * [readAny] aliase with custom columns, sort and limit.
	 *
	 * @access public
	 * @param mixed $columns
	 * @param array $sort
	 * @param int $limit
	 * @return array
	 */
	public function search($columns = '*', array $sort = [], ?int $limit = 0) : array
	{
		$sql = "{$this->getSelectQuery($columns)} ";
		$sql .= "{$this->getWhereQuery()} ";
		$sql .= "{$this->getSortQuery($sort)} ";
		$sql .= "{$this->getLimitQuery($limit)};";
		return (array)$this->execute($sql);
	}

	/**
	 * Search row using binded data,
	 * [read] aliase with custom columns and sort.
	 *
	 * @access public
	 * @param mixed $columns
	 * @param array $sort
	 * @return array
	 */
	public function searchOne($columns = '*', array $sort = []) : array
	{
		$sql = "{$this->getSelectQuery($columns)} ";
		$sql .= "{$this->getWhereQuery()} ";
		$sql .= "{$this->getSortQuery($sort)} ";
		$sql .= "{$this->getLimitQuery(limit: 1)};";
		return $this->getRow($sql);
	}

	/**
	 * Search column using binded data,
	 * Returns 1D array.
	 *
	 * @access public
	 * @param string $column
	 * @param array $sort
	 * @param int $limit
	 * @return array
	 */
	public function searchColumn(string $column, array $sort = [], ?int $limit = 0) : array
	{
		$sql = "{$this->getSelectQuery($column)} ";
		$sql .= "{$this->getWhereQuery()} ";
		$sql .= "{$this->getSortQuery($sort)} ";
		$sql .= "{$this->getLimitQuery($limit)};";
		return $this->getColumn($sql);
	}

	/**
	 * Select distinct rows using binded data.
	 *
	 * @access public
	 * @param mixed $columns
	 * @param array $sort
	 * @return array
	 */
	public function distinct($columns, array $sort = []) : array
	{
		$sql = "{$this->getSelectQuery($columns, distinct: true)} ";
		$sql .= "{$this->getWhereQuery()} ";
		$sql .= "{$this->getSortQuery($sort)};";
		return (array)$this->execute($sql);
	}

	/**
	 * Select older rows using binded data.
	 *
	 * @access public
	 * @param int $days
	 * @param mixed $columns
	 * @param string $col date
	 * @return array
	 */
	public function olderThan(int $days, $columns = '*', string $col = 'date') : array
	{
		$sql = "{$this->getSelectQuery($columns)} ";
		$sql .= "{$this->getWhereDateQuery($days, $col)} ";
		$sql .= "{$this->getSortQuery([$col => 'DESC'])};";
		return (array)$this->execute($sql);
	}

	/**
	 * Delete older rows using binded data.
	 *
	 * @access public
	 * @param int $days
	 * @param string $col date
	 * @return int
	 */
	public function deleteOlderThan(int $days, string $col = 'date') : int
	{
		$sql = "{$this->getDeleteQuery()} ";
		$sql .= "{$this->getWhereDateQuery($days, $col)} ";
		return (int)$this->execute($sql);
	}

	/**
	 * Select newer rows using binded data.
	 *
	 * @access public
	 * @param int $days
	 * @param mixed $columns
	 * @param string $col date
	 * @return array
	 */
	public function newerThan(int $days, $columns = '*', string $col = 'date') : array
	{
		$sql = "{$this->getSelectQuery($columns)} ";
		$sql .= "{$this->getWhereDateQuery($days, $col, operator: '>=')} ";
		$sql .= "{$this->getSortQuery([$col => 'DESC'])};";
		return (array)$this->execute($sql);
	}

	/**
	 * Delete newer rows using binded data.
	 *
	 * @access public
	 * @param int $days
	 * @param string $col date
	 * @return int
	 */
	public function deleteNewerThan(int $days, string $col = 'date') : int
	{
		$sql = "{$this->getDeleteQuery()} ";
		$sql .= "{$this->getWhereDateQuery($days, $col, operator: '>=')} ";
		return (int)$this->execute($sql);
	}

	/**
	 * Delete duplicated rows using custom columns.
	 *
	 * @access public
	 * @param array $columns
	 * @return int
	 */
	public function keepOne(array $columns = []) : int
	{
		$sql = "{$this->getDeleteDuplicatedQuery()} ";
		$sql .= "{$this->getWhereDuplicatedQuery($columns)} ";
		return (int)$this->execute($sql, true);
	}

	/**
	 * Get all rows.
	 *
	 * @access public
	 * @param mixed $columns
	 * @return array
	 */
	public function all($columns = '*', array $sort = []) : array
	{
		$sql = "{$this->getSelectQuery($columns)} ";
		$sql .= "{$this->getSortQuery($sort)};";
		return (array)$this->execute($sql, true);
	}

	/**
	 * Get min value.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function min(string $field) : mixed
	{
		$sql = "SELECT min({$field}) FROM `{$this->table}`;";
		return $this->getSingle($sql);
	}

	/**
	 * Get max value.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function max(string $field) : mixed
	{
		$sql = "SELECT max({$field}) FROM `{$this->table}`;";
		return $this->getSingle($sql);
	}

	/**
	 * Get avg value.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function avg(string $field) : mixed
	{
		$sql = "SELECT avg({$field}) FROM `{$this->table}`;";
		return $this->getSingle($sql);
	}

	/**
	 * Get sum value.
	 *
	 * @access public
	 * @param string $field
	 * @return mixed
	 */
	public function sum(string $field) : mixed
	{
		$sql = "SELECT sum({$field}) FROM `{$this->table}`;";
		return $this->getSingle($sql);
	}

	/**
	 * Count rows.
	 *
	 * @access public
	 * @param string $column
	 * @param bool $distinct
	 * @return int
	 */
	public function count(?string $column = null, bool $distinct = false) : int
	{
		$sql = $this->getCountQuery($column, $distinct);
		return (int)$this->getSingle($sql);
	}

	/**
	 * Clear table.
	 *
	 * @access public
	 * @return int
	 */
	public function clear() : int
	{
		$sql = "{$this->getDeleteQuery()};";
		return $this->execute($sql, true);
	}

	/**
	 * Reset table rows Ids.
	 *
	 * @access public
	 * @param string $table
	 * @return mixed
	 */
	public function resetId(?string $table = null) : mixed
	{
		if ( $table ) $this->table = $table;
		$sql = "ALTER TABLE `{$this->table}` AUTO_INCREMENT = 1;";
		return $this->execute($sql, true);
	}

	/**
	 * Check database table.
	 *
	 * @access public
	 * @param string $table
	 * @return bool
	 */
	public function hasTable(?string $table = null) : bool
	{
		if ( $table ) $this->table = $table;
		$sql = "SHOW TABLES LIKE '{$this->table}';";
		return (bool)$this->execute($sql, true);
	}

	/**
	 * Get database table columns.
	 *
	 * @access public
	 * @param string $table
	 * @return array
	 */
	public function columns(?string $table = null) : array
	{
		if ( $table ) $this->table = $table;
		$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ";
		$sql .= "WHERE TABLE_NAME = '{$this->table}' ";
		$sql .= "ORDER BY ORDINAL_POSITION;";
		$this->resetBind();
		return $this->getColumn($sql);
	}

	/**
	 * Get database tables.
	 *
	 * @access public
	 * @return array
	 */
	public function tables() : array
	{
		$this->resetBind();
		return $this->getColumn('SHOW TABLES;');
	}

	/**
	 * Setup database.
	 *
	 * @access public
	 * @return bool
	 */
	public function setup() : bool
	{
		try {

			$root = $this->getDbRootAccess();

			$dsn = "mysql:host={$this->access['host']};port={$this->access['port']}";
			$pdo = new PDO($dsn, $root['user'], $root['pswd']);

			$sql = "CREATE DATABASE IF NOT EXISTS `{$this->access['name']}` ";
			$sql .= "CHARACTER SET {$this->access['charset']} ";
			$sql .= "COLLATE {$this->access['collate']};";

			$query = $pdo->prepare($sql);
			return (bool)$query->execute();

		} catch (PDOException $e) {
			exit("ERROR : {$e->getMessage()}");
		}
	}

	/**
	 * Disable database connection.
	 *
	 * @access public
	 * @return void
	 */
	public static function noConnect() : void
	{
		self::$connect = false;
	}

	/**
	 * Execute query,
	 * Returns anything.
	 * 
	 * @access protected
	 * @param string $sql
	 * @param bool $reset
	 * @return mixed
	 */
	protected function execute(string $sql, bool $reset = false) : mixed
	{
		$this->verify();
		if ( $reset ) $this->resetBind();
		$sql = $this->formatSpace($sql);
		return $this->db->query($sql, $this->getBind());
	}

	/**
	 * Execute single query,
	 * Returns field value.
	 * 
	 * @access protected
	 * @param string $sql
	 * @return mixed
	 */
	protected function getSingle(string $sql) : mixed
	{
		$this->verify();
		$sql = $this->formatSpace($sql);
		return $this->db->single($sql, $this->getBind());
	}

	/**
	 * Execute column query,
	 * Returns column.
	 * 
	 * @access protected
	 * @param string $sql
	 * @return array
	 */
	protected function getColumn(string $sql) : array
	{
		$this->verify();
		$sql = $this->formatSpace($sql);
		return (array)$this->db->column($sql, $this->getBind());
	}

	/**
	 * Execute row query,
	 * Returns row.
	 * 
	 * @access protected
	 * @param string $sql
	 * @param int $mode
	 * @return array
	 */
	protected function getRow(string $sql, int $mode = 2) : array
	{
		$this->verify();
		$sql = $this->formatSpace($sql);
		$row = $this->db->row($sql, $this->getBind(), $mode);
		$this->row = ($row) ? $row : [];
		return $this->row;
	}

	/**
	 * Get insert query string.
	 * 
	 * @access private
	 * @return string
	 */
	private function getInsertQuery() : string
	{
		$sql = "INSERT INTO `{$this->table}` ";
		if ( !empty($this->bind) ) {
			$columns = $this->getColumnsString();
			$values = $this->getValueString();
			$sql .= "({$columns}) VALUES ({$values})";

		} else {
			$sql .= "() VALUES ()";
		}
		return $sql;
	}

	/**
	 * Get select query string.
	 * 
	 * @access private
	 * @param mixed $columns
	 * @param bool $distinct
	 * @return string
	 */
	private function getSelectQuery($columns = '*', ?bool $distinct = null) : string
	{
		if ( $distinct == true && $columns !== '*' ) {
			$distinct = 'DISTINCT';
		}
		$sql = "SELECT {$distinct} {$this->formatColumns($columns)} ";
		$sql .= "FROM `{$this->table}`";
		return $sql;
	}

	/**
	 * Get where query string.
	 * 
	 * @access private
	 * @param bool $single
	 * @return string
	 */
	private function getWhereQuery($single = false) : string
	{
		if ( $single ) {
			$comparator = $this->getComparator(
				$this->getValueType($this->key)
			);
			return "WHERE `{$this->key}` {$comparator} :{$this->key}";
		}
		$sql = '';
		if ( !empty($this->bind) ) {
			$sql .= 'WHERE ';
			foreach ($this->getColumns() as $key => $column) {

				$comparator = $this->getComparator($column['type']);

				// Set binded data
				$unbind = false;
				$value = ":{$column['name']}";

				// Set static data
				if ( $column['type'] == 'true' ) {
					$value = 'TRUE';
					$unbind = true;

				} elseif ( $column['type'] == 'false' ) {
					$value = 'FALSE';
					$unbind = true;

				} elseif ( $column['type'] == 'null' ) {
					$value = 'NULL';
					$unbind = true;
				}

				// Unbind data
				if ( $unbind ) {
					unset($this->bind[$column['name']]);
				}

				$sql .= "`{$column['name']}` {$comparator} {$value} AND ";
			}
			$sql = rtrim($sql, ' AND ');
		}
		return $sql;
	}

	/**
	 * Get where date query string.
	 * 
	 * @access private
	 * @param int $days
	 * @param string $col date
	 * @param string $operator
	 * @return string
	 */
	private function getWhereDateQuery(int $days, string $col = 'date', string $operator = '<=') : string
	{
		$days = ($days) ? $days : 1;
		$where = $this->getWhereQuery();
		if ( empty($where) ) {
			$where = 'WHERE ';

		} else {
			$where .= ' AND ';
		}
		$where .= "( {$days} {$operator} DATEDIFF(NOW(), `{$col}`) )";
		return $where;
	}

	/**
	 * Get where duplicated query string.
	 * 
	 * @access private
	 * @param array $columns
	 * @return string
	 */
	private function getWhereDuplicatedQuery(array $columns = []) : string
	{
		$sql = "WHERE i1.`{$this->key}` > i2.`{$this->key}`";
		foreach ($columns as $key => $column) {
			if ( $column !== $this->key ) {
				$sql .= " AND i1.`{$column}` = i2.`{$column}` ";
			}
		}
		return $sql;
	}

	/**
	 * Parse custom where query string.
	 * 
	 * @access private
	 * @param array $where
	 * @return string
	 */
	private function parseWhereQuery(array $where = []) : string
	{
		$sql = '';
		if ( !empty($where) ) {
			$sql .= 'WHERE ';
			foreach ($where as $key => $value) {
				$type = $this->getValueType($value);
				if ( $type == 'char' ) {
					$value = "'{$value}'";
				}
				$sql .= "`{$key}` {$this->getComparator($type)} {$value} AND ";
			}
			$sql = rtrim($sql, ' AND ');
		}
		return $sql;
	}

	/**
	 * Get update query string.
	 * 
	 * @access private
	 * @return string
	 */
	private function getUpdateQuery() : string
	{
		$sql = "UPDATE `{$this->table}`";
		$update = '';
		foreach ($this->getColumns() as $column) {
			// Ignore primary key
			if ( $column['name'] !== $this->key ) {
				$update .= "`{$column['name']}` = :{$column['name']}, ";
			}
		}
		if ( !empty($update) ) {
			$update = rtrim($update, ', ');
			$sql .= " SET {$update}";
		}
		return $sql;
	}

	/**
	 * Get delete query string.
	 * 
	 * @access private
	 * @return string
	 */
	private function getDeleteQuery() : string
	{
		$sql = "DELETE FROM `{$this->table}`";
		return $sql;
	}

	/**
	 * Get delete duplicated query string.
	 * 
	 * @access private
	 * @return string
	 */
	private function getDeleteDuplicatedQuery() : string
	{
		$sql = "DELETE i1 FROM `{$this->table}` i1, `{$this->table}` i2";
		return $sql;
	}

	/**
	 * Get count query string.
	 *
	 * @access private
	 * @param string $column
	 * @param bool $distinct
	 * @return string
	 */
	private function getCountQuery(?string $column = null, bool $distinct = false) : string
	{
		$column = $column ?: $this->key ?: '*';

		if ( $distinct ) {
			$column = "DISTINCT {$column}";
		}
		$sql = "SELECT COUNT({$column}) FROM `{$this->table}` ";
		$sql .= "{$this->getWhereQuery()};";
		return $sql;
	}

	/**
	 * Get sort query string.
	 *
	 * @access private
	 * @param array $sort
	 * @return string
	 */
	private function getSortQuery(array $sort = []) : string
	{
		$sql = '';
		if ( !empty($sort) ) {
			$sorted = [];
			foreach ($sort as $key => $value) {
				$sorted[] = "`{$key}` {$value}";
			}
			$sql .= 'ORDER BY ' . implode(', ', $sorted);
		}
		return $sql;
	}

	/**
	 * Get limit query string.
	 *
	 * @access private
	 * @param int $limit
	 * @return string
	 */
	private function getLimitQuery(?int $limit = 0) : string
	{
		$sql = '';
		if ( $limit ) {
			$sql .= "LIMIT {$limit}";
		}
		return $sql;
	}

	/**
	 * Get columns names with types from bind.
	 * 
	 * @access private
	 * @return array
	 */
	private function getColumns() : array
	{
		$columns = [];
		foreach ($this->bind as $name => $value) {
			$columns[] = [
				'name' => $name,
				'type' => $this->getValueType($value)
			];
		}
		return $columns;
	}

	/**
	 * Get columns as string.
	 * 
	 * @access private
	 * @return string
	 */
	private function getColumnsString() : string
	{
		return $this->formatColumns(
			$this->getColumns()
		);
	}

	/**
	 * Format columns string (Binded || Custom).
	 * 
	 * @access private
	 * @param mixed $columns
	 * @return string
	 */
	private function formatColumns($columns) : string
	{
		if ( $this->isType('string', $columns) ) {
			$columns = $this->stripSpace($columns);
			if ( $columns == '*' || empty($columns) ) {
				return '*';
			}
			if ( $this->hasString($columns, ',') ) {
				$columns = explode(',', $columns);

			} else {
				return "`{$columns}`";
			}
		}

		if ( $this->isType('array', $columns) && !empty($columns) ) {
			$wrapper = [];
			foreach ($columns as $key => $column) {
				if ( isset($column['name']) ) {
					// Binded columns
					$wrapper[$key] = "`{$column['name']}`";

				} else {
					// Custom columns
					$wrapper[$key] = "`{$column}`";
				}
			}
			return implode(',', $wrapper);
		}

		return '*';
	}

	/**
	 * Get values string.
	 *
	 * @access private
	 * @return string
	 */
	private function getValueString() : string
	{
		$value = [];
		foreach ($this->getColumns() as $key => $column) {
			$value[$key] = $column['name'];
		}
		return ':' . implode(',:', $value);
	}

	/**
	 * Get binded data before reset.
	 *
	 * @access private
	 * @return array
	 */
	private function getBind() : array
	{
		$bind = $this->bind;
		$this->resetBind();
		return $bind;
	}

	/**
	 * Reset binded data.
	 *
	 * @access private
	 * @return void
	 */
	private function resetBind() : void
	{
		$this->bind = [];
	}

	/**
	 * Get value type [num, true, false, null, char].
	 * 
	 * @access private
	 * @param mixed $value
	 * @return string
	 */
	private function getValueType($value = null) : string
	{
		if ( $this->isType('int', $value) || $this->isType('float', $value) ) {
			return 'num';

		} elseif ( $this->isType('true', $value) ) {
			return 'true';

		} elseif ( $this->isType('false', $value) ) {
			return 'false';

		} elseif ( $this->isType('null', $value) ) {
			return 'null';
		}

		return 'char';
	}

	/**
	 * Get comparator by value type, to avoid result conflit.
	 * 
	 * @access private
	 * @param mixed $type
	 * @return string
	 */
	private function getComparator(string $type = 'char') : string
	{
		if ( $type == 'num' ) {
			return '=';

		} elseif ( $type == 'true' || $type == 'false' || $type == 'null' ) {
			return 'is';
		}

		return 'LIKE';
	}

	/**
	 * Verify object instance.
	 *
	 * @access private
	 * @return void
	 * @throws OrmException
	 */
	private function verify() : void
	{
		if ( !$this->db ) {
			throw new OrmException(
				message: OrmException::invalidDbObject()
			);
		}
	}
}
