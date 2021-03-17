<?php 
namespace com\microdle\model\bo;

/**
 * Microdle Framework - https://microdle.com/
 * Abstract business class using database connection.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.model.bo
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
abstract class AbstractBo {
	/**
	 * Request instance.
	 * @var \com\microdle\request\Request|null
	 */
	protected \com\microdle\request\Request $_request;
	
	/**
	 * Response HTTP status code. 200 by default for successful response.
	 * @var int
	 */
	public int $httpCode = 200;
	
	/**
	 * Response content to return.
	 * @var mixed
	 */
	public $response = null;
	
	/**
	 * Allow to break AOP loop. false by default to execute all process AOP methods.
	 * @var bool true to break AOP loop.
	 */
	public bool $break = false;
	
	/**
	 * Request parameters.
	 * @var array|null
	 */
	protected ?array $_parameters = null;
	
	/**
	 * Form data to check before process.
	 * @var array|null Associative array with key/value.
	 */
	protected ?array $_formData = null;
	
	/**
	 * View uri associated with action method. If not set, then set from $_SERVER['REDIRECT_URL'].
	 * @var string|null
	 */
	protected ?string $_viewUri = null;
	
	/**
	 * Data sources connections. Structure is:
	 * [
	 *		'<dataSourceName1>' => <dataSourceInstance1>,
	 *		'<dataSourceName2>' => <dataSourceInstance2>,
	 *		...
	 * ]
	 * @var array
	 */
	protected array $_dataSources = [];
	
	/**
	 * Form data log file.
	 * @var string|null
	 */
	protected ?string $_formDataLogFile = null;
	
	/**
	 * Initialize page request. Define resource path, controller name and action name.
	 * @param \com\microdle\request\AbstractRequest $request Resquest instance.
	 * @param array $parameters (optional) Parameters from form in associative array. null by default.
	 * @param array formData (optional) Form data. Associative array with key/value, null by default.
	 * @param string $viewUri (optional) View uri with action name (ex: "/faq/index", not "/faq/").
	 * @param array $i18n (optional) Translations in a language.
	 * @return void
	 */
	public function __construct(\com\microdle\request\Request $request, array &$parameters = null, array &$formData = null, string $viewUri = null) {
		$this->_request = &$request;
		$this->_parameters = &$parameters;
		$this->_formData = &$formData;
		$this->_viewUri = &$viewUri;
	}
	
	/**
	 * Load and create data source instance property. It is just a data source factory.
	 * @param string $className Data source name.
	 * @return com\microdle\model\ds\AbstractDs
	 */
	public function __get(string $className): ?object {
		//Case Redis
		if($className == 'redis') {
			//Connect to Redis manager
			return $this->redis = new \com\microdle\library\core\RedisManager($this);
		}
		
		//Case traditional database
		//Case data source is already instanciated
		//if(isset($this->_dataSources[$className])) {
		//	return $this->_dataSources[$className];
		//}
		
		//Case data source is not instanciated yet
		//Build the data source path
		$dataSourcePath = $_ENV['ROOTS']['dao'] . '/' . $className;
		
		//Case data source folder exists
		if(is_dir($dataSourcePath)) {
			//Load data sources configuration: $_dataSources
			$_dataSources = null;
			require $dataSourceFile = $_ENV['ROOTS']['configuration'] . '/' . $_SERVER['HTTP_HOST'] . $_ENV['FILE_EXTENSIONS']['dataSource'];

			//Check data source configuration
			if(!isset($_dataSources[$className])) {
				throw new \com\microdle\exception\DatabaseConnectionException('Data source not found in ' . $dataSourceFile . ': ' . $className);
			}
			
			//Case data source class exists in the framework
			$classFileName = ucfirst($_dataSources[$className]['type']) . $_ENV['CLASS_SUFFIXES']['ds'];
			if(is_file($fileName = $_ENV['FRAMEWORK_ROOT'] . '/model/ds/' . $classFileName . $_ENV['FILE_EXTENSIONS']['class'])) {
				//Set data source class namespace
				$dsClassName = '\\com\\microdle\\model\\ds\\' . $classFileName;
			}
			
			//Case data source class exists in the project
			elseif(is_file($fileName = $_ENV['ROOTS']['ds'] . '/' . $classFileName . $_ENV['FILE_EXTENSIONS']['class'])) {
				$dsClassName = 'com\\microdle\\model\\ds\\' . $classFileName;
			}
			
			//Case data source class does not exist
			else {
				throw new \com\microdle\exception\FileNotFoundException('Load data source impossible: ' . $_ENV['ROOTS']['ds'] . '/' . $classFileName . $_ENV['FILE_EXTENSIONS']['class']);
			}
			
			//Create data source instance: require_once because of several data sources
			require_once $fileName;
			$this->_dataSources[$className] = new $dsClassName($_dataSources[$className]);

			//Set data source key
			$this->_dataSources[$className]->key = $className;

			//Return data source instance
			return $this->_dataSources[$className];
		}
		
		//Return null by default
		return null;
	}
	
	/**
	 * Throw form data exception. All parameters are case sensitives.
	 * @param string $fieldId Field identifier.
	 * @param string $type (optional) Error type (format, required, minLength, maxLength, messages). "format" by default.
	 * @param string $messageKey (optional) Message key containing the message. "message" by default. If it is not "message", then $errorType = "messages".
	 * @param string $exceptionClassName (optional) Exception class name without namespace. "FormDataException" by default.
	 * @param string $message (optional) Additional error message for log only. null by default.
	 * @return void
	 * @throws \com\microdle\exception\FormDataException
	 */
	protected function _throwException(string $fieldId, string $type = 'format', string $messageKey = 'message', string $exceptionClassName = 'FormDataException', string $message = null): void {
		//Build exception
		$exception = '\\com\\microdle\\exception\\' . $exceptionClassName;
		$exception = new $exception(
			json_encode([
				'fieldId' => &$fieldId,
				'type' => &$type,
				'message' => isset($this->_formData[$fieldId][$type][$messageKey]) ? $this->_formData[$fieldId][$type][$messageKey] : $message,
				'label' => isset($this->_formData[$fieldId]['title']) ? $this->_formData[$fieldId]['title'] : $this->_formData[$fieldId]['label']
			])
		);
		
		//Case error: log error
		if($exceptionClassName !== 'FormDataException') {
			//Log error and notify administrator by email
			\com\microdle\library\core\LogManager::log(
				$message === null
					? $this->_formData[$fieldId][$type][$messageKey]
					: $this->_formData[$fieldId][$type][$messageKey] . ' ' . $message,
				$exception->getTrace()
			);
		}
		
		//Throw exception
		throw $exception;
	}
	
	/**
	 * Return request instance.
	 * @return \org\adventy\request\AbstractRequest
	 */
	public function getRequest(): \org\adventy\request\AbstractRequest {
		return $this->_request;
	}
	
	/**
	 * Set parameters.
	 * @param array $parameters Parameters.
	 * @return void
	 */
	public function setParameters(array $parameters): void {
		$this->_parameters = $parameters;
	}
	
	/**
	 * Return parameters.
	 * @return array
	 */
	public function getParameters(): array {
		return $this->_parameters;
	}
	
	/**
	 * Set form data.
	 * @param array $formData Form data.
	 * @return void
	 */
	public function setFormData(array $formData): void {
		$this->_formData = $formData;
	}
	
	/**s
	 * Return form data associated with the action method.
	 * @return array
	 */
	public function getFormData(): array {
		return $this->_formData;
	}
	
	/**
	 * Set the view uri of the request.
	 * @param string $viewUri View Uri if not the request uri by default.
	 * @return void
	 */
	public function setView(string $viewUri): void {
		$this->_viewUri = $viewUri;
		$this->_request->setUri($viewUri);
	}
	
	/**
	 * Return view uri associated with the action method.
	 * @return string
	 */
	public function getView(): string {
		return $this->_viewUri;
	}
	
	/**
	 * Check a date field.
	 * @param string $fieldName Date field name (ex: 'registrationDate').
	 * @param string $anteriorDateFormKey Anterior date form key of the check rules (ex: AnteriorDate). null by default.
	 * @param string $posteriorDateFormKey Posterior date form key of the check rules (ex: PosteriorDate). null by default.
	 * @return void
	 * @throws \com\microdle\exception\FormDataException
	 */
	public function checkDateField(string $fieldName, string $anteriorDateFormKey = null, string $posteriorDateFormKey = null): void {
		//Retrieve parameters
		$parameters = &$this->_parameters;
		
		//Convert date to Y-m-d format
		if(\library\core\Date::isDmy($parameters[$fieldName])) {
			$parameters[$fieldName] = \library\core\Date::toYmd($parameters[$fieldName]);
		}

		//Check date: valid date
		if(!\library\core\Date::isValid($parameters[$fieldName], 'Y-m-d')) {
			$this->_throwException($fieldName, 'format', 'message');
		}
		
		//Check date: must be at least anterior to today
		if($anteriorDateFormKey != null && $parameters[$fieldName] < date('Y-m-d')) {
			$this->_throwException($fieldName, 'messages', $anteriorDateFormKey);
		}
		
		//Check date: must be at least posterior to today
		if($posteriorDateFormKey != null && $parameters[$fieldName] > date('Y-m-d')) {
			$this->_throwException($fieldName, 'messages', $posteriorDateFormKey);
		}
	}
	
	/**
	 * Check field.
	 * @param array $data (optional) Data to check. $this->_parameters by default. Data expected:
	 *		- uri of the request to load form data
	 *		- json is the parameters to check
	 * @return void
	 */
	public function checkField(array &$data = null): void {
		//Retrieve parameters
		if($data === null) {
			$data = &$this->_parameters;
		}
		
		//Load form data
		$_formData = null;
		$this->_request->loadFormData($data['uri'], $_formData);
		
		//Case no form data
		if($_formData === null) {
			return;
		}
		
		//Convert JSON to associative array
		$data['json'] = json_decode($data['json'], true);
		if($data['json'] === null) {
			return;
		}
		
		//Build form data from $_data_formData
		$fieldData = [];
		$formData = [];
		foreach($data['json'] as $fieldName => &$fieldValue) {
			//Check field name in $_formData
			if(!isset($_formData[$fieldName])) {
				$this->_throwException($fieldName, 'messages', 'A technical error occurs.', 'KeyException', 'Field name unknown: ' . $fieldName);
			}
			
			//Add data
			$fieldData[$fieldName] = &$fieldValue;
			$formData[$fieldName] = &$_formData[$fieldName];
		}
		
		//Check parameter
		$this->checkParameters($fieldData, $formData);
	}
	
	/**
	 * Check form data.
	 * @param array $data (optional) Data to check. $this->_parameters by default.
	 * @param array $formData (optional) Form data. $this->_formData by default.
	 * @param string $redirectUrl Redirect URL on error. null by default.
	 * @return void
	 */
	public function checkParameters(array &$data = null, array &$formData = null, string $redirectUrl = null): void {
		//Form data is required
		if($formData === null) {
			$formData = &$this->_formData;
			if($formData === null) {
				return;
			}
		}
		
		//Set data by default
		if($data === null) {
			$data = &$this->_parameters;
		}
		
		try {
			//Loop on form data
			foreach($formData as $key => &$fieldData) {
				//Case title exists
				//if(isset($fieldData['title'])) {
				//	$fieldData['label'] = &$fieldData['title'];
				//}
				$label = isset($fieldData['title']) ? $fieldData['title'] : $fieldData['label'];

				//Case data exists in formData
				if(isset($data[$key])) {
					//Case array value from select, checkbox
					//Case string value from input, textarea, radio
					//$values = is_array($data[$key]) ? $data[$key] : [$data[$key]];
					if(is_array($data[$key])) {
						//Check required value
						if($fieldData['required']['value'] && empty($data[$key])) {
							//Case no default value
							if($fieldData['defaultValue'] == null) {
								throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'required', 'message' => &$fieldData['required']['message'], 'label' => &$label]));
							}

							//Set default value
							$data[$key] = is_array($fieldData['defaultValue']) ? $fieldData['defaultValue'] : [$fieldData['defaultValue']];
						}

						//Set values to check
						$values = $data[$key];
					}
					else {
						//Put value into values list to check
						$values = [&$data[$key]];
					}

					//Loop on values
					foreach($values as &$value) {
						//Clean value: string by default
						$value = trim($value);

						//Check required value
						if($fieldData['required']['value'] && $value === '') {
							//Case no default value
							if($fieldData['defaultValue'] == null) {
								throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'required', 'message' => &$fieldData['required']['message'], 'label' => &$label]));
							}

							//Set default value
							$data[$key] = $value = $fieldData['defaultValue'];
						}

						//Check length and format
						$length = \mb_strlen($value, 'UTF-8');
						if($length > 0) {
							//Check miunimum length
							if($fieldData['minLength']['value'] && $length < $fieldData['minLength']['value']) {
								throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'minLength', 'message' => &$fieldData['minLength']['message'], 'label' => &$label]));
							}

							//Check maximum length
							if($fieldData['maxLength']['value'] && $length > $fieldData['maxLength']['value']) {
								throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'maxLength', 'message' => &$fieldData['maxLength']['message'], 'label' => &$label]));
							}

							//Check format
							if($fieldData['format']['value']) {
								//Case filter with constant
								if(is_int($fieldData['format']['value'])) {
									if(!filter_var($value, $fieldData['format']['value'])) {
										throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
									}
								}

								//Case filter with string
								elseif($fieldData['format']['value'][0] !== '/') {
									//Examples: 'EMAIL' or 'FILTER_VALIDATE_EMAIL'
									$c = strtoupper($fieldData['format']['value']);
									$filter = 'FILTER_VALIDATE_' . $c;
									if(!defined($filter) || !filter_var($value, constant($filter))) {
										if(!defined($c) || !filter_var($value, constant($c))) {
											throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
										}
									}
								}

								//Case regular expression
								elseif(!preg_match($fieldData['format']['value'], $value)) {
									throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
								}
							}

							//Check select: $fieldData['values'] should not be empty
							if($fieldData['type'] === 'select' && $fieldData['values'] !== null) {
								//Case select key not found with $value string type by default
								if(!isset($fieldData['values'][$value])) {
									//Get key type: the first can be empty, so get the second
									//$keys = array_keys($fieldData['values']);
									//$type = gettype($keys[1]);
									$type = gettype(key($fieldData['values']));
									
									//String type is already checked by default
									if($type === 'string') {
										throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
									}

									//Check with key type
									$castedValue = $value;
									settype($castedValue, $type);
									if(!isset($fieldData['values'][$castedValue])) {
										throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
									}
								}
							}

							//Check number: min and max
							elseif($fieldData['type'] === 'number') {
								//Cast value to integer or decimal: integer by default
								$data[$key] = $value = !isset($fieldData['step']) || is_int($fieldData['step']) ? (integer)$value : (float)$value;

								//Case min exists
								if(isset($fieldData['min']) && $value < $fieldData['min']) {
									throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
								}

								//Case max exists
								if(isset($fieldData['max']) && $value > $fieldData['max']) {
									throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'format', 'message' => &$fieldData['format']['message'], 'label' => &$label]));
								}
							}
						}
					}
				}

				//Case data is not set but is required
				elseif($fieldData['required']['value']) {
					//Case no default value
					if($fieldData['type'] === 'checkbox' || $fieldData['defaultValue'] == null) {
						throw new \com\microdle\exception\FormDataException(json_encode(['fieldId' => &$key, 'type' => 'required', 'message' => &$fieldData['required']['message'], 'label' => &$label]));
					}

					//Set default value
					$data[$key] = $fieldData['defaultValue'];
				}
			}
		} catch(\Exception $e) {
			//Case no redirect URL, throw exception
			if($redirectUrl === null) {
				throw $e;
			}
			
			//On error, redirect URL
			$this->_request->redirect($redirectUrl);
		}
	}
	
	/**
	 * Return data sources.
	 * @return array
	 */
	public function getDataSources(): array {
		return $this->_dataSources;
	}
	
	/**
	 * Determine if a data source exists.
	 * @param string $dataSourceName (optional) Data source name. null to determine if sata sources list is empty.
	 * @return boolean
	 */
	public function existsDataSource(string $dataSourceName = null): bool {
		if($dataSourceName !== null) {
			return isset($this->_dataSources[$dataSourceName]);
		}
		
		return !empty($this->_dataSources);
	}
	
	/**
	 * Open data source, and store the connection in $this->_dataSources.
	 * @param string $dataSourceName (optional) Data source name. if not set, then open all data sources declared in *.datasource.cfg.php.
	 * @param boolean $beginTransaction (optional) Transaction status. true by default to begin transaction. 
	 * @return void
	 * @throws \com\microdle\exception\DatabaseConnectionException
	 */
	public function openDataSource(string &$dataSourceName = null, bool &$beginTransaction = true): void {
		//Case data source is already opened
		if(isset($this->_dataSources[$dataSourceName])) {
			return;
		}
		
		//Load data sources configuration: $_dataSources
		$_dataSources = null;
		require $dataSourceFile = $_ENV['ROOTS']['configuration'] . '/' . $_SERVER['HTTP_HOST'] . $_ENV['FILE_EXTENSIONS']['dataSource'];
		
		//Case only a data source
		if($dataSourceName !== null) {
			//Check data source configuration
			if(!isset($_dataSources[$dataSourceName])) {
				throw new \com\microdle\exception\DatabaseConnectionException('Data source not found in ' . $dataSourceFile . ': ' . $dataSourceName);
			}
			
			//Initialize data sources configuration
			$_dataSources = [
				$dataSourceName => $_dataSources[$dataSourceName]
			];
		}
		
		//Case all data sources
		//else {
		//}
		
		//Open all data sources defined in data sources configuration
		foreach($_dataSources as $dataSourceName => &$configurationData) {
			//Create data source instance
			$dsClassName = '\\com\\microdle\\model\\ds\\' . ucfirst($configurationData['type']) . $_ENV['CLASS_SUFFIXES']['ds'];
			$this->_dataSources[$dataSourceName] = new $dsClassName($configurationData);
			
			//Set data source key and type
			$this->_dataSources[$dataSourceName]->key = $dataSourceName;
			$this->_dataSources[$dataSourceName]->type = $configurationData['type'];
		}
		
		//Case begin transation
		if($beginTransaction) {
			$this->beginDataSourceTransaction();
		}
	}
	
	/**
	 * Close only a data source, or close all data sources.
	 * @param string $dataSourceName (optional) Data source name to close. null to close all data sources.
	 * @return void
	 */
	public function closeDataSource(string $dataSourceName = null): void {
		//Case no data source
		if(empty($this->_dataSources)) {
			return;
		}
		
		//Case only a data source
		if($dataSourceName !== null) {
			//Check data source does not exist
			if(!isset($this->_dataSources[$dataSourceName])) {
				//Ignore close
				return;
			}
			
			//Set data source to close
			$dataSources = [
				$dataSourceName => &$this->_dataSources[$dataSourceName]
			];
		}
		
		//Case all data sources
		else {
			//Set all data sources to close
			$dataSources = &$this->_dataSources;
		}
		
		//Close all data sources set
		foreach($dataSources as $name => &$dataSource) {
			//Case data source connection not closed yet
			if($dataSource !== null) {
				//Close connection
				$dataSource->closeConnection();
				unset($dataSources[$name]);
			}
		}
	}
	
	/**
	 * Commit a data source or all data sources.
	 * @param string $dataSourceName (optional) Data source name to commit. null to commit all data sources.
	 * @return void
	 */
	public function commitDataSource(string $dataSourceName = null): void {
		//Case no data source
		if(empty($this->_dataSources)) {
			return;
		}
		
		//Case only a data source
		if($dataSourceName !== null) {
			//Check data source does not exist
			if(!isset($this->_dataSources[$dataSourceName])) {
				throw new \com\microdle\exception\DatabaseException('Commit data source impossible. Data source not instanciated: ' . $dataSourceName);
			}
			
			//Set data source to commit
			$dataSources = [
				$dataSourceName => &$this->_dataSources[$dataSourceName]
			];
		}
		
		//Case all data sources
		else {
			//Set all data sources to commit
			$dataSources = &$this->_dataSources;
		}
		
		//Commit all data sources set
		foreach($dataSources as $dataSourceName => &$dataSource) {
			//Commit data source
			$dataSource->commit();
		}
	}
	
	/**
	 * Rollback data sources.
	 * @param string $dataSourceName (optional) Data source name to rollback. null to rollback all data sources.
	 * @return void
	 */
	public function rollbackDataSource(string $dataSourceName = null): void {
		//Case no data source
		if(empty($this->_dataSources)) {
			return;
		}
		
		//Case only a data source
		if($dataSourceName !== null) {
			//Check data source does not exist
			if(!isset($this->_dataSources[$dataSourceName])) {
				throw new \com\microdle\exception\DatabaseException('Rollback data source impossible. Data source not instanciated: ' . $dataSourceName);
			}
			
			//Set the data source to rollback
			$dataSources = [
				$dataSourceName => &$this->_dataSources[$dataSourceName]
			];
		}
		
		//Case all data sources
		else {
			//Set all data sources to rollback
			$dataSources = &$this->_dataSources;
		}
		
		//Rollback all data sources set
		foreach($dataSources as &$dataSource) {
			//Rollback data source
			$dataSource->rollback();
		}
	}
	
	/**
	 * Begin data source transaction.
	 * @param string $dataSourceName (optional) Data source name to begin transaction. null to begin transaction of all data sources.
	 * @return void
	 */
	public function beginDataSourceTransaction(string $dataSourceName = null): void {
		//Case begin transaction of a data source
		if($dataSourceName !== null) {
			//Check data source does not exist
			if(!isset($this->_dataSources[$dataSourceName])) {
				throw new \com\microdle\exception\DatabaseException('Begin data source transaction impossible. Data source not instanciated: ' . $dataSourceName);
			}
			
			//Set data source to set autocommit
			$dataSources = [
				$dataSourceName => &$this->_dataSources[$dataSourceName]
			];
		}
		
		//Case begin transaction of all data sources
		else {
			//Set all data sources to set autocommit
			$dataSources = &$this->_dataSources;
		}
		
		//Set all data sources autocommit
		foreach($dataSources as $dataSourceName => &$dataSource) {
			//Set data source autocommit
			$dataSource->beginTransaction();
		}
	}
	
	/**
	 * Store form data log before saving data in database.
	 * @param string $formName Form name.
	 * @param array $data Data to store.
	 * @return void
	 */
	public function saveFormDataLog(string $formName, array &$data): void {
		//Build the file name
		$fileName = $_ENV['ROOTS']['log'] . '/' . $formName . '-' . time() . '-' . uniqid();
		
		//Store form data
		\com\microdle\library\core\FileManager::setPHPVariable($fileName, str_replace(' ', '', lcfirst(ucwords(str_replace('-', ' ', $formName)))) . 'Data', $data);
		
		//Store the file name
		$this->_formDataLogFile = $fileName;
	}
	
	/**
	 * Remove stored form data log.
	 * @return void
	 */
	public function removeFormDataLog(): void {
		//Remove file
		if($this->_formDataLogFile != null && is_file($this->_formDataLogFile)) {
			unlink($this->_formDataLogFile);
		}
	}
	
	/**
	 * Remove parameters key prefix.
	 * @param array $parameters Parameters.
	 * @param string $prefix Prefix to remove.
	 * @return void
	 */
	static public function removeParametersKeyPrefix(array &$parameters, string $prefix): void {
		$newParameters = [];
		foreach($parameters as $key => &$value) {
			$newParameters[str_replace($prefix, '', $key)] = &$value;
		}
		$parameters = $newParameters;
	}
}
?>