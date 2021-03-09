<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['primaryKeys']
 *	- $data['columnTypes']
 */

$fieldNames = array_keys($data['columnTypes']);
?>

	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDao::getAll()
	 */
	public function getAll(array $fieldNames = null, int $page = 1, int $quantity = 30, string $orderClause = null): array {
		$page = $page > 0 ? $page - 1 : 0;
		if($quantity < 1) {
			$quantity = 30;
		}
		
		$fieldNames = !$fieldNames&&count($fieldNames) ? implode(',', $fieldNames) : '*';
		$sql = 'SELECT <?= implode(', ', $fieldNames)?> FROM <?= $data['tableName']?> WHERE 1 LIMIT ' . ($page * $quantity) . ', ' . $quantity;
		if(!empty($orderClause)) {
			$sql .= ' ORDER BY ' . $orderClause;
		}
		
		if(!($result = $this->_connection->query($sql))) {
			throw new SqlException($this->_connection->error);
		}
		
		$data = [];
		while($row = $result->fetch_assoc()) {
			$data[] = row;
		}
		
		$result->close();
		
		return $data;
	}