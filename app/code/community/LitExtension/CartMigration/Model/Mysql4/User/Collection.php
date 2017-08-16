<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Mysql4_User_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract{

    public function _construct() {
        parent::_construct();
        $this->_init('lecamg/user');
    }
}