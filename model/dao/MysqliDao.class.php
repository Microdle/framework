<?php 
namespace com\microdle\model\dao;

/**
 * Microdle Framework - https://microdle.com/
 * MySQL/MariaDB Data Access Object.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.dao
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class MysqliDao extends \com\microdle\model\dao\AbstractDao {
	/**
	 * Constructor.
	 * @param object $connection Database connection.
	 * @param string $tableName Table name.
	 * @param array $fields (optional) Table fields definition. Empty array by default.
	 * @param array $primaryKey (optional) Primary key.
	 * @return void
	 */
	public function __construct(object $connection, string $tableName, array $fields = [], array $primaryKey = null) {
		parent::__construct($connection, $tableName, $fields, $primaryKey);
		
		//Set data source type
		$this->_dataSourceType = 'mysqli';
	}
	
	/**
	 * Return "where" or "set" bind clause.
	 * @param array $data Data.
	 * @param string $delimiter " AND " for where clause, ", " for set clause.
	 * @return string Clause.
	 */
	public function getBindClause(array &$data, string $delimiter): string {
		$fieldNames = array_keys($data);
		$clause = '';
		foreach($fieldNames as &$fieldName) {
			$clause .= $delimiter.$fieldName . ' = ?';
		}
		return substr($clause, strlen($delimiter));
	}
	
	/**
	 * Return bind parameter type according to a field type.
	 * @param integer $fieldType Field type.
	 * @return string Bind parameter type.
	 */
	protected function _getBindParameterType(int $fieldType): string {
		return $fieldType === self::INTEGER_FIELD_TYPE || $fieldType === self::BOOLEAN_FIELD_TYPE
			? 'i'
			: ($fieldType === self::LONG_FIELD_TYPE || $fieldType === self::FLOAT_FIELD_TYPE ? 'd' : 's');
	}
	
	/**
	 * Return values by reference sorted by field names.
	 * @param array $data Data.
	 * @param array $sortedFieldNames Sorted field names.
	 * @return array
	 */
	public function getReferenceValues(array &$data, array &$sortedFieldNames = null): array {
		$fieldNames = array_keys($sortedFieldNames ? $sortedFieldNames : $data);
		
		//Build values passed by reference
		//$values[0] is the values types list
		$values = [0 => ''];
		foreach($fieldNames as $fieldName) {
			$values[0] .= $this->_getBindParameterType($this->_fields[$fieldName]);
			$values[] = &$data[$fieldName];
		}
		
		return $values;
	}
	
	/**
	 * Update by columns.
	 * @param array $data Data to update. Primary key included.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	public function updateColumns(array &$data): void {
		//Retrieve primary key
		$primaryKey = $this->getPrimaryKey($data);
		if(!$primaryKey) {
			throw new \com\microdle\exception\SqlException('Primary key required to update data: '.$this->_tableName);
		}
		$setData = $data;
		foreach($primaryKey as $fieldName => &$value) {
			unset($setData[$fieldName]);
		}
		
		if(!($stmt = $this->_connection->prepare('UPDATE ' . $this->_tableName . ' SET ' . $this->getBindClause($setData, ', ') . ' WHERE ' . $this->getBindClause($primaryKey, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		$setData = array_merge($setData, $primaryKey);
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($setData));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'update: ' . \print_r($data, true));
		}
		
		$stmt->close();
	}
	
	/**
	 * Return data by primary key or index.
	 * @param array $data Primary key or index in associative array.
	 * @return array Array if found, otherwise null.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function get(array &$data): ?array {
		//Retrieve primary key
		$fields = $this->getPrimaryKey($data);
		if(!$fields) {
			throw new \com\microdle\exception\SqlException('Primary key required to get data: ' . $this->_tableName);
		}
		
		if(!($stmt = $this->_connection->prepare('SELECT * FROM ' . $this->_tableName . ' WHERE ' . $this->getBindClause($fields, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($data, $fields));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'get: ' . \print_r($data, true));
		}
		
		//Create dynamically variables for binding
		$fields = [];
		$meta = $stmt->result_metadata();
		while($field = $meta->fetch_field()) {
			$var = $field->name;
			$$var = null;
			$fields[$var] = &$$var;
		}
		
		//Bind results
		call_user_func_array([$stmt, 'bind_result'], $fields);
		
		//Fetch results
		$row = $stmt->fetch();
		
		$stmt->close();
		
		return $row ? $fields : null;
	}
	
	/**
	 * Return page of records collection.
	 * @param array $filters (optional) Associative array.
	 * @param integer $page (optional) Page number, beginning by 1.
	 * @param integer $quantity (optional) Number of records per page.
	 * @param boolean $count (optional) Return total quantity.
	 * @param string $orderClause (optional) Order clause. null by default.
	 * @return array ['results'=>Elements collection, 'count'=>Total quantity]
	 */
	public function getPage(array $filters = null, int $page = 1, int $quantity = 10, bool $count = true, string $orderClause = null): array {
		throw new \com\microdle\exception\SqlException(__METHOD__ . ' is not implemented.');
	}
	
	/**
	 * Return all data.
	 * @param array $fieldNames (optional) Fields to retrieve. If null then retrieve all fields.
	 * @param string $orderClause (optional) Order clause (ex: "date DESC"). no order defined by default.
	 * @return array Collection of associative arrays.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getAll(array $fieldNames = null, string $orderClause = null): array {
		$fieldNames = $fieldNames && count($fieldNames) ? implode(',', $fieldNames) : '*';
		$sql = 'SELECT ' . $fieldNames . ' FROM ' . $this->_tableName . ' WHERE 1';
		if(!empty($orderClause)) {
			$sql .= ' ORDER BY ' . $orderClause;
		}
		
		if(!($result = $this->_connection->query($sql))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		
		$data = [];
		while($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		
		$result->close();
		
		return $data;
	}
	
	/**
	 * Insert new data.
	 * @param array $data New data to insert in associative array.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	public function insert(array &$data): void {
		if(!($stmt = $this->_connection->prepare('INSERT INTO ' . $this->_tableName . ' VALUES(' . substr(str_repeat(',?', count($this->_fields)), 1) . ')'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		/*
		$reflectionClass = new ReflectionClass('mysqli_stmt');
		$reflectionClass->getMethod('bind_param')->invokeArgs($stmt, $this->getReferenceValues($this->_fields, $data));
		*/
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($data, $this->_fields));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'insert: ' . \print_r($data, true));
		}
		
		$stmt->close();
	}
	
	/**
	 * Update data.
	 * @param array $data Data to update. Primary key included.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	public function update(array &$data): void {
		//Retrieve primary key
		$primaryKey = $this->getPrimaryKey($data);
		if(!$primaryKey) {
			throw new \com\microdle\exception\SqlException('Primary key required to update data: '.$this->_tableName);
		}
		$setData = $data;
		foreach($primaryKey as $fieldName=>&$value) {
			unset($setData[$fieldName]);
		}
		
		if(!($stmt = $this->_connection->prepare('UPDATE ' . $this->_tableName . ' SET ' . $this->getBindClause($setData, ', ') . ' WHERE ' . $this->getBindClause($primaryKey, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		$setData = array_merge($setData, $primaryKey);
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($setData));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'update: ' . \print_r($data, true));
		}
		
		$stmt->close();
	}
	
	/**
	 * Delete data.
	 * @param array $data Primary key in associative array.
	 * @return void
	 * @throws \com\microdle\exception\SqlException
	 */
	public function delete(array &$data): void {
		//Retrieve primary key
		$primaryKey = $this->getPrimaryKey($data);
		if(!$primaryKey) {
			throw new \com\microdle\exception\SqlException('Primary key required to delete data: '.$this->_tableName);
		}
		
		if(!($stmt = $this->_connection->prepare('DELETE FROM ' . $this->_tableName . ' WHERE ' . $this->getBindClause($primaryKey, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($data, $primaryKey));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'delete: ' . \print_r($data, true));
		}
		
		$stmt->close();
	}
	
	/**
	 * Determine existence by primary key.
	 * @param array $data Primary key in associative array.
	 * @return boolean
	 * @throws \com\microdle\exception\SqlException
	 */
	public function exists(array &$data): bool {
		//Retrieve primary key
		$primaryKey = $this->getPrimaryKey($data);
		if(!$primaryKey) {
			throw new \com\microdle\exception\SqlException('Primary key required to determine data existence: '.$this->_tableName);
		}
		
		if(!($stmt = $this->_connection->prepare('SELECT 1 FROM ' . $this->_tableName . ' WHERE ' . $this->getBindClause($primaryKey, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($data, $primaryKey));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'exists: ' . \print_r($data, true));
		}
		$n = 0;
		if(!$stmt->bind_result($n)) {
			$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT, 'exists: ' . \print_r($data, true));
		}
		
		$stmt->fetch();
		
		$stmt->close();
		
		return $n === 1;
	}
	
	/**
	 * Check record existence by columns.
	 * @param array $fields Field names and values associated.
	 * @return boolean true if record exists, otherwise false.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function existsByFields(array $fields): bool {
		if(!($stmt = $this->_connection->prepare('SELECT 1 FROM ' . $this->_tableName . ' WHERE ' . $this->getBindClause($fields, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($fields));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'existsByFields: ' . \print_r($fields, true));
		}
		$n = 0;
		if(!$stmt->bind_result($n)) {
			$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT, 'existsByFields: ' . \print_r($fields, true));
		}
		
		$stmt->fetch();
		
		$stmt->close();
		
		return $n === 1;
	}
	
	/**
	 * Return identifier by field names.
	 * @param array $fields Field names and values associated.
	 * @return array Identifier in array format if found, otherwise null.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getIdByFields(array $fields): ?array {
		if(!($stmt = $this->_connection->prepare('SELECT ' . implode(', ', $this->_primaryKey) . ' FROM ' . $this->_tableName . ' WHERE ' . $this->getBindClause($fields, ' AND ')))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		call_user_func_array([$stmt, 'bind_param'], $this->getReferenceValues($fields));
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT, 'getIdByFields: ' . \print_r($fields, true));
		}
		
		//Create dynamically variables for binding
		$fields = [];
		foreach($this->_primaryKey as $name) {
			$$name = null;
			$fields[$name] = &$$name;
		}
		
		//Bind results
		call_user_func_array([$stmt, 'bind_result'], $fields);
		
		//Fetch results
		$row = $stmt->fetch();
		
		$stmt->close();
		
		return $row ? $fields : null;
	}
}
?>