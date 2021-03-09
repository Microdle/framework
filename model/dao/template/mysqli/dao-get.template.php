<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['primaryKeys']
 *	- $data['columnTypes']
 */

if(!empty($data['primaryKeys'])) {
	$whereNames = [];
	$bindTypes = '';
	$bindValues = [];
	foreach($data['primaryKeys'] as &$fieldName) {
		$whereNames[$fieldName] = $fieldName . ' = ?';
		$bindTypes .= $data['columnTypes'][$fieldName]['bindType'];
		$bindValues[$fieldName] = '$data[\'' . $fieldName . '\']';
	}
	
	$fieldNames = [];
	$fieldVariables = [];
	foreach($data['columnTypes'] as $fieldName => &$typeData) {
		$fieldNames[$fieldName] = $fieldName;
		$recordData[$fieldName] = "\n\t\t\t\t'" . $fieldName . '\' => $__' . $fieldName;
	}
}
?>

	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDao::get()
	 */
	public function get(array &$data): ?array {
<?php if(empty($data['primaryKeys'])) {?>
		throw new \com\microdle\exception\MethodNotAvailableException(__METHOD__ . ' not available.');
<?php } else {?>
		if(!($stmt = $this->_connection->prepare('SELECT <?= implode(', ', $fieldNames)?> FROM <?= $data['tableName']?> WHERE <?= implode(' AND ', $whereNames)?>'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $bindTypes?>', <?= implode(', ', $bindValues)?>)) {
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
<?php }?>
	}