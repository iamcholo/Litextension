<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Block_Adminhtml_Banner extends Mage_Adminhtml_Block_Widget_Grid_Container {

    public function __construct() {
        $this->_controller = 'adminhtml_banner';
        $this->_blockGroup = 'promotionbanner';
        $this->_headerText = Mage::helper('promotionbanner')->__('Manage Banner');
        $this->_addButtonLabel = Mage::helper('promotionbanner')->__('Add Banner');
        parent::__construct();
    }

}