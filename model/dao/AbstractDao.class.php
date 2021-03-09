<?php 
namespace com\microdle\model\dao;

use com\microdle\exception\SqlException;

/**
 * Microdle Framework - https://microdle.com/
 * Abstract class for Data Access Object.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.dao
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
abstract class AbstractDao {
	/**
	 * Integer field type.
	 * @var integer
	 */
	const INTEGER_FIELD_TYPE = 1;
	
	/**
	 * Big integer field type.
	 * @var integer
	 */
	const LONG_FIELD_TYPE = 2;
	
	/**
	 * Float field type.
	 * @var integer
	 */
	const FLOAT_FIELD_TYPE = 3;
	
	/**
	 * String field type.
	 * @var integer
	 */
	const STRING_FIELD_TYPE = 4;
	
	/**
	 * Boolean field type.
	 * @var integer
	 */
	const BOOLEAN_FIELD_TYPE = 5;
	
	/**
	 * Date field type.
	 * @var integer
	 */
	const DATE_FIELD_TYPE = 6;
	
	/**
	 * Bind parameters statement.
	 * @var string
	 */
	const BIND_PARAM_STATEMENT = 'bind_param';
	
	/**
	 * Execute statement.
	 * @var string
	 */
	const EXECUTE_STATEMENT = 'execute';
	
	/**
	 * Bind result statement.
	 * @var string
	 */
	const BIND_RESULT_STATEMENT = 'bind_result';
	
	/**
	 * Sort by ascending.
	 * @var string
	 */
	const ASCENDING_SORT = 'ASC';
	
	/**
	 * Sort by descending.
	 * @var string
	 */
	const DESCENDING_SORT = 'DESC';
	
	/**
	 * Data source type.
	 * @var string
	 */
	protected ?string $_dataSourceType = null;
	
	/**
	 * Database connection.
	 * @var object
	 */
	protected $_connection = null;
	
	/**
	 * Table name.
	 * @var string
	 */
	protected ?string $_tableName = null;
	
	/**
	 * Table fields definition.
	 * @var array
	 */
	protected ?array $_fields = null;
	
	/**
	 * Primary key.
	 * @var array
	 */
	protected ?array $_primaryKey = null;
	
	/**
	 * Return page of records collection.
	 * @param array $filters (optional) Associative array.
	 * @param integer $page (optional) Page number, beginning by 1.
	 * @param integer $quantity (optional) Number of records per page.
	 * @param boolean $count (optional) Return total quantity.
	 * @param string $orderClause (optional) Order clause. null by default.
	 * @return array ['results'=>Elements collection, 'count'=>Total quantity]
	 */
	abstract public function getPage(array $filters = null, int $page = 1, int $quantity = 10, bool $count = true, string $orderClause = null): array;
	
	/**
	 * Return data by primary key or index.
	 * @param array $data Primary key or index in associative array.
	 * @return array Array if found, otherwise null.
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function get(array &$data): ?array;
	
	/**
	 * Return all data.
	 * @param array $fieldNames (optional) Fields to retrieve. If null then retrieve all fields.
	 * @param string $orderClause (optional) Order clause (ex: "date DESC"). no order defined by default.
	 * @return array Collection of associative arrays.
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function getAll(array $fieldNames = null, string $orderClause = null): array;
	
	/**
	 * Insert new data.
	 * @param array $data New data to insert in associative array.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function insert(array &$data): void;
	
	/**
	 * Update data.
	 * @param array $data Data to update. Primary key included.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function update(array &$data): void;
	
	/**
	 * Delete data.
	 * @param array $data Primary key in associative array.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function delete(array &$data): void;
	
	/**
	 * Determine existence by primary key.
	 * @param array $data Primary key in associative array.
	 * @return boolean
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function exists(array &$data): bool;
	
	/**
	 * Check record existence by columns.
	 * @param array $fields Field names and values associated.
	 * @return boolean true if record exists, otherwise false.
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function existsByFields(array $fields): bool;
	
	/**
	 * Return identifier by field names.
	 * @param array $fields Field names and values associated.
	 * @return array Identifier in array format if found, otherwise null.
	 * @throws \com\microdle\exception\SqlException
	 */
	abstract public function getIdByFields(array $fields): ?array;
	
	/**
	 * Constructor.
	 * @param object $connection Database connection.
	 * @param string $tableName Table name.
	 * @param array $fields Table fields definition.
	 * @param array $primaryKey (optional) Primary key.
	 * @return void
	 */
	public function __construct(object $connection, $tableName, array $fields = [], array $primaryKey = null) {
		$this->_connection = $connection;
		$this->_tableName = $tableName;
		$this->_fields = $fields;
		$this->_primaryKey = !empty($primaryKey) ? $primaryKey : null;
	}
	
	/**
	 * Throw Sql Exception.
	 * @param mysqli_stmt $stmt PDO statement.
	 * @param string (optional) Statement function name: self::BIND_PARAM_STATEMENT, self::EXECUTE_STATEMENT or self::BIND_RESULT_STATEMENT. self::BIND_PARAM_STATEMENT by default.
	 * @param string (optional) $message Supplementary message. Null by default.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	protected function _throwSqlException(\mysqli_stmt &$stmt, string $stmtFunctionName = self::BIND_PARAM_STATEMENT, string $message = null): void {
		$msg = empty($stmt->error) ? $stmtFunctionName : $stmt->error;
		$cod = $stmt->errno;
		$stmt->close();
		throw new SqlException($message ? $message . ' - ' . $msg : $msg, $cod);
	}
	
	/**
	 * Determine if the field exists.
	 * @param string $name Field name.
	 * @return bool
	 */
	public function existsField(string $name): bool {
		return isset($this->_fields[$name]);
	}
	
	/**
	 * Retrieve primary key.
	 * @pram array $data Data.
	 * @return array Primary key in associative array, otherwise null.
	 */
	public function getPrimaryKey(array &$data): ?array {
		if(!$this->_primaryKey) {
			return null;
		}
		$primaryKey = [];
		foreach($this->_primaryKey as &$fieldName) {
			if(!isset($data[$fieldName])) {
				return null;
			}
			$primaryKey[$fieldName] = $data[$fieldName];
		}
		return $primaryKey;
	}
	
	/**
	 * Return a new identifier.
	 * @return integer New identifier.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getNewId(): int {
		if(!$this->_primaryKey || count($this->_primaryKey) != 1 || ($this->_fields[$this->_primaryKey[0]] != self::INTEGER_FIELD_TYPE && $this->_fields[$this->_primaryKey[0]] != self::LONG_FIELD_TYPE)) {
			throw new SqlException('Primary key integer type missing: ' . get_class($this));
		}
		$identifierName = &$this->_primaryKey[0];
		if(!($result = $this->_connection->query('SELECT MIN(' . $identifierName . '+1) AS ' . $identifierName . ' FROM ' . $this->_tableName . ' WHERE ' . $identifierName . '+1 NOT IN (SELECT ' . $identifierName . ' FROM ' . $this->_tableName . ')'))) {
			throw new SqlException($this->_connection->error);
		}
		$row = $result->fetch_assoc();
		
		$result->close();
		
		return empty($row[$identifierName]) ? 1 : $row[$identifierName];
	}
	
	/**
	 * Build where clause and reference values from a filters list.
	 * @param array $filters Filters in format: [fieldName1: [operator: ..., value: ...], ...].
	 * @return array ['where', 'referenceValues']
	 */
	public function getFiltersClause(array &$filters) : array {
		$where = '1 = 1';
		$referenceValues = [0 => ''];
		$arithmeticOperators = [
			'eq' => '=',
			'ne' => '<>',
			'le' => '<=',
			'lt' => '<',
			'ge' => '>=',
			'gt' => '>'
		];
		$likeOperators = [
			'exists' => 'LIKE ',
			'nexists' => 'NOT LIKE ',
			'starts' => 'LIKE ',
			'nstarts' => 'NOT LIKE ',
			'ends' => 'LIKE ',
			'nends' => 'NOT LIKE '
		];
		$fieldTypes = [
			self::INTEGER_FIELD_TYPE => 'i',
			self::LONG_FIELD_TYPE => 'd',
			self::FLOAT_FIELD_TYPE => 'd',
			self::STRING_FIELD_TYPE => 's',
			self::BOOLEAN_FIELD_TYPE => 'i',
			self::DATE_FIELD_TYPE => 's'
		];
		
		foreach($filters as $fieldName => &$data) {
			if(isset($arithmeticOperators[$data['operator']])) {
				$operator = $arithmeticOperators[$data['operator']];
			}
			elseif(isset($likeOperators[$data['operator']])) {
				$operator = $likeOperators[$data['operator']];
				if($data['operator'] === 'exists' || $data['operator'] === 'nexists') {
					$data['value'] = '%' . $data['value'] . '%';
				}
				elseif($data['operator'] === 'starts' || $data['operator'] === 'nstarts') {
					$data['value'] = $data['value'] . '%';
				}
				elseif($data['operator'] === 'ends' || $data['operator'] === 'nends') {
					$data['value'] = '%' . $data['value'];
				}
			}
			else {
				$operator = '=';
			}
			
			//Define field type
			//Case $fieldName = <fieldName>
			if(isset($this->_fields[$fieldName])) {
				$fieldType = $this->_fields[$fieldName];
			}
			
			//Case $fieldName = <tableName>.<fieldName>
			else {
				$t = explode('.', $fieldName);
				if(isset($t[1]) && isset($this->_fields[$t[1]])) {
					$fieldType = $this->_fields[$t[1]];
				}
				else {
					//By default string field type
					$fieldType = self::DATE_FIELD_TYPE;
				}
			}
			
			//Case simple value
			if(!is_array($data['value'])) {
				$where .= ' AND ' . $fieldName . ' ' . $operator . ' ?';
				$referenceValues[0] .= $fieldTypes[$fieldType];
				$referenceValues[] = &$data['value'];
			}
			
			//Case multiple value
			else {
				$t = [];
				foreach($data['value'] as &$value) {
					$t[] = '?';
					$referenceValues[0] .= $fieldTypes[$fieldType];
					$referenceValues[] = &$value;
				}
				$where .= ' AND ' . $fieldName . ' IN (' . implode(',', $t) . ')';
			}
		}
		
		return [
			'where' => &$where,
			'referenceValues' => &$referenceValues
		];
	}
}
?>