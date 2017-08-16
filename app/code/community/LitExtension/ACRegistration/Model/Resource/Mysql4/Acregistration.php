<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ACRegistration_Model_Resource_Mysql4_Acregistration extends Mage_Core_Model_Mysql4_Abstract
{

    public function _construct()
    {
        $this->_init('acregistration/acregistration', 'acr_id');
    }

    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        return parent::_afterLoad($object);
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        return parent::_getLoadSelect($field, $value, $object);
    }

    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        return parent::_afterSave($object);
    }
}