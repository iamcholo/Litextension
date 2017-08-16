<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Group extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'adminhtml_group';
        $this->_blockGroup = 'leimageslider';
        $this->_headerText = Mage::helper('leimageslider')->__('Manage Group');
        $this->_addButtonLabel = Mage::helper('leimageslider')->__('Add Group');
        parent::__construct();
    }

}