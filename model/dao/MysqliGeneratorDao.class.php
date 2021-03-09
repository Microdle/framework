<?php 
namespace com\microdle\model\dao;

/**
 * Microdle Framework - https://microdle.com/
 * Generator to generate source of Data Access Objects.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.dao
 * @see /application/model/bo/CoreWsBo.class.php
 * @uses https://xxx/core/generate-dao?dataSourceName=microdleDs
 * @uses https://xxx/core/generate-dao?dataSourceName=microdleDs&tableName=myTable
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class MysqliGeneratorDao extends \com\microdle\model\dao\MysqliDao {
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
	 * Parameter types map between bind type and method parameter type.
	 * @var array 
	 */
	static protected array $_parameterTypes = [
		'i' => 'int',
		'd' => 'float',
		's' => 'string'
	];
	
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
	 * Generate a part of the source.
	 * @param string $methodName Method name: insert, update, delete, get.
	 * @param array $data Input used in com.microdle.model.dao.template.*.
	 * @return void
	 * @throws \com\microdle\exception\DatabaseConnectionException
	 */
	protected function _generateTemplate(string $methodName, array $data): void {
		//Generate source
		$fileName = $_ENV['FRAMEWORK_ROOT'] . '/model/dao/template/' . $this->_dataSourceType . '/dao' . (!empty($methodName) ? '-' . $methodName : '') . $_ENV['FILE_EXTENSIONS']['template'];
		if(is_file($fileName)) {
			require $fileName;
		}
		else {
			echo '/*', $fileName, ' not found!*/';
		}
	}
	
	/**
	 * return all tables of the database.
	 * @return array
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getTables(): array {
		return $this->_tables;
	}
	
	/**
	 * Generate DAO source(s).
	 * @param string $dataSourceName Data source Name. See "/application/configuration/*.datasource.cfg.php".
	 * @param string $tableName (optional) Table name. If set, generate DAO only for this table, otherwise generate all DAO. 
	 * @param bool $archive (optional) if true, archive the current dao file before genarating a new one. true by default.
	 * @return array Generated tables.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function generateSource(string $dataSourceName, string $tableName = null, bool $archive = true): array {
		//Define DAO to generate
		$generatedTables = [];
		if($tableName !== null) {
			$tableKey = strtolower($tableName);
			if(!isset($this->_tables[$tableKey])) {
				return [];
			}
			$tables = [$tableKey => $tableName];
		}
		else {
			$tables = &$this->_tables;
		}
		
		//Loop on tables
		//$tables = Array ( [0] => Array ( [0] => table1 ) [1] => Array ( [0] => table2 ) [2] => Array ( [0] => table3 ) ) 
		foreach($tables as &$tableName) {
			//Retrieve index columns
			$result = $this->_connection->query('SHOW INDEXES FROM ' . $tableName);
			$columns = $result->fetch_all();
			$result->close();
			
			//Build table structure: table should have a least one column
			$keys = [];
			$columnTypes = [];
			$primaryKeys = [];
			$uniqueKeys = [];
			$indexKeys = [];
			$otherKeys = [];
			foreach($columns as &$column) {
				//column = [
				//	0 => Table (ex: customer_form 	)
				//	1 => Non_unique: 0 or 1
				//	2 => Key_name: PRIMARY or column name (ex: customerId)
				//	3 => Seq_in_index: 1, 2, 3, etc. Sequence number for index with multiple column
				//	4 => Column_name (ex: customerId)
				//	5 => Collation (ex: A)
				//	6 => Cardinality (ex: 1)
				//	7 => Sub_part (ex: NULL)
				//	8 => Packed (ex: NULL)
				//	9 => Null
				//	10 => Index_type (ex: BTREE)
				//	11 => Comment
				//	12 => Index_comment
				//	13 => Visible (ex: YES)
				//	14 => Expression (ex: NULL)
				//]
				
				//Case primary key
				if($column[2] === 'PRIMARY') {
					$primaryKeys[$column[4]] = $column[4];
				}
				
				//Case unique
				elseif($column[1] == '0'/* && $column[2] !== 'PRIMARY'*/) {
					$uniqueKeys[$column[4]] = $column[4];
				}
				
				//Case index
				elseif($column[1] == '1') {
					$indexKeys[$column[4]] = $column[4];
				}
				
				//Set keys: primary, unique, index
				$keys[$column[4]] = 1;
			}
			
			//Retrieve all columns
			//DESCRIBE|EXPLAIN <table>
			$result = $this->_connection->query('SHOW COLUMNS FROM ' . $tableName);
			$columns = $result->fetch_all();
			$result->close();
			
			foreach($columns as &$column) {
				//column = [
				//	0 => <Field name>, (ex: customerId)
				//	1 => <Type>, (ex: int(7) unsigned, varchar(64), char(3), datetime)
				//	2 => <Null>, (ex: NO, YES)
				//	3 => <Key>, (ex: PRI (primary key), UNI (unique), MUL (index))
				//	4 => <Default>,
				//	5 => <Extra>
				//]
				
				//Case not a pk, unique and index
				if(!isset($keys[$column[0]])) {
					$otherKeys[$column[0]] = $column[0];
				}
				
				//Define colum type: match on sss(d,d) format
				if(preg_match('/^([^\(]+?)(\((\d+)(,(\d+))?\).*)?$/', explode(' ', $column[1])[0], $matches)) {
					//$matches = Array(
					//	[0] => float(6,2)
					//	[1] => float
					//	[2] => (6,2)
					//	[3] => 6
					//	[4] => ,2
					//	[5] => 2
					//)
					switch($matches[1]) {
						case 'tinyint':
						case 'smallint':
						case 'mediumint':
						case 'int':
							$columnTypes[$column[0]] = [
								'bindType' => 'i',
								'daoType' => 'self::INTEGER_FIELD_TYPE'
							];
							break;
						case 'bigint':
						case 'float':
						case 'double':
						case 'real':
						case 'decimal':
							$columnTypes[$column[0]] = [
								'bindType' => 'd',
								'daoType' => 'self::LONG_FIELD_TYPE'
							];
							break;

						default:
							$columnTypes[$column[0]] = [
								'bindType' => 's',
								'daoType' => 'self::STRING_FIELD_TYPE'
							];
					}
				}

				//Should never occur
				else {
					//String by default
					$columnTypes[$column[0]] = [
						'bindType' => 's',
						'daoType' => 'self::STRING_FIELD_TYPE'
					];
				}
			}
			
			//Generate dao source file
			$daoClass = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))) . $_ENV['CLASS_SUFFIXES']['dao'];
			ob_start();
			$this->_generateTemplate(
				'',
				[
					'dataSourceType' => $this->_dataSourceType,
					'dataSourceName' => $dataSourceName,
					'tableName' => $tableName,
					'daoClass' => $daoClass,
					'primaryKeys' => $primaryKeys,
					'uniqueKeys' => $uniqueKeys,
					'indexKeys' => $indexKeys,
					'otherKeys' => $otherKeys,
					'columnTypes' => $columnTypes
				]
			);
			$source = ob_get_clean();
			$fileName = $_ENV['ROOTS']['dao'] . '/' . $dataSourceName . '/' . $daoClass . $_ENV['FILE_EXTENSIONS']['class'];
			$toGenerate = true;
			if($archive && is_file($fileName)) {
				$toGenerate = rename($fileName, str_replace($_ENV['CLASS_SUFFIXES']['dao'] . $_ENV['FILE_EXTENSIONS']['class'], $_ENV['CLASS_SUFFIXES']['dao'] . '-' . date('YmdHis') . $_ENV['FILE_EXTENSIONS']['class'], $fileName));
			}
			if($toGenerate) {
				file_put_contents($fileName, $source);
				$generatedTables[] = $tableName;
			}
			else {
				echo "\nGenerate source impossible: " . $fileName;
			}
		}
		
		return $generatedTables;
	}
}
?>