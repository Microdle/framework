<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['uniqueKeys']
 *	- $data['columnTypes']
 */

if(!empty($data['uniqueKeys'])) {
	$fieldNames = [];
	$recordData = [];
	foreach($data['columnTypes'] as $fieldName => &$typeData) {
		$fieldNames[$fieldName] = $fieldName;
		$recordData[$fieldName] = "\n\t\t\t\t'" . $fieldName . '\' => $__' . $fieldName;
	}
	
	foreach($data['uniqueKeys'] as &$uniqueKey) {
		$methodSuffix = str_replace(' ', '', ucwords(str_replace('_', ' ', $uniqueKey)));
?>

	
	/**
	 * Return data by unique key "<?= $uniqueKey?>".
	 * @param <?= self::$_parameterTypes[$data['columnTypes'][$uniqueKey]['bindType']]?> $name Unique key name.
	 * @return array Array if found, otherwise null.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function getBy<?= $methodSuffix?>(<?= self::$_parameterTypes[$data['columnTypes'][$uniqueKey]['bindType']]?> $name): ?array {
		if(!($stmt = $this->_connection->prepare('SELECT <?= implode(', ', $fieldNames)?> FROM <?= $data['tableName']?> WHERE <?= $uniqueKey?> = ?'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $data['columnTypes'][$uniqueKey]['bindType']?>', $name)) {
			$this->_throwSqlException($stmt);
		}
		
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
		}
		if(!$stmt->bind_result($__<?= implode(', $__', $fieldNames)?>)) {
			$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT);
		}
		
		$stmt->fetch();
		
		$stmt->close();
		
		if(!empty($__<?= reset($fieldNames)?>)) {
			return [<?= implode(',', $recordData), "\n\t\t\t"?>];
		}
		
		return null;
	}
	
	/**
	 * Determine existence by unique key "<?= $uniqueKey?>".
	 * @param <?= self::$_parameterTypes[$data['columnTypes'][$uniqueKey]['bindType']]?> $name Unique key name.
	 * @return boolean.
	 * @throws \com\microdle\exception\SqlException
	 */
	public function existsBy<?= $methodSuffix?>(<?= self::$_parameterTypes[$data['columnTypes'][$uniqueKey]['bindType']]?> $name): bool {
		if(!($stmt = $this->_connection->prepare('SELECT 1 FROM <?= $data['tableName']?> WHERE <?= $uniqueKey?> = ?'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $data['columnTypes'][$uniqueKey]['bindType']?>', $name)) {
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