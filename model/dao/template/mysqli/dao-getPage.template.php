<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['primaryKeys']
 *	- $data['uniqueKeys']
 *	- $data['indexKeys']
 *	- $data['otherKeys']
 *	- $data['columnTypes']
 */

$fieldNames = [];
foreach($data['columnTypes'] as $fieldName => &$typeData) {
	$fieldNames[$fieldName] = $fieldName;
}
?>

	
	/**
	 * (non-PHPdoc)
	 * @see AbstractDao::getPage()
	 */
	public function getPage(array $filters = null, int $page = 1, int $quantity = 10, bool $count = true, string $orderClause = null): array {
<?php if(empty($data['primaryKeys']) && empty($data['uniqueKeys']) && empty($data['indexKeys'])) {?>
		throw new \com\microdle\exception\MethodNotAvailableException(__METHOD__);
<?php 
	}
	
	else {
		$filters = array_merge($data['primaryKeys'], $data['uniqueKeys'], $data['indexKeys'], $data['otherKeys']);
		$distinctClause = !empty($data['primaryKeys'])
			? $data['primaryKeys']
			: (!empty($data['uniqueKeys']) ? $data['uniqueKeys'] : $fieldNames);
		$distinctClause = implode(', ', $distinctClause);
?>
		//Count number of records
		$from = '<?= $data['tableName']?>';
		$where = '1';
		$referenceValues = [0 => ''];
		
		//Case filters exist
		if(!empty($filters)) {
			//Build SQL with operators
			if(is_array(reset($filters))) {
				$filters = $this->getFiltersClause($filters);
				$where = $filters['where'];
				$referenceValues = &$filters['referenceValues'];
			}
			
			//Build SQL by default on index columns
			else {
				//Build SQL
<?php 
foreach($filters as &$key) {
?>
				if(isset($filters['<?= $key?>'])) {
					$where .= ' AND <?= $key?> = ?';
					$referenceValues[0] .= '<?= $data['columnTypes'][$key]['bindType']?>';
					$referenceValues[] = &$filters['<?= $key?>'];
				}

<?php 
}

if(isset($data['columnTypes']['created'])) {
?>
				if(isset($filters['createdStart']) || isset($filters['createdEnd'])) {
					if(isset($filters['createdStart']) && isset($filters['createdEnd'])) {
						$where .= ' AND created BETWEEN ? AND ?';
						$referenceValues[0] .= 'ss';
						$referenceValues[] = &$filters['createdStart'];
						$referenceValues[] = &$filters['createdEnd'];
					}
					elseif(isset($filters['createdStart'])) {
						$where .= ' AND created >= ?';
						$referenceValues[0] .= 's';
						$referenceValues[] = &$filters['createdStart'];
					}
					else {
						$where .= ' AND created <= ?';
						$referenceValues[0] .= 's';
						$referenceValues[] = &$filters['createdEnd'];
					}
				}

<?php 
}

if(isset($data['columnTypes']['updated'])) {
?>
				if(isset($filters['updatedStart']) || isset($filters['updatedEnd'])) {
					if(isset($filters['updatedStart']) && isset($filters['updatedEnd'])) {
						$where .= ' AND updated BETWEEN ? AND ?';
						$referenceValues[0] .= 'ss';
						$referenceValues[] = &$filters['updatedStart'];
						$referenceValues[] = &$filters['updatedEnd'];
					}
					elseif(isset($filters['updatedStart'])) {
						$where .= ' AND updated >= ?';
						$referenceValues[0] .= 's';
						$referenceValues[] = &$filters['updatedStart'];
					}
					else {
						$where .= ' AND updated <= ?';
						$referenceValues[0] .= 's';
						$referenceValues[] = &$filters['updatedEnd'];
					}
				}
			
<?php 
}
?>
			}
		}
		
		if($count) {
			if($referenceValues[0] !== '') {
				if(!($stmt = $this->_connection->prepare('SELECT COUNT(DISTINCT <?= $distinctClause?>) FROM ' . $from . ' WHERE ' . $where))) {
					throw new \com\microdle\exception\SqlException($this->_connection->error);
				}
				if(call_user_func_array([$stmt, 'bind_param'], $referenceValues)===false) {
					$this->_throwSqlException($stmt);
				}
				if(!$stmt->execute()) {
					$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
				}
				if(!$stmt->bind_result($n)) {
					$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT);
				}
				$stmt->fetch();
				$stmt->close();
				$n = (integer)$n;
			}
			else {
				if(!($result = $this->_connection->query('SELECT COUNT(DISTINCT <?= $distinctClause?>) FROM <?= $data['tableName']?>'))) {
					throw new \com\microdle\exception\SqlException($this->_connection->error);
				}
				$n = $result->fetch_row();
				$result->close();
				$n = (integer)$n[0];
			}
		}
		else $n = null;
		
		//Retrieve data
		$results = [];
		if($n || !$count) {
			$index = ($page-1) * $quantity;
			if(!empty($orderClause)) {
				$orderClause = ' ORDER BY ' . $orderClause;
			}
			if(!($stmt = $this->_connection->prepare('SELECT DISTINCT <?= implode(', ', $fieldNames)?> FROM ' . $from . ' WHERE ' . $where . $orderClause . ' LIMIT ?, ?'))) {
				throw new \com\microdle\exception\SqlException($this->_connection->error);
			}
			$referenceValues[0] .= 'ii';
			$referenceValues[] = &$index;
			$referenceValues[] = &$quantity;
			if(call_user_func_array([$stmt, 'bind_param'], $referenceValues)===false) {
				$this->_throwSqlException($stmt);
			}
			if(!$stmt->execute()) {
				$this->_throwSqlException($stmt, self::EXECUTE_STATEMENT);
			}
			if(!$stmt->bind_result($__<?= implode(', $__', $fieldNames)?>)) {
				$this->_throwSqlException($stmt, self::BIND_RESULT_STATEMENT);
			}
			while($stmt->fetch()) {
				$results[] = [<?php 
	foreach($fieldNames as $key => &$value) {
		$value = "\n\t\t\t\t\t'" . $key . '\' => $__' . $key;
	}
	echo implode(',', $fieldNames);
?>

				];
			}
			$stmt->close();
		}
		
		return $count ? ['results' => $results, 'count' => $n] : $results;
<?php }?>
	}