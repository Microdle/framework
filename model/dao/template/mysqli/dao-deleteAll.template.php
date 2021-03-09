<?php 
/**
 * Inputs:
 *	- $data['tableName']
 */
?>

	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDao::deleteAll()
	 */
	public function deleteAll(): void {
		if(!$this->_connection->query('DELETE FROM <?= $data['tableName']?>')) {
			throw new \com\microdle\exception\SqlException($this->_connection->error);
		}
	}