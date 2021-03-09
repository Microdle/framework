<?php 
namespace com\microdle\model\ds;

require_once $_ENV['FRAMEWORK_ROOT'] . '/model/ds/AbstractDs' . $_ENV['FILE_EXTENSIONS']['class'];

/**
 * Microdle Framework - https://microdle.com/
 * MySqli Data source.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.ds
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class MysqliDs extends \com\microdle\model\ds\AbstractDs {
	/**
	 * Data source name (database name).
	 * @var string
	 */
	protected ?string $_dataSourceName = null;
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::openConnection()
	 */
	public function openConnection(array $configurationData): void {
		//Open data source
		$this->_connection = new \mysqli(
			$configurationData['serverName'],
			$configurationData['userName'],
			$configurationData['password'],
			$configurationData['dataSourceName'],
			$configurationData['port']
		);
		if(mysqli_connect_errno()) {
			throw new \com\microdle\exception\DatabaseConnectionException(mysqli_connect_error(), mysqli_connect_errno());
		}
		
		//Use UTF8
		$this->_connection->query('SET NAMES \'utf8\'');
		$this->_dataSourceName = $configurationData['dataSourceName'];
		
		//Disable autocommit by default, and begin transaction
		$this->autocommit(false);
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::closeConnection()
	 */
	public function closeConnection(): void {
		if($this->_connection !== null) {
			$this->_connection->close();
			$this->_connection = null;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::autocommit()
	 */
	public function autocommit(bool $active = false): void {
		if($this->_connection !== null) {
			$this->_connection->autocommit($active);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::beginTransaction()
	 */
	public function beginTransaction(): void {
		if($this->_connection !== null) {
			//Disable autocommit
			$this->_connection->autocommit(false);
			
			//Begin transaction
			$this->_connection->begin_transaction();
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::commit()
	 */
	public function commit(): void {
		if($this->_connection !== null) {
			//Commit transaction
			$this->_connection->commit();
			
			//Restore autocommit by default
			$this->_connection->autocommit(true);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::rollBack()
	 */
	public function rollBack(): void {
		if($this->_connection !== null) {
			//Rollback transaction
			$this->_connection->rollBack();
			
			//Restore autocommit by default
			$this->_connection->autocommit(true);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::query()
	 */
	public function query(string $sql): ?array {
		//@todo
		throw new \Exception('Method not available: ' . __METHOD__);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDs::prepare()
	 */
	public function prepare(string $sql, array &$parameters): ?array {
		//@todo
		throw new \Exception('Method not available: ' . __METHOD__);
	}
	
	/**
	 * Determines if the table exists.
	 * @param string $tableName Table name.
	 * @return bool
	 */
	public function existsTable(string $tableName): bool {
		if(!($stmt = $this->_connection->prepare('SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE (TABLE_SCHEMA = ?) AND (TABLE_NAME = ?)'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('ss', $this->_dataSourceName , $tableName)) {
			$this->_throwSqlException($stmt);
		}
		
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
		}
		if(!$stmt->bind_result($__rows)) {
			$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT);
		}
		
		$stmt->fetch();
		
		$stmt->close();
		
		return $__rows === 1;
	}
	
	/**
	 * Create table.
	 * @param array $properties Array must contain: name, engine (InnoDB by default), charset (utf8 by default), comment (optional).
	 * @param array $columns Collection of arrays: name, type (int|varchar|...), length (optional), default (optional), attribute(optional - unsigned|...), null (false|true), index (optional - primary|unique|index|...), autoIncrement (true|false), comment (optional).
	 * @return void
	 */
	public function createTable(array $properties, array $columns): void {
		//Check required data
		if(empty($properties['name'])) {
			throw new \com\microdle\exception\RequiredException('$properties[\'name\'] required.');
		}
		
		//Define defaults values
		$defaultTableProperties = [
			'engine' => 'InnoDB',
			'charset' => 'utf8',
			'comment' => 'Created on ' . date('Y-m-d H:i:s')
		];
		$defaultColumnProperties = [
			'type' => 'varchar',
			'length' => 256,
			'default' => null,
			'attribute' => 'UNSIGNED',
			'null' => false,
			'index' => null,
			'autoIncrement' => false,
			'comment' => ''
		];
		
		//Set default properties
		foreach($defaultTableProperties as $key => &$tablePropertyValue) {
			if(!isset($properties[$key])) {
				$properties[$key] = &$tablePropertyValue;
			}
		}
		foreach($defaultColumnProperties as $key => &$columnPropertyValue) {
			foreach($columns as &$columnData) {
				if(!isset($columnData[$key])) {
					//Case common property
					if($key == 'type' || $key == 'default' || $key == 'null' || $key == 'index' || $key == 'comment') {
						$columnData[$key] = &$columnPropertyValue;
					}
					
					//Case varchar or char
					if($columnData['type'] == 'varchar' || $columnData['type'] == 'char') {
						if(empty($columnData['length'])) {
							$columnData['length'] = &$defaultColumnProperties['length'];
						}
					}
					
					//Case integer
					elseif($columnData['type'] == 'tinyint' || $columnData['type'] == 'smallint' || $columnData['type'] == 'mediumint' || $columnData['type'] == 'int' || $columnData['type'] == 'bigint') {
						if($columnData['attribute'] != 'UNSIGNED' && $columnData['attribute'] !== null) {
							$columnData['attribute'] = &$defaultColumnProperties['attribute'];
						}
						if(empty($columnData['autoIncrement'])) {
							$columnData['autoIncrement'] = &$defaultColumnProperties['autoIncrement'];
						}
					}
				}
			}
		}
		
		//Generate SQL column
		$indexPrefixes = [
			'PRIMARY' => 'ADD PRIMARY KEY',
			'UNIQUE' => 'ADD UNIQUE KEY',
			'INDEX' => 'ADD KEY',
			'FULLTEXT' => 'ADD FULLTEXT KEY'
		];
		$indexes = [];
		foreach($columns as $columnName => &$columnSql) {
			//Build index
			if(isset($indexPrefixes[$columnSql['index']])) {
				$indexes[] = $indexPrefixes[$columnSql['index']] . ($columnSql['index'] != 'PRIMARY' ? ' ' . $columnName : '') . ' (' . $columnName . ')';
			}
			
			$sql = $columnName . ' ' . $columnSql['type'];
			
			//Case varchar or char
			if($columnSql['type'] == 'varchar' || $columnSql['type'] == 'char') {
				$sql .= '(' . $columnSql['length'] . ')';
			}

			//Case integer
			elseif($columnSql['type'] == 'tinyint' || $columnSql['type'] == 'smallint' || $columnSql['type'] == 'mediumint' || $columnSql['type'] == 'int' || $columnSql['type'] == 'bigint') {
				$sql .= ' ' . $columnSql['attribute'];
			}
			
			$columnSql = $sql . ' ' . ($columnSql['null'] ? 'DEFAULT' : 'NOT') . ' NULL COMMENT \'' . str_replace('\'', '\'\'', $columnSql['comment']) . '\'';
		}
		
		//Generate SQL
		$sql = 'CREATE TABLE ' . $properties['name'] . ' ('
			. implode(',', $columns)
			. ') ENGINE=' . $properties['engine'] . ' DEFAULT CHARSET=' . $properties['charset'] . ' COMMENT=\'' . str_replace('\'', '\'\'', $properties['comment']) .'\'';
		
		//Create table
		if(!$this->_connection->query($sql)) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		
		//Create indexes
		$sql = 'ALTER TABLE ' . $properties['name'] . ' ' . implode(',', $indexes);
		if(!$this->_connection->query($sql)) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
	}
}
?>