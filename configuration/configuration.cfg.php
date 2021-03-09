<?php 
/**
 * Microdle Framework - https://microdle.com/
 * Set framework base configuration.
 * These variables are not defined as constant to allow to overwrite and/or add new configuration variables from the project configuration.
 * @author Vincent SOYSOUVANH
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
$_ENV = [
	//Framework root
	'FRAMEWORK_ROOT' => $_ENV['FRAMEWORK_ROOT'],
	
	//Content compressed before sending reponse
	'SERVER_DEFLATE_ENABLED' => true,
	
	//Application Roots
	'ROOTS' => [
		//Configuration data
		'configuration' => $_SERVER['DOCUMENT_ROOT'] . '/application/configuration',
		
		//Application resources
		'aop' => $_SERVER['DOCUMENT_ROOT'] . '/application/aop',
		'bo' => $_SERVER['DOCUMENT_ROOT'] . '/application/model/bo',
		'dao' => $_SERVER['DOCUMENT_ROOT'] . '/application/model/dao',
		'ds' => $_SERVER['DOCUMENT_ROOT'] . '/application/model/ds',
		'field' => $_SERVER['DOCUMENT_ROOT'] . '/application/field',
		'form' => $_SERVER['DOCUMENT_ROOT'] . '/application/form',
		'exception' => $_SERVER['DOCUMENT_ROOT'] . '/application/exception',
		'library' => $_SERVER['DOCUMENT_ROOT'] . '/application/library',
		'view' => $_SERVER['DOCUMENT_ROOT'] . '/application/view',
		
		//Private used to save files by the system
		'cache' => $_SERVER['DOCUMENT_ROOT'] . '/_cache',
		'log' => $_SERVER['DOCUMENT_ROOT'] . '/_log'
	],
	
	//File extensions
	'FILE_EXTENSIONS' => [
		'class' => '.class.php',
		'trait' => '.trait.php',
		'data' => '.data.php',
		'field' => '.field.php',
		'form' => '.form.php',
		'aop' => '.aop.php',
		'configuration' => '.cfg.php',
		'dataSource' => '.datasource.cfg.php',
		'routing' => '.routing.cfg.php',
		'logCfg' => '.log.cfg.php',
		'body' => '.body.php',
		'log' => '.log'
	],
	
	//Class suffixes
	'CLASS_SUFFIXES' => [
		'bo' => 'Bo',
		'dao' => 'Dao',
		'ds' => 'Ds'
	],
		
	//Protocol + domaine name: today, secured domain is required
	'HOME_URL' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'],
	
	//Environments: LOCAL is developer environment
	'IS_LOCAL' => false,
	'IS_TEST' => false,
	'IS_STAGING' => false,
	'IS_PRODUCTION' => false
];

//By default, redirect url is the request url without query string
$_SERVER['REDIRECT_URL'] = strtok($_SERVER['REQUEST_URI'], '?');

//Define classes autoloader
spl_autoload_register(function($className) {
	//Change folder separator
	$className = str_replace('\\', '/', $className);
	
	//Case Microdle framework class
	if(strpos($className, 'com/microdle/') === 0) {
		$fileName = str_replace('com/microdle/', $_ENV['FRAMEWORK_ROOT'] . '/', $className);
	}
	
	//Case application class
	else {
		$fileName = $_SERVER['DOCUMENT_ROOT'] . '/application/' . $className;
	}
	
	//Add file extension
	$fileName .= $_ENV['FILE_EXTENSIONS']['class'];
	
	//Case class file exists
	if(is_file($fileName)) {
		//Load class
		require $fileName;
	}
	
	//Case lookup class in the external library
	else {
		//Case external project library
		if(
			//Case DAO
			(substr($className, -3) == 'Dao' && is_file($fileName = $_ENV['ROOTS']['dao'] . str_replace('model/dao', '', $className) . $_ENV['FILE_EXTENSIONS']['class']))
			
			//Case BO
			|| (substr($className, -2) == 'Bo' && is_file($fileName = $_ENV['ROOTS']['bo'] . str_replace('model/bo', '', $className) . $_ENV['FILE_EXTENSIONS']['class']))
		) {
			//Load class
			require $fileName;
		}
		
		//Case external vendor library
		else {
			//Retrieve vendor name (ex: Symfony, Zend)
			$vendorName = explode('/', $className)[0];

			//Case external library exists
			if(isset($_ENV['LIBRARY'][$vendorName])) {
				//Build file to load
				$fileName = $_ENV['LIBRARY'][$vendorName]['path'] . $className . $_ENV['LIBRARY'][$vendorName]['classExtension'];

				//Case class file exists
				if(is_file($fileName)) {
					//Load class
					require $fileName;
				}
			}

			//Otherwise PHP error occurs
		}
	}
});

//Define error/exception not catched
set_error_handler(function(string $code, string $message, string $file, int $line, array $context = null) {
	\com\microdle\request\Request::errorHandler($code, $message, $file, $line);
});

//Callback on end process
register_shutdown_function(function() {
	//Case error (fatal error)
	$error = error_get_last();
	if($error !== null) {
		\com\microdle\request\Request::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
	}
});
?>