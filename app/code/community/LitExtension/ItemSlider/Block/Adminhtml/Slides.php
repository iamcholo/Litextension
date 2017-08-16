    <?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Slides extends Mage_Adminhtml_Block_Widget_Grid_Container{

    public function __construct(){
        $this->_controller 		= 'adminhtml_slides';
        $this->_blockGroup 		= 'itemslider';
        $this->_headerText 		= Mage::helper('itemslider')->__('Manage Sliders');
        $this->_addButtonLabel 	= Mage::helper('itemslider')->__('Add Slider');
        parent::__construct();
    }
}