<?= "<?php \n"?>
namespace model\dao\<?= $data['dataSourceName']?>;

/**
 * DAO class for "<?= $data['tableName']?>" table.
 *
 * @author Microdle DAO generator by Vincent SOYSOUVANH
 * @version 0.1
 * @package model.dao.<?= $data['dataSourceName'], "\n"?>
 */
 class <?= $data['daoClass']?> extends \com\microdle\model\dao\<?= ucfirst($data['dataSourceType'])?>Dao {
<?php 
$this->_generateTemplate('constructor', $data);
$this->_generateTemplate('insert', $data);
$this->_generateTemplate('update', $data);
$this->_generateTemplate('exists', $data);
$this->_generateTemplate('get', $data);
$this->_generateTemplate('delete', $data);
$this->_generateTemplate('deleteAll', $data);
$this->_generateTemplate('getByUnique', $data);
$this->_generateTemplate('getByIndex', $data);
$this->_generateTemplate('getPage', $data);
?>

}
<?= '?>'?>