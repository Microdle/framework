<?php 
/**
 * Inputs:
 *	- $data['tableName']
 *	- $data['primaryKeys']
 *	- $data['columnTypes']
 */

foreach($data['columnTypes'] as $fieldName => &$typeData) {
	$typeData = '\'' . $fieldName . '\' => ' . $typeData['daoType'];
}
?>
	/**
	 * Constructor.
	 * @param object $connection Database connection.
	 * @return void
	 */
	public function __construct(object $connection) {
		parent::__construct(
			$connection,
			<?php 
			echo '\'', $data['tableName'], "',\n\t\t\t[\n\t\t\t\t", implode(",\n\t\t\t\t", $data['columnTypes']), "\n\t\t\t]";
			if(!empty($data['primaryKeys'])) {
				echo ",\n\t\t\t", '[\'', implode('\',\'', $data['primaryKeys']), "']\n";
			}
			else {
				echo "\n";
			}
			?>
		);
	}