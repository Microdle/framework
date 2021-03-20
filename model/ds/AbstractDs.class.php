<?php 
namespace com\microdle\model\ds;

require_once $_ENV['FRAMEWORK_ROOT'] . '/model/dao/AbstractDao' . $_ENV['FILE_EXTENSIONS']['class'];

/**
 * Microdle Framework - https://microdle.com/
 * Abstract Data source.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.ds
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
abstract class AbstractDs {
	/**
	 * Data source connection, set in ::openConnection.
	 * @var resource
	 */
	protected $_connection = null;
	
	/**
	 * Collection of DAO instances.
	 * @var array
	 */
	protected array $_daos = [];
	
	/**
	 * Data source name.
	 * @var string 
	 */
	protected ?string $_dataSourceName = null;
	
	/**
	 * Open data source connection, disable autocommit by default et begin transaction.
	 * @param array $configurationData Configuration data to create data source connection.
	 * @return void
	 */
	abstract public function openConnection(array $configurationData): void;
	
	/**
	 * Close data source connection.
	 * @return void
	 */
	abstract public function closeConnection(): void;
	
	/**
	 * Active autocommit.
	 * @param boolean $active true to autocommit, otherwise inactive autocommit. false by default.
	 * @return void
	 */
	abstract public function autocommit(bool $active = false): void;
	
	/**
	 * Begin data source transaction.
	 * @return void
	 */
	abstract public function beginTransaction(): void;
	
	/**
	 * Validate transaction. Set $this->_commitStatus = true if successful.
	 * @return void
	 */
	abstract public function commit(): void;
	
	/**
	 * Avoid transaction.
	 * @return void
	 */
	abstract public function rollBack(): void;
	
	/**
	 * Execute a request (insert, update, delete, get).
	 * @param string $sql Request.
	 * @return array
	 */
	abstract public function query(string $sql): ?array;
	
	/**
	 * Execute a prepared statement (insert, update, delete, get).
	 * @param string $sql Request.
	 * @param array $parameters Array of parameters.
	 * @param astring $sqlName (optional) SQL name. "mySql" by default. This parameter is used for PostgreSQL.
	 * @return array
	 */
	abstract public function prepare(string $sql, array &$parameters, string $sqlName = 'mySql'): ?array;
	
	/**
	 * Load and create dao instance property. It is just a DAO factory.
	 * @param string $className DAO class name.
	 * @return com\microdle\model\dao\AbstractDao
	 */
	public function __get(string $className): object {
		//Case DAO is already instanciated
		if(isset($this->_daos[$className])) {
			return $this->_daos[$className];
		}
		
		//Case not DAO
		if(substr($className, -3) !== $_ENV['CLASS_SUFFIXES']['dao']) {
			throw new \com\microdle\exception\FileNotFoundException('DAO call: ' . substr($className, -3) . 'Dao is expected instead of ' . $className . '.');
		}
		
		//Case DAO is not instanciated yet
		//Load DAO class file: fatal error if file does not exist
		$dataSourceName = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0]['object']->key;
		$classFileName = ucfirst($className);
		
		//Case DAO class exists in the project
		if(is_file($fileName = $_ENV['ROOTS']['dao'] . '/' . $dataSourceName . '/' . $classFileName . $_ENV['FILE_EXTENSIONS']['class'])) {
			//Set dao class namespace
			$class = '\\model\\dao\\' . $dataSourceName . '\\' . $classFileName;
		}

		//Case DAO class exists in the framework
		elseif(is_file($fileName = $_ENV['FRAMEWORK_ROOT'] . '/model/dao/' . $classFileName . $_ENV['FILE_EXTENSIONS']['class'])) {
			$class = 'com\\microdle\\model\\dao\\' . $classFileName;
		}

		//Case DAO class does not exist
		else {
			throw new \com\microdle\exception\FileNotFoundException('Load DAO impossible: ' . $_ENV['ROOTS']['dao'] . '/' . $dataSourceName . '/' . $classFileName . $_ENV['FILE_EXTENSIONS']['class']);
		}
		
		//Load class now, faster than class loader
		require $fileName;

		//Create and return dao instance
		return $this->_daos[$className] = new $class($this->_connection);
	}
	
	/**
	 * Initialize data source.
	 * @param array $configurationData (optional) Configuration data to create data source connection. If null, then call ::openConnection to create data source connection.
	 * @return void
	 */
	public function __construct(array $configurationData = null) {
		if($configurationData !== null) {
			//Store data source name
			if(isset($configurationData['dataSourceName'])) {
				$this->_dataSourceName = $configurationData['dataSourceName'];
			}
			
			//Open connection
			$this->openConnection($configurationData);
		}
	}
	
	/**
	 * Return data source connection.
	 * @return object Data source connection if found, otherwise null.
	 */
	public function getConnection(): ?object {
		return $this->_connection;
	}
}
?>