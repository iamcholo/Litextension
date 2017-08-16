<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_ItemSlider_Model_Status extends Varien_Object {
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    public function addEnabledFilterToCollection($collection) {
        $collection->addEnableFilter(array('in' => $this->getEnabledStatusIds()));
        return $this;
    }

    public function addCatFilterToCollection($collection, $cat) {
        $collection->addCatFilter($cat);
        return $this;
    }

    public function getEnabledStatusIds() {
        return array(self::STATUS_ENABLED);
    }

    public function getDisabledStatusIds() {
        return array(self::STATUS_DISABLED);
    }

    static public function getOptionArray() {
        return array(
            self::STATUS_ENABLED => Mage::helper('itemslider')->__('Enabled'),
            self::STATUS_DISABLED => Mage::helper('itemslider')->__('Disabled'),
        );
    }

}
