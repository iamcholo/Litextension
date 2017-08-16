<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Block_Adminhtml_Slide extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'adminhtml_slide';
        $this->_blockGroup = 'rotatingimageslider';
        $this->_headerText = Mage::helper('rotatingimageslider')->__('Manage Slide');
        $this->_addButtonLabel = Mage::helper('rotatingimageslider')->__('Add slide');
        parent::__construct();
    }

}