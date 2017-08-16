<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Mysql4_Recent
    extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('lecamg/recent', 'id');
    }
}