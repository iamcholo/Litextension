<?php
/**
 * @project     AjaxLogin
 * @package     LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_Model_Attributes extends Mage_Core_Model_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('ajaxlogin/attributes');
    }

    public function getAjaxloginId($attrid){
        if ($attrid) {
            $this->load($attrid,'attribute_id');
        }
        $id = $this->getSection();
        return $id;
    }

    public function getShowOnCustomerGrid($attrid){
        if ($attrid) {
            $this->load($attrid,'attribute_id');
        }
        $status = $this->getShow_on_customer_grid();
        return $status;
    }

}