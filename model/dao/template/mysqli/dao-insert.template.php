<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['primaryKeys']
 *	- $data['columnTypes']
 */

$fieldNames = [];
$bindTypes = '';
$bindValues = [];
foreach($data['columnTypes'] as $fieldName => &$typeData) {
	$fieldNames[$fieldName] = $fieldName;
	$bindTypes .= $typeData['bindType'];
	$bindValues[$fieldName] = '$data[\'' . $fieldName . '\']';
}

$primaryKey = reset($data['primaryKeys']);
?>

	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDao::insert()
	 */
	public function insert(array &$data): void {
<?php if(count($data['primaryKeys']) === 1) {?>
		//Create identifier
		if(empty($data['<?= $primaryKey?>'])) {
			$data['<?= $primaryKey?>'] = $this->getNewId();
		}
		
<?php }?>
<?php if(isset($fieldNames['updated'])) {?>
		//Define updated date
		if(!isset($data['updated'])) {
			$data['updated'] = date('Y-m-d H:i:s');
		}
		
<?php }?>
<?php if(isset($fieldNames['created'])) {?>
		//Define created date
		if(!isset($data['created'])) {
<?php if(isset($fieldNames['updated'])) {?>
			$data['created'] = &$data['updated'];
<?php } else {?>
			$data['created'] = date('Y-m-d H:i:s');
<?php }?>
		}
		
<?php }?>
		if(!($stmt = $this->_connection->prepare('INSERT INTO <?= $data['tableName']?>(<?= implode(', ', $fieldNames)?>) VALUES(<?= implode(', ', array_fill(0, count($fieldNames), '?'))?>)'))) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
		if(!$stmt->bind_param('<?= $bindTypes?>', <?= implode(', ', $bindValues)?>)) {
			$this->_throwSqlException($stmt);
		}
		
		if(!$stmt->execute()) {
			$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
		}
		
		$stmt->close();
	}