<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['indexKeys']
 *	- $data['columnTypes']
 */

if(!empty($data['indexKeys'])) {
	$fieldNames = [];
	$recordData = [];
	foreach($data['columnTypes'] as $fieldName => &$typeData) {
		$fieldNames[$fieldName] = $fieldName;
		$recordData[$fieldName] = "\n\t\t\t\t'" . $fieldName . '\' => $__' . $fieldName;
	}
	
	foreach($data['indexKeys'] as &$indexKey) {
		$methodSuffix = str_replace(' ', '', ucwords(str_replace('_', ' ', $indexKey)));
?>

	
	/**
	 * Return data by index key "<?= $indexKey?>".
	 * @param <?= self::$_parameterTypes[$data['columnTypes'][$indexKey]['bindType']]?> $name Unique key name.
	 * @return array Array if found, otherwise null.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getBy<?= $methodSuffix?>(<?= self::$_parameterTypes[$data['columnTypes'][$indexKey]['bindType']]?> $name): ?array {
		if(!($stmt = $this->_connection->prepare('SELECT <?= implode(', ', $fieldNames)?> FROM <?= $data['tableName']?> WHERE <?= $indexKey?> = ?'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $data['columnTypes'][$indexKey]['bindType']?>', $name)) {
			$this->_throwSqlException($stmt);
		}
		
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
		}
		if(!$stmt->bind_result($__<?= implode(', $__', $fieldNames)?>)) {
			$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT);
		}
		
		$results = [];
		while($stmt->fetch()) {
			$results[] = [<?= implode(',', $recordData)?>

			];
		}
		$stmt->close();
		
		return !empty($results) ? $results : null;
	}
	
	/**
	 * Determine existence by index key "<?= $indexKey?>".
	 * @param <?= self::$_parameterTypes[$data['columnTypes'][$indexKey]['bindType']]?> $name Unique key name.
	 * @return boolean.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function existsBy<?= $methodSuffix?>(<?= self::$_parameterTypes[$data['columnTypes'][$indexKey]['bindType']]?> $name): bool {
		if(!($stmt = $this->_connection->prepare('SELECT 1 FROM <?= $data['tableName']?> WHERE <?= $indexKey?> = ?'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $data['columnTypes'][$indexKey]['bindType']?>', $name)) {
			$this->_throwSqlException($stmt);
		}
		
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
		}
		if(!$stmt->bind_result($exists)) {
			$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT);
		}
		
		$stmt->fetch();
		
		$stmt->close();
		
		return $exists === 1;
	}<?php 
	}
}
?>