<?php 
namespace com\microdle\library\core;

/**
 * Microdle Framework - https://microdle.com/
 * Redis Manager to access to DAO via Data Source. This class extends Redis (phpredis).
 * @author Vincent SOYSOUVANH
 * @package com.microdle.library.core
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class RedisManager extends \Redis {
	/**
	 * Output formats list initialized in the constructor.
	 * @var array
	 */
	static public ?array $outputFormats = null;
	
	/**
	 * BO passed in the constructor.
	 * @var \com\microdle\model\bo\AbstractBo
	 */
	protected \com\microdle\model\bo\AbstractBo $_bo;
	
	/**
	 * Data source passed in the constructor.
	 * @var \com\microdle\model\ds\AbstractDs
	 */
	protected \com\microdle\model\ds\AbstractDs $_ds;
	
	/**
	 * Open a Redis connection.
	 * @param \com\microdle\model\ds\Abstract (optional) $ds Data source instance. null by default.
	 * @param array $configurationData (optional) 'serverName' and 'port' expected (ex: ['serverName' => '127.0.0.1', 'port' => 6379]). null by default.
	 * @return void
	 */
	public function __construct(\com\microdle\model\bo\AbstractBo $bo, \com\microdle\model\ds\AbstractDs $ds = null, array $configurationData = null) {
		//Initialize Redis
		parent::__construct();
		
		//Case default configuration
		if($configurationData === null) {
			//Load data sources configuration: $_dataSources
			$_dataSources = null;
			require $dataSourceFile = $_ENV['ROOTS']['configuration'] . '/' . $_SERVER['HTTP_HOST'] . $_ENV['FILE_EXTENSIONS']['dataSource'];
			
			//Retrieve configuration data
			$configurationData = $_dataSources['redis'];
		}
		
		//Create Redis connection
		$this->connect(
			$configurationData['serverName'],
			$configurationData['port'],
			$configurationData['timeout'],
			$configurationData['reserved'],
			$configurationData['retryInterval'],
			$configurationData['readTimeout']
		);
		
		//Set BO
		$this->_bo = &$bo;
		
		//Set DS
		$this->_ds = &$ds;
		
		//Set output formats if not initialized yet
		if(self::$outputFormats === null) {
			//Get request onstance
			$request = $this->_bo->getRequest();
			
			//Initialize output formats: values can be customized on public access
			self::$outputFormats = [
				'csv' => [
					'object' => &$request,
					'methodName' => 'arrayToCsv',
					'defaultArguments' => [
						'delimiter' => ',',
						'enclosure' => '"',
						'escapeChar' => '\\'
					]
				],
				'json' => [
					'object' => null,
					'methodName' => 'json_encode',
					'defaultArguments' => null
				],
				'xml' => [
					'object' => &$request,
					'methodName' => 'arrayToXml',
					'defaultArguments' => [
						'rootElement' => '<root/>'
					]
				]
			];
		}
	}
	
	/**
	 * Remove data with specified parameters.
	 * @param string $tableName Database table name. Example: "user".
	 * @param array $pk Primary key or unique in associative array. Example: ['id' => 41, 'city' => 'Vendôme'].
	 * @param string $format (optional) Content format. Example csv, json, xml. "json" by default.
	 * @param string $keyDelimiter (optional) Delimiter to build Redis key. "|" by default.
	 * @return void
	 */
	public function remove(string $tableName, array $pk, string $format = 'json', string $keyDelimiter = '|'): void {
		//Build key
		//$key = $format . $keyDelimiter . $tableName . $keyDelimiter . implode($keyDelimiter, $pk);
		
		//Delete data
		$this->del($format . $keyDelimiter . $tableName . $keyDelimiter . implode($keyDelimiter, $pk));
	}
	
	/**
	 * Retrieve data by primary key and return in XML format.
	 * @param string $tableName Database table name. Example: "user".
	 * @param array $pk Primary key or unique in associative array. Example: ['id' => 41, 'city' => 'Vendôme'].
	 * @param \com\microdle\model\ds\AbstractDs $ds (optional) Data source to retrieve data if not exists in Redis. null by default to not use data source.
	 * @param string $format (optional) String format to return. "csv", "json", or "xml" is expected. "json" by default.
	 * @param string $keyDelimiter (optional) Key delimiter to build Redis key. "|" by default.
	 * @return string CSV|JSON|XML
	 */
	public function getBy(string $tableName, array $pk, \com\microdle\model\ds\AbstractDs $ds = null, string $format = 'json', string $keyDelimiter = '|'): string {
		//Build key
		$key = $format . $keyDelimiter . $tableName . $keyDelimiter . implode($keyDelimiter, $pk);
		
		//Retrieve data
		$string = $this->get($key);
		
		//Case data does not exist
		if($string === false) {
			//Case no data source
			if($ds === null && $this->_ds === null) {
				return null;
			}
			
			//Build dao name
			$dao = $tableName . 'Dao';
			
			//Retrieve data from data source
			$data = ($ds !== null ? $ds : $this->_ds)->$dao->get($pk);
			
			//Case no data
			if($data === null) {
				return null;
			}
			
			//Determine function to execute
			$function = self::$outputFormats[$format]['object'] !== null
				? [self::$outputFormats[$format]['object'], self::$outputFormats[$format]['methodName']]
				: self::$outputFormats[$format]['methodName'];
			
			//Build function arguments
			$args = [&$data];
			if(self::$outputFormats[$format]['defaultArguments'] !== null) {
				foreach(self::$outputFormats[$format]['defaultArguments'] as &$value) {
					$args[] = &$value;
				}
			}
			
			//Convert data to the desired format
			$data = call_user_func_array($function, $args);
			
			//Save data in Redis
			$this->set($key, $data);
			
			//Return data
			return $data;
		}
		
		//Return value
		return $string;
	}
	
	/**
	 * Retrieve data by primary key and return in XML format.
	 * @param string $tableName Database table name. Example: "user".
	 * @param array $filters (optional) Filters in associative array. Example: ['id' => 41, 'city' => 'Vendôme']. null by default.
	 * @param \com\microdle\model\ds\AbstractDs $ds (optional) Data source to retrieve data if not exists in Redis. null by default to not use data source.
	 * @param string $format (optional) String format to return. "csv", "json", or "xml" is expected. "json" by default.
	 * @param integer $page (optional) Page number, beginning by 1. 1 by default.
	 * @param integer $quantity (optional) Number of records per page. 1000 by default.
	 * @param string $orderClause (optional) Order clause. Example: "country, city". null by default.
	 * @param string $keyDelimiter (optional) Key delimiter to build Redis key. "|" by default.
	 * @return string CSV|JSON|XML
	 */
	public function getList(string $tableName, array $filters = null, \com\microdle\model\ds\AbstractDs $ds = null, string $format = 'json', int $page = 1, int $quantity = 1000, string $orderClause = null, string $keyDelimiter = '|'): string {
		//Build key
		$key = $format . $keyDelimiter . $tableName;
		if($filters !== null) {
			$key .= $keyDelimiter . implode($keyDelimiter, $filters);
		}
		
		//Retrieve data
		$string = $this->get($key);
		
		//Case data does not exist
		if($string === false) {
			//Case no data source
			if($ds === null && $this->_ds === null) {
				return null;
			}
			
			//Build dao name
			$dao = $tableName . 'Dao';
			
			//Retrieve data from data source
			$data = ($ds !== null ? $ds : $this->_ds)->$dao->getPage($filters, $page, $quantity, true, $orderClause);
			
			//Case no data
			if($data === null) {
				return null;
			}
			
			//Determine function to execute
			$function = self::$outputFormats[$format]['object'] !== null
				? [self::$outputFormats[$format]['object'], self::$outputFormats[$format]['methodName']]
				: self::$outputFormats[$format]['methodName'];
			
			//Build function arguments
			$args = [&$data];
			if(self::$outputFormats[$format]['defaultArguments'] !== null) {
				foreach(self::$outputFormats[$format]['defaultArguments'] as &$value) {
					$args[] = &$value;
				}
			}
			
			//Convert data to the desired format
			$data = call_user_func_array($function, $args);
			
			
			//Save data in Redis
			$this->set($key, $data);
			
			//Return data
			return $data;
		}
		
		//Return value
		return $string;
	}
}
?>