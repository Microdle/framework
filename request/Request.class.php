<?php 
namespace com\microdle\request;

//use Shmop;

/**
 * Microdle Framework - https://microdle.com/
 * Request class.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.request
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class Request {
	/**
	 * HTTP status codes. All status codes are not defined. Only the status codes used by the framework are defined.
	 * self::$httpCodes can be completed dynamically by others HTTP status codes.
	 * @var array
	 */
	static public array $httpCodes = [
		//---Informational response
		
		//---Success
		200 => 'OK',//On successful (no exception)
		
		//---Redirection
		//301 => 'Moved Permanently',
		
		//---Client errors
		401 => 'Unauthorized',//On AuthenticationException
		403 => 'Forbidden',//On ForbiddenException
		404 => 'Not Found',//On resource not found
		//405 => 'Method Not Allowed',
		//407 => 'Proxy Authentication Required',
		//408 => 'Request Timeout',
		409 => 'Conflict',//On DuplicationException
		412 => 'Precondition Failed',//On FormDataException
		
		//---Server errors
		500 => 'Internal Server Error',//On others exception: Databaseconnection, MethodNotAvailableException, SqlException...
		501 => 'Not Implemented',//On NotImplementedException
		503 => 'Service Unavailable'//On ServiceUnavailableException
		
		//---Unofficial codes
	];
	
	/**
	 * Content types. All content types are not defined. Only the content types used by the framework are defined.
	 * self::$contentTypes can be completed dynamically by others content types.
	 * @var array
	 */
	static public array $contentTypes = [
		//'css' => 'text/css',
		'csv' => 'text/csv',
		//'html' => 'text/html',
		//'jpg' => 'image/jpeg',
		//'js' => 'application/javascript',
		'json' => 'application/json',
		//'pdf' => 'application/pdf',
		//'png' => 'image/png',
		//'svg' => 'image/svg+xml',
		'text' => 'text/plain',
		'xml' => 'application/xml'
	];
	
	/**
	 * Response HTTP status code. 200 by default for successful response.
	 * @var int
	 */
	public int $httpCode = 200;

	/**
	 * Response content.
	 * @var mixed
	 */
	public $response = null;
	
	/**
	 * Response format to lower case. "json by default".
	 * @var string json|xml|csv|html|js|css|text|pdf|jpg|png...
	 */
	protected string $_format = 'json';
	
	/**
	 * Request method to lower case.
	 * @var string get|post|put|delete|options|head|trace|connect
	 */
	protected string $_method = 'get';
	
	/**
	 * Accept encoding. true if deflate is accepted, otherwise false. This information comes from browser.
	 * @var bool
	 */
	protected bool $_acceptEncoding = false;
	
	/**
	 * Request data from query string or/and form. It must be an array and is never null.
	 * @var array
	 */
	protected ?array $_parameters = null;
	
	/**
	 * BO resource path. By default root path '/' and must start and end by '/'. It is different to $_viewPath.
	 * @var string
	 * @example "/", "/folder1/", "/folder1/folder2/"...
	 */
	protected string $_boPath = '/';
	
	/**
	 * Business class name without suffix "Bo". "Index" by default.
	 * @var string
	 * @example "Index", "Client" or "Option".
	 */
	protected string $_boName = 'Index';
	
	/**
	 * BO instance.
	 * @var \com\microdle\model\bo\AbstractBo
	 */
	protected ?\com\microdle\model\bo\AbstractBo $_bo = null;
	
	/**
	 * Action name of the method to execute without suffix "Action". It is initialized in the constructor. "index" by default.
	 * @var string
	 * @example "createUser", "updateUser" or "getUser".
	 */
	protected string $_actionName = 'index';
	
	/**
	 * URI with action name (ex: "/faq/index" instead of "/faq/"). It is initialized in the constructor.
	 * @var string
	 */
	protected ?string $_uri = null;
	
	/**
	 * Form data.
	 * @var array
	 */
	protected ?array $_formData = null;
	
	/**
	 * View resource path. By default root path '/' and must start and end by '/'. It is different from $_boPath.
	 * @var string
	 * @example "/", "/folder1/", "/folder1/folder2/"...
	 */
	protected string $_viewPath = '/';
	
	/**
	 * Method result from aspect weaver methods.
	 * @var array
	 */
	protected array $_aopResults = [];
	
	/**
	 * Put resource in cache into *.html and *.html.deflate files.
	 * @param string $text Resource content to cache.
	 * @return void
	 */
	//protected function _putCache(string &$text): void {
	//	//@todo
	//}
	
	/**
	 * Return resource content from cache.
	 * @return string String (page content) if exists, otherwise null.
	 */
	//protected function _getCache(): ?string {
	//	//@todo
	//	return null;
	//}
	
	/**
	 * Catch error. By default "/var/apache2/log/error.log".
	 * @param integer $code Error Code.
	 * @param string $description Error description.
	 * @param string $file (optional) File where error occured. null by default.
	 * @param integer $line (optional) Line in file where error occured. null by default.
	 * @return void
	 */
	static public function errorHandler(string $code, string $description, string $file = null, ?int $line = null): void {
		//Log exception
		\com\microdle\library\core\LogManager::log('Error code ' . $code . ': ' . $description . ' - File: ' . $file . ' - Line: ' . $line);
		
		//This part allows to use try catch on undefined variable, division by zero, etc.
		//throw new \ErrorException($description, $code);
	}
	
	/**
	 * Convert collection of values to reference.
	 * It is a hack to pass method arguments by reference using call_user_func_array.
	 * @param array $arguments Array values to convert into reference.
	 * @return array
	 */
	static public function toReference(array &$arguments): array {
		$args = [];
		if($arguments != null) {
			foreach($arguments as &$argument) {
				$args[] = &$argument;
			}
		}
		return $args;
	}
	
	/**
	 * Initialize request. Add new values in $_ENV:
	 * 	- RESOURCE_TYPE: resource type (ex: "controller")
	 * 	- RESOURCE_SUFFIX: resource suffix (ex: "Controller")
	 * @return void
	 */
	public function __construct() {
		//Retrieve method in lower case
		$this->_method = \strtolower($_SERVER['REQUEST_METHOD']);
		
		//Case not GET and not POST
		if(!($this->_method == 'get' || $this->_method == 'post')) {
			//Set $_REQUEST
			$parameters = null;
			parse_str(file_get_contents('php://input'), $parameters);
			$_REQUEST = array_merge($_REQUEST, $parameters);
		}
		
		//Retrieve parameters
		//Set request data from query string or/and form: post method overwrite get method
		$this->_parameters = &$_REQUEST;
		$parameters = &$this->_parameters;
		
		//Determine uri
		$t = explode(',', $_SERVER['REDIRECT_URL']);
		$uri = $t[0];
		
		//Case response format is defined
		if(isset($t[1])) {
			//Set request format
			$this->_format = $t[1];
		}
		
		//Case not root
		$isRewriteRuleFound = false;
		if($uri != '/') {
			//Load BO routing: $_boRouting
			$_boRouting = null;
			$fileName = $_ENV['ROOTS']['configuration'] . '/' . $_SERVER['HTTP_HOST'] . $_ENV['FILE_EXTENSIONS']['routing'];
			if(is_file($fileName)) {
				require $fileName;
			}

			//Check in BO routing
			if($_boRouting != null) {
				//Case rewrite rule static found
				$node = &$_boRouting[$this->_method];
				if(isset($node[$_SERVER['REDIRECT_URL']])) {
					//Case page only
					$node = $node[$_SERVER['REDIRECT_URL']];
					if(!isset($node['name'])) {
						//Set new URI
						$uri = $node;
						$t = explode('/', $uri);
						$n = count($t) - 1;
					}
					
					//Case page with BO
					else {
						//Set resource data
						$this->_boName = $node['name'];
						$this->_boPath = $node['path'];
						$this->_actionName = $node['action'];
						if(isset($node['uri'])) {
							$uri = $node['uri'];
						}
						
						//Rewrite rule is found
						$isRewriteRuleFound = true;
						
					}
					
					//Retrieve GET parameters if exists
					$tmp = explode('?', $uri);
					if(isset($tmp[1])) {
						$uri = $tmp[0];
						$parms = null;
						parse_str($tmp[1], $parms);
						$parameters = array_merge($parameters, $parms);
						
					}
				}
				
				//Case rewrite rule dynamic
				else {
					//Look for the last matched node
					$t = explode('/', $uri);
					$n = count($t) - 1;
					$i = 1;
					while(isset($node[$t[$i]]) && $i <= $n) {
						$node = $node[$t[$i++]];
					}
					
					//Determne the resource with the number of parameters
					$shift = $t[$n] != '' ? 1 : 0;
					$nParms = $n - $i + $shift;
					if($nParms >= 0 && isset($node[$nParms])) {
						//Set resource data
						$node = $node[$nParms];
						$this->_boName = $node['name'];
						$this->_boPath = $node['path'];
						$this->_actionName = $node['action'];
						$this->_uri = $node['uri'];
						$uri = $node['uri'];
						
						//Define additional parameters
						$i = $n - $nParms + $shift;
						foreach($node['parameters'] as &$key) {
							$parameters[$key] = $t[$i++];
						}
						
						//Rewrite rule is foound
						$isRewriteRuleFound = true;
					}
				}
			}
			
			//Case Rewrite rule not found
			if(!$isRewriteRuleFound) {
				//Determine bo path, bo class name, action
				//Algorithm faster than preg_match + set variables

				//Case not index action, otherwise index action by default
				if($t[$n] != '') {
					$this->_actionName = $t[$n];
				}

				//Case subfolder
				if($n > 1) {
					//Build BO name class
					$this->_boName = str_replace(' ', '', ucwords(str_replace('-', ' ', $t[--$n])));

					//Determine BO path
					while(--$n) {
						$this->_boPath = '/' . $t[$n] . $this->_boPath;
					}
				}
			}
			
			//Set resource view path
			$this->_viewPath = preg_replace('/[^\/]*$/', '', $uri);
		}
		
		//Case uri not already set from routing
		if($this->_uri === null) {
			$this->_uri = $this->_actionName === 'index' && substr($uri, -1) === '/' ? $uri . 'index' : $uri;
			$this->_actionName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $this->_actionName))));
		}
		
		//Define the accept encoding: true or false
		$this->_acceptEncoding = isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false;
	}
	
	/**
	 * Return BO instance.
	 * @return object \com\microdle\model\bo\AbstractBo
	 */
	public function getBo(): object {
		return $this->_bo;
	}
	
	/**
	 * Load form data ($this->_formData), before loading BO.
	 * @return void
	 */
	public function loadFormData(): void {
		//Load $this->_formData if exists according to request type
		$fileName = $_ENV['ROOTS']['form'] . $this->_uri . ',' . $this->_method .  $_ENV['FILE_EXTENSIONS']['form'];
		if(is_file($fileName)) {
			$_formData = null;
			require $fileName;
			$this->_formData = &$_formData;
		}
	}
	
	/**
	 * Build and return form data.
	 * @param array $uris List of file names without file extension from $_ENV['ROOTS']['form'] folder (ex: ['name', '/common/civility', ...]).
	 * @return array
	 */
	public function getFormData(array $uris): array {
		//Initialize form data
		$_formData = [];
		
		//Build form data from fields
		if($uris !== null) {
			foreach($uris as &$uri) {
				require $_ENV['ROOTS']['field'] . ($uri[0] == '/' ? $uri : '/' . $uri) . $_ENV['FILE_EXTENSIONS']['field'];
			}
		}
		
		//Return form data built
		return $_formData;
	}
	
	/**
	 * Load ressources: BO, view data.
	 * @return boolean true if an aspect exists at least, otherwise false.
	 */
	protected function _loadResources(): bool {
		//Determine BO file name with complete path
		$boClass = $this->_boName . $_ENV['CLASS_SUFFIXES']['bo'];
		$bo = $this->_boPath . $boClass;
		$fileName = $_ENV['ROOTS']['bo'] . $bo . $_ENV['FILE_EXTENSIONS']['class'];
		
		//By default, data source is not used
		$existsDataSource = false;
		$existsAspect = false;
		
		//Case BO exists
		if(is_file($fileName)) {
			//Load BO class
			require $_ENV['FRAMEWORK_ROOT'] . '/model/bo/AbstractBo.class.php';
			require $fileName;
			
			//Determine the business logic class namespace
			$boClass = str_replace([$_SERVER['DOCUMENT_ROOT'] . '/application', '/'], ['', '\\'], $_ENV['ROOTS']['bo'] . $this->_boPath) . $boClass;
			
			//Instanciate the business logic class
			$bo = new $boClass($this, $this->_parameters, $this->_formData, $this->_uri);
			$this->_bo = &$bo;
			
			try {
				//Case AOP file exists
				$aopFile = $_ENV['ROOTS']['aop'] . $this->_uri . $_ENV['FILE_EXTENSIONS']['aop'];
				$_aopMethods = null;
				if(is_file($aopFile)) {
					//Load aspect weaver: $_aopMethods
					require $aopFile;
				}
				
				// Case weaver aspect exists
				if(isset($_aopMethods[$this->_method])) {
					//Case loop on AOP methods to execute
					$_aopMethods = $_aopMethods[$this->_method];
					foreach($_aopMethods as &$methodData) {
						//Execute method and retrieve result
						//If class method is not found, then the error message is logged (see /_log/)
						//$this->_aopResults[$methodData['name']] = call_user_func_array([$bo, $methodData['name']], $methodData['arguments'] != null ? self::toReference($methodData['arguments']) : []);
						
						//Case method without argument
						if($methodData['arguments'] === null) {
							$method = &$methodData['name'];
							$this->_aopResults[$methodData['name']] = $bo->$method();
						}
						
						//Case method with argument(s)
						else {
							$this->_aopResults[$methodData['name']] = \call_user_func_array([$bo, $methodData['name']], self::toReference($methodData['arguments']));
						}
						
						//Case break AOP loop
						if($bo->break) {
							break;
						}
					}
					$existsAspect = true;
				}

				//Case standard call action: execute the unique aspect
				elseif(\is_callable([$bo, $method = $this->_actionName . \ucfirst($this->_method)])) {
					$bo->$method();
					$existsAspect = true;
				}
				
				//Case action not found
				else {
					$this->httpCode = 404;
				}
				
				//Case transactions are not committed yet
				if($bo->existsDataSource()) {
					$bo->commitDataSource();
					$existsDataSource = true;
				}
			}
			
			//Case technical error
			catch(\Throwable $e) {
				//Case exists data source
				if($bo->existsDataSource()) {
					$bo->rollBackDataSource();
					$existsDataSource = true;
				}
				
				//Case business error: $errorCode = 0 comes from set_error_handler
				$errorCode = $e->getCode();
				if($errorCode > 0 && $errorCode < 500) {
					$this->httpCode = &$errorCode;
					$bo->response = $e->getMessage();
				}
				
				//Case technical error
				else {
					//Log error
					\com\microdle\library\core\LogManager::log($e->getMessage(), $e->getTrace());
					
					//Case default error
					$this->httpCode = $bo->httpCode != 200 ? $bo->httpCode : 500;
				}
			}
			finally {
				//Close data sources
				if($existsDataSource) {
					$bo->closeDataSource();
				}
			}

			//Retrieve BO reponse
			if($this->httpCode == 200) {
				$this->httpCode = &$bo->httpCode;
			}
			$this->response = &$bo->response;
		}
		
		//Case BO not found
		else {
			$this->httpCode = 404;
		}
		
		return $existsAspect ;
	}
	
	/**
	 * Execute and output response.
	 * @return void
	 */
	public function execute(): void {
		//Load form data
		$this->loadFormData();
		
		//Load and execute the business logic class
		$this->_loadResources();
		
		//Check body view
		$fileName = $_ENV['ROOTS']['view'] . '/' . $this->_format . $_ENV['FILE_EXTENSIONS']['body'];
		if(!is_file($fileName)) {
			//Set default view "/json"
			$fileName = $_ENV['ROOTS']['view'] . '/json' . $_ENV['FILE_EXTENSIONS']['body'];
		}
		
		//Display the view only if success
		if($this->httpCode == 200 || $this->httpCode == 412) {
			require $fileName;
		}
		
		//Set HTTP status code
		\header($_SERVER['SERVER_PROTOCOL'] . ' ' . $this->httpCode . (isset(self::$httpCodes[$this->httpCode]) ? self::$httpCodes[$this->httpCode] : $this->httpMessage));
		
		//Set content type
		\header('Content-Type: ' . (isset(self::$contentTypes[$this->_format]) ? self::$contentTypes[$this->_format] : self::$contentTypes['text']));
	}
	
	/**
	 * Use CURL to send synchronous request.
	 * @param string $url URL.
	 * @param array $parameters (optional) Parameters with key/value. null by default.
	 * @param string $method (optional) Request method. "GET" method by default.
	 * @param array $options (optional) CURL options. null by default.
	 * @param int $httpCode (option) HTTP status code to retrieve. 0 by default.
	 * @param int $loop Maximum of loops on expired request. 3 by default.
	 * @param int $usleep Maximum duration of sleep in micro second before each loop. 3000000 µs (3000 ms = 3 s) by default.
	 * @return string Request reponse.
	 * @throws \Exception
	 */
	static public function send(string $url, array $parameters = null, string $method = 'GET', array $options = null, int &$httpCode = 0, int $loop = 3, int $usleep = 3000000): string {
		//Build query string
		$queryString = $parameters != null ? http_build_query($parameters) : null;
		
		//Case GET method
		$isGetMethod = $method == 'GET';
		if($isGetMethod && $queryString !== null) {
			$t = explode('?', $url);
			$url = $t[0] . '?' . (isset($t[1]) ? $t[1] . '&' . $queryString : $queryString);
		}
		
		//Define default options: these options can be overwritten by $options
		$defaultOptions = [
			CURLOPT_URL => &$url,
			CURLOPT_POSTFIELDS => &$queryString,
			CURLOPT_POST => !$isGetMethod,
			CURLOPT_CUSTOMREQUEST => &$method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_FRESH_CONNECT => true,

			CURLOPT_HEADER => false,
			//CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17',

			//Fix Error 51: SSL: certificate subject name xxx does not match target host name
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,

			CURLOPT_TIMEOUT => 30
		];
		
		//Case options defined: add and/or overwrite options
		if($options !== null) {
			//Add options to default options
			foreach($options as $key => &$value) {
				$defaultOptions[$key] = &$value;
			}
		}
		
		//Initialize CURL
		$ch = curl_init();
		try {
			//Set options
			if(curl_setopt_array($ch, $defaultOptions) === false) {
				throw new \Exception(curl_error($ch));
			}
			
			do {
				//Send request and retrieve response
				$response = curl_exec($ch);
				if($response !== false) {
					break;
				}
				
				//Case no response, sleep before the next request
				if(--$loop > 0) {
					//Random pause
					usleep(mt_rand(0, $usleep));
				}
			} while($loop > 0);
			
			//Retrieve HTTP code
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			//Case error
			if($response === false) {
				throw new \Exception(curl_error($ch));
			}
		//} catch(\Exception $e) {
		//	throw $e;
		} finally {
			//End cURL
			curl_close($ch);
		}
		
		//Return request response
		return $response;
	}

	/**
	 * Send an asynchronous request.
	 * @param string $url URL.
	 * @param array $parameters Parameters. null by default.
	 * @param string $method (optional) Request method. "GET" method by default.
	 * @param array $options (optional) CURL options. null by default. Example: ['Authorization' => 'Basic ' . base64_encode($username . ':' . $password)]
	 * @param array $cookies Cookies with key/value. null by default. Example: ['PHPSESSID' => session_id(), 'path' => '/', 'id' => 3]
	 * @return bool true if sent, otherwise false.
	 */
	static public function sendAsync(string $url, array $parameters = null, string $method = 'GET', array $options = null, array $cookies = null): bool {
		try {
			//Parse URL
			$crlf = "\r\n";
			$parsedUrl = parse_url($url);
			
			//Initialize scheme and port
			if(isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'https') {
				$parsedUrl['scheme'] = 'ssl://';
				$parsedUrl['port'] = 443;
			}
			else {
				$parsedUrl['scheme'] = '';
				if(!isset($parsedUrl['port'])) {
					$parsedUrl['port'] = 80;
				}
			}
			
			//Define host
			if(!isset($parsedUrl['host'])) {
				$t = explode('/', $parsedUrl['path']);
				$parsedUrl['host'] = $t[0];

				unset($t[0]);
				$parsedUrl['path'] = '/' . implode('/', $t);
			}
			
			//Build cookies
			if($cookies != null) {
				foreach($cookies as $key => &$value) {
					$value = $key . '=' . $value;
				}
				$cookies = 'Cookie: ' . implode('; ', $cookies) . $crlf;
			}
			else {
				$cookies = '';
			}
			
			//Prepare data to send
			$query = !empty($parameters) ? http_build_query($parameters) : null;
			
			//Case GET method
			$isGetMethod = $method == 'GET';
			if($isGetMethod) {
				//Retrieve query string
				if(isset($parsedUrl['query'])) {
					$query = $query === null ? $parsedUrl['query'] : $query . '&' . $parsedUrl['query'];
				}
				
				//Build request
				$query = 'GET ' . $parsedUrl['path'] . ($query != null ? '?' . $query : '') . ' HTTP/1.1' . $crlf
					. 'Host: ' . $parsedUrl['host'] . $crlf
					. $cookies
					. 'Connection: Close' . $crlf
					. $crlf;
			}
			
			//Case other method
			else {
				//Initialize request
				$query = $method . ' ' . $parsedUrl['path'] . ' HTTP/1.1' . $crlf
					. 'Host: ' . $parsedUrl['host'] . $crlf
					. $cookies;
				
				//Case customize request
				if($options !== null) {
					foreach($options as $key => &$value) {
						$query .= $key . ': ' . $value . $crlf;
					}
				}
				
				//Case content type is not set yet
				if(!isset($options['Content-Type'])) {
					//Inform content type
					$query .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
				}
				
				//Finalize request
				$query .= 'Content-Length: ' . strlen($query) . $crlf
					. 'Connection: Close' . $crlf
					. $crlf
					. $query;
			}
			
			//Set stream context
			$streamContext = stream_context_create(
				[
					'ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false
					]
				]
			);
			
			//Open socket connection
			$errno = null;
			$errstr = null;
			if(($fp = \stream_socket_client($parsedUrl['scheme'] . $parsedUrl['host'] . ':' . $parsedUrl['port'], $errno, $errstr, ini_get('default_socket_timeout'), STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT, $streamContext)) === false) {
				throw new \Exception(__METHOD__ . ' stream_socket_client error ' . $errno . ' ' . $errstr . ': ' . $url . "\nParameters: " . \print_r($parameters, true) . "\n" . $query);
			}
			
			//Allow asynchronous
			ignore_user_abort(true);
			stream_set_blocking($fp, false);

			//Send request
			if(fwrite($fp, $query) === false) {
				throw new \Exception(__METHOD__ . ' fwrite error' . ': ' . $url . "\nParameters: ". \print_r($parameters, true) . "\n" . $query);
			}
			
			//Case not GET method
			if(!$isGetMethod) {
				//Pause 20ms to be sure the query is sent before closing
				usleep(20000);
			}
			
			//Close socket connection
			if(fclose($fp) === false) {
				throw new \Exception(__METHOD__ . ' fclose error' . ': ' . $url . "\nParameters: ". \print_r($parameters, true) . "\n" . $query);
			}
		} catch(\Exception $e) {
			//Log error but not block the process
			\com\microdle\library\core\LogManager::log($e->getMessage(), $e->getTrace());
			return false;
		}

		return true;
	}

	/**
	 * Launch asynchronous requests, then return response of each request in an array.
	 * @param array $curls Collection of curls options.
	 * @return array
	 */
	static public function sendMultiple(array &$curls): array {
		//Initialize handlers manager
		$multihandler = curl_multi_init();

		//Add handles to handles manager
		$handlers = [];
		foreach($curls as $curlKey => &$options) {
			//Set URL
			$handlers[$curlKey] = curl_init($options[CURLOPT_URL]);
			
			//Remove URL from options
			unset($options[CURLOPT_URL]);
			
			//Set options (without CURLOPT_URL option)
			foreach($options as $key => &$value) {
				curl_setopt($handlers[$curlKey], $key, $value);
			}
			
			//Set return request response by default
			if(!isset($options[CURLOPT_RETURNTRANSFER])) {
				curl_setopt($handlers[$curlKey], CURLOPT_RETURNTRANSFER, true);
			}
			
			//Add handle to handles manager
			curl_multi_add_handle($multihandler, $handlers[$curlKey]);
		}

		//Launch requests
		$pendingConnex = 0;
		do {
			curl_multi_exec($multihandler, $pendingConnex);
			usleep(10000); // 10 ms
		} while($pendingConnex > 0);

		//Retrieve requests response
		$results = [];
		foreach($handlers as $curlKey => &$handle) {
			//Retrieve response request
			$results[$curlKey] = curl_multi_getcontent($handle);

			//Close handle
			curl_multi_remove_handle($multihandler, $handle);
		}

		//Close handles manage
		curl_multi_close($multihandler);

		// Retourne les résultats des requêtes
		return $results;
	}
	
	/**
	 * Set and force URI.
	 * @param string $uri New URI.
	 * @return void
	 */
	public function setUri(string $uri): void {
		//Set URI
		$this->_uri = $uri;
		
		//Load I18N and form data before rendering
		$this->loadFormData($uri);
	}
	
	/**
	 * Return AOP method result.
	 * @param string $methodName AOP method name.
	 * @return mixed
	 */
	public function getAopResult(string $methodName): ?string {
		//Case method result exists
		if(isset($this->_aopResults[$methodName])) {
			return $this->_aopResults[$methodName];
		}
		
		//Return null by default
		return null;
	}
	
	/**
	 * Convert array to XML. SimpleXMLElement is used.
	 * @param array $array Associative array.
	 * @param string $rootElement (optional) Root element. "<root/>" by default.
	 * @param \SimpleXMLElement $xml (optional) XML object initialized with $rootElement. null by default.
	 * @return string XML
	 */
	static function arrayToXml(array &$array, string $rootElement = '<root/>', \SimpleXMLElement $xml = null): string {
		//Case XML not yet initialized
		if($xml === null) {
			$xml = new \SimpleXMLElement($rootElement);
		}
		
		//Visit all key value pair
		foreach($array as $k => &$v) {
			//Case value is an array
			if(is_array($v)) {
				//Call function for nested array
				self::arrayToXml($v, $k, $xml->addChild($k));
			}
			
			//Case simple value (not an array)
			else {
				//Add child element.
				$xml->addChild($k, $v);
			}
		}
		
		//Return XML in string format
		return $xml->asXML();
	}
	
	/**
	 * Convert array to CSV.
	 * @param array $rows Array to convert.
	 * @param string $delimiter (optional) Field delimiter. "," by default.
	 * @param string $enclosure (optional) Enclosure. '"' by default.
	 * @param string $escapeChar (optional) Escape character. "\" by default.
	 * @return string CSV
	 */
	static function arrayToCsv(array &$rows, string $delimiter = ',', string $enclosure = '"', string $escapeChar = '\\'): string {
		//Create file pointer
		$f = fopen('php://memory', 'r+');
		
		//Retrieve row title
		$rowData = $rows[array_key_first($rows)];
		
		//Case no collection, but fields
		if(!is_array($rowData)) {
			//Build collection with unique row
			$rowData = $rows;
			$rows = [$rows];
		}
		
		//Add column title
		$rowData = array_keys($rowData);
		fputcsv($f, $rowData, $delimiter, $enclosure, $escapeChar);
		
		//Add rows
		foreach($rows as &$rowData) {
			fputcsv($f, $rowData, $delimiter, $enclosure, $escapeChar);
		}
		
		//Replace file pointer at the beginning of the file
		rewind($f);
		
		//Return content
		return stream_get_contents($f);
	}
}
?>