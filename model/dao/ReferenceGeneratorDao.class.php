<?php 
namespace com\microdle\model\dao;

/**
 * Microdle Framework - https://microdle.com/
 * Generator to generate reference from a table.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.dao
 * @see /application/model/bo/CoreWsBo.class.php
 * @uses http://xxx/core/generate-reference?tableName=myTable[&orderBy=fieldName]
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class ReferenceGeneratorDao extends \com\microdle\model\dao\MysqliDao {
	/**
	 * Database name.
	 * @var string
	 */
	protected ?string $_databaseName = null;
	
	/**
	 * Database tables.
	 * @var array
	 */
	protected ?array $_tables = null;
	
	/**
	 * Constructor.
	 * @param object $connection Database connection.
	 * @return void
	 * @throws \com\microdle\exception\DatabaseConnectionException
	 */
	function __construct(object $connection) {
        parent::__construct($connection, '');
		
		//Retrieve all tables
		$result = $this->_connection->query('SHOW TABLES');
		if(!$result) {
			die('No table in database: ' . $this->_databaseName . '.');
		}

		//Return array of arrays: Array ( [0] => Array ( [0] => table1 ) [1] => Array ( [0] => table2 ) [2] => Array ( [0] => table3 ) ) 
		$tables = $result->fetch_all();
		$result->close();
		
		//Set table name and dao name
		$this->_tables = [];
		foreach($tables as &$array) {
			//From Windows, table name is always in lower case
			//$tableNameDao = str_replace(' ', '', ucwords(str_replace('_', ' ', $array[0])));
			$this->_tables[strtolower($array[0])] = $array[0];
		}
	}
	
	/**
	 * Determine the keys of a table.
	 * @param string $tableName Table name.
	 * @return array
	 */
	public function getKeys(string $tableName): array {
		//DESCRIBE|EXPLAIN <table>
		$result = $this->_connection->query('SHOW COLUMNS FROM ' . $tableName);
		if ($result === false) {
			throw new \com\microdle\exception\SqlException('SQL error with table: ' . $tableName);
		}
		$columns = $result->fetch_all();
		
		//Free result set
		$result->close();

		//Build table structure: table should have a least one column
		$keys = [];
		foreach($columns as &$column) {
			//column = [
			//	0 => <Field name>, (ex: customerId)
			//	1 => <Type>, (ex: int(7) unsigned, varchar(64), char(3), datetime)
			//	2 => <Null>, (ex: NO, YES)
			//	3 => <Key>, (ex: PRI (primary key), UNI (unique), MUL (index))
			//	4 => <Default>,
			//	5 => <Extra>
			//)
			if(!isset($keys[$column[3]])) {
				$keys[$column[3]] = [];
			}
			
			//Add field name to a keys list
			$keys[$column[3]][] = $column[0];
		}
		
		//Return keys found
		return $keys;
	}
    
    /**
	 * Return all data.
     * @param string $tableName Table name.
	 * @param array $fieldNames (optional) Fields to retrieve. If null then retrieve all fields.
	 * @param string $orderClause (optional) Order clause (ex: "date DESC"). no order defined by default.
	 * @return array Collection of associative arrays.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getAllByTable(string $tableName, array $fieldNames = null, string $orderClause = null): array {
		$fieldNames = $fieldNames && count($fieldNames) ? implode(',', $fieldNames) : '*';
		$sql = 'SELECT ' . $fieldNames . ' FROM ' . $tableName . ' WHERE 1';
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
}
?>