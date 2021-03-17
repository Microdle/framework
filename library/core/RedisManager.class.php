<?php 
namespace com\microdle\library\core;

/**
 * Microdle Framework - https://microdle.com/
 * Redis Manager. This class extends Redis.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.library.core
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class RedisManager extends \Redis {
	/**
	 * Open a Redis connection.
	 * @param array $configurationData (optional) 'serverName' and 'port' expected (ex: ['serverName' => '127.0.0.1', 'port' => 6379]). null by default.
	 * @return void
	 */
	public function __construct(array $configurationData = null) {
		parent::__construct();
		
		if($configurationData === null) {
			//Load data sources configuration: $_dataSources
			$_dataSources = null;
			require $dataSourceFile = $_ENV['ROOTS']['configuration'] . '/' . $_SERVER['HTTP_HOST'] . $_ENV['FILE_EXTENSIONS']['dataSource'];
			
			//Retrieve configuration data
			$configurationData = $_dataSources['redis'];
		}
		
		//Create Redis connection
		$this->connect($configurationData['serverName'], $configurationData['port']);
	}
}
?>