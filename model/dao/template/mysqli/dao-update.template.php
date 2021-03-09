<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['primaryKeys']
 *	- $data['columnTypes']
 */

if(!empty($data['primaryKeys'])) {
	$keyTypes = [];
	foreach($data['primaryKeys'] as &$key) {
		$keyTypes[$key] = $data['columnTypes'][$key];
		unset($data['columnTypes'][$key]);
	}
	
	$fieldNames = [];
	$bindTypes = '';
	$bindValues = [];
	foreach($data['columnTypes'] as $fieldName => &$typeData) {
		if($fieldName != 'created') {
			$fieldNames[$fieldName] = $fieldName . ' = ?';
			$bindTypes .= $typeData['bindType'];
			$bindValues[$fieldName] = '$data[\'' . $fieldName . '\']';
		}
	}
	
	$whereNames = [];
	foreach($keyTypes as $fieldName => &$typeData) {
		$whereNames[$fieldName] = $fieldName . ' = ?';
		$bindTypes .= $typeData['bindType'];
		$bindValues[$fieldName] = '$data[\'' . $fieldName . '\']';
	}
}
?>

	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDao::update()
	 */
	public function update(array &$data): void {
<?php if(empty($data['primaryKeys'])) {?>
		throw new \com\microdle\exception\MethodNotAvailableException(__METHOD__ . ' not available.');
<?php } else {?>
<?php if(isset($fieldNames['updated'])) {?>
		//Define updated date
		if(!isset($data['updated'])) {
			$data['updated'] = date('Y-m-d H:i:s');
		}
		
<?php }?>
		if(!($stmt = $this->_connection->prepare('UPDATE <?= $data['tableName']?> SET <?= implode(', ', $fieldNames)?> WHERE <?= implode(' AND ', $whereNames)?>'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $bindTypes?>', <?= implode(', ', $bindValues)?>)) {
			$this->_throwSqlException($stmt);
		}
		
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
		}
		
		$stmt->close();
<?php }?>
	}