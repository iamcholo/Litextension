<?php
/**
 * @project     ACRegistration
 * @package     LitExtension_ACRegistration
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_ACRegistration_Model_Form extends Mage_Customer_Model_Form
{
    public function getAttributesDefault()
    {
        if (is_null($this->_attributes)) {
            /* @var $collection Mage_Eav_Model_Resource_Form_Attribute_Collection */
            $collection = $this->_getFormAttributeCollection();

            $collection->setStore($this->getStore())
                ->setEntityType($this->getEntityType())
                ->addFormCodeFilter($this->getFormCode())->addFilter('is_user_defined', 0)
                ->setSortOrder();

            $this->_attributes      = array();
            $this->_userAttributes  = array();
            foreach ($collection as $attribute) {
                /* @var $attribute Mage_Eav_Model_Entity_Attribute */
                $this->_attributes[$attribute->getAttributeCode()] = $attribute;
                if ($attribute->getIsUserDefined()) {
                    $this->_userAttributes[$attribute->getAttributeCode()] = $attribute;
                } else {
                    $this->_systemAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
        return $this->_attributes;
    }
}
