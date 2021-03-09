<?php 
namespace com\microdle\library\core;

/**
 * Microdle Framework - https://microdle.com/
 * Log manager.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.library.core
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class LogManager {
		/**
	 * Information log type.
	 * @var string
	 */
	const INFORMATION_LOG = 'information';
	
	/**
	 * Warning log type.
	 * @var string
	 */
	const WARNING_LOG = 'warning';
	
	/**
	 * Error log type.
	 * @var string
	 */
	const ERROR_LOG = 'error';
	
	/**
	 * Fatal log type.
	 * @var string
	 */
	const FATAL_LOG = 'fatal';
	
	/**
	 * Return client IP (ex: 83.206.16.131).
	 * @return string
	 */
	static public function getIp(): string {
		$ip = null;
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif(isset($_SERVER['HTTP_X_REAL_IP'])) {
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		}
		elseif(isset($_SERVER['HTTP_X_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_X_CLIENT_IP'];
		}
		elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif(isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		}
		elseif(isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		}
		elseif(isset($_SERVER['HTTP_FORWARDED'])) {
			$ip = $_SERVER['HTTP_FORWARDED'];
		}
		elseif(isset($_SERVER['HTTP_VIA'])) {
			$ip = $_SERVER['HTTP_VIA'];
		}
		elseif(isset($_SERVER['HTTP_X_COMING_FROM'])) {
			$ip = $_SERVER['HTTP_X_COMING_FROM'];
		}
		elseif(isset($_SERVER['HTTP_COMING_FROM'])) {
			$ip = $_SERVER['HTTP_COMING_FROM'];
		}
		
		// Retrieve IP behind proxy, otherwise by default without proxy ($_SERVER['REMOTE_ADDR'])
		$matches = null;
		return $ip !== null ? (preg_match('/^(\d{1,3}\.){3}\d{1,3}$/', $ip, $matches) ? $matches[0] : $_SERVER['REMOTE_ADDR']) : $_SERVER['REMOTE_ADDR'];
	}
	
	/**
	 * Log message. $_logData is expected, otherwise apache error by default (see /var/log/apache2/error.log).
	 * @param string $message Text to log.
	 * @param array $traces (optional) Traces from \Exception::getTrace. null by default.
	 * @param string $type (optional) Log type: information, warning, error, fatal, etc. "error" by default. $type can be customized and can take others value than xxx_LOG constants (ex: trace, log, test, etc.).
	 * @return void
	 */
	static public function log(string $message, array $traces = null, string $type = self::ERROR_LOG): void {
		//Load log configuration: $_logData
		$_logData = null;
		$fileName = $_ENV['ROOTS']['configuration'] . '/' . $_SERVER['HTTP_HOST'] . $_ENV['FILE_EXTENSIONS']['logCfg'];
		if(\is_file($fileName)) {
			require $fileName;
		}
		
		//Case log disabled
		if($_logData === null) {
			return;
		}
		
		//Case no trace
		if($traces === null) {
			//Set default traces
			$traces = debug_backtrace();
		}
		
		//Build error message
		$errorMessage = "\n" . date('Y-m-d H:i:s') . ' ' . self::getIp() . ' ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']
			. "\nMessage: " . $message
			. "\nParameters: " . trim(\print_r($_REQUEST, true))
			. "\nHeaders: " . trim(\print_r(getallheaders(), true))
			. "\nTraces:\n" . \print_r($traces[0], true);
		if(isset($traces[1]['function']) && $traces[1]['function'] != '{closure}') {
			$errorMessage .= \print_r($traces[1], true);
		}
		
		//Case customized log exists
		$traceData = $traces[0];
		$class = $traceData['class'];
		$method = $traceData['function'];
		if(isset($_logData[$class][$method])) {
			//Loop on error files
			foreach($_logData[$class][$method] as &$errorFile) {
				//Append error in error file
				error_log($errorMessage, 3, $errorFile . $_ENV['FILE_EXTENSIONS']['log']);
			}
			
			//End log
			return;
		}
		
		//Case application log by default
		$mailLogExists = isset($_logData['application']['MAIL_ERROR_LOG']);
		$pathLogExists = isset($_logData['application']['PATH_ERROR_LOG']);
		if($mailLogExists || $pathLogExists) {
			//Case log error in file
			if($pathLogExists) {
				//Build error log file
				$errorFile = $_logData['application']['PATH_ERROR_LOG'] . $type . date('-Ymd') . $_ENV['FILE_EXTENSIONS']['log'];
				
				//Append error in application error file
				error_log($errorMessage, 3, $errorFile);
			}
			else {
				$errorFile = null;
			}
			
			//Case send notification by email only if the day log does not exist yet to avoid too much emails
			if($mailLogExists && $errorFile !== null && !\is_file($errorFile)) {
				//Send error message by email
				//$headers = 'Subject: Error ' . $_SERVER['HTTP_HOST'] . PHP_EOL
				//	. 'From: ' . $_logData['application']['MAIL_ERROR_LOG'] . PHP_EOL;
				//	//. 'MIME-Version: 1.0' . PHP_EOL
				//	//. 'Content-Type: text/html; charset=ISO-8859-1' . PHP_EOL;
				//error_log($errorMessage, 1, $_logData['application']['MAIL_ERROR_LOG'], $headers);
				error_log($errorMessage, 1, $_logData['application']['MAIL_ERROR_LOG'], $_logData['application']['MAIL_ERROR_LOG']);
			}
			
			//End log
			return;
		}
		
		//No catch exception by default: apache log used
		//throw $exception;
	}	
}
?>