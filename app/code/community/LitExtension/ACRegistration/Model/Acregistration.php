<?php

/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ACRegistration_Model_Acregistration extends Mage_Core_Model_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('acregistration/acregistration');
    }

    public function getAcrId($attrid){
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