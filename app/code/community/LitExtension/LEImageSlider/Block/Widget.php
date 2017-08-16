<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Widget extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface {

    protected $_htmlTemplate = 'le_leimageslider/widget.phtml';
    protected $_serializer = null;
    protected $_group_id;

    protected function _construct() {
        $this->_serializer = new Varien_Object();
        parent::_construct();
    }

    protected function _beforeToHtml() {
        $this->_group_id = $this->getData('le_group_id');
        parent::_beforeToHtml();
    }

    protected function _toHtml() {
        $this->setTemplate($this->_htmlTemplate);

        $data = $this->getListSlide();
        $group_config = $this->_getGroupLe();

        $this->assign('data', $data);
        $this->assign('group_config', $group_config);
        return parent::_toHtml();
    }

    public function getListSlide() {
        $collection = Mage::getModel('leimageslider/slide')->getCollection();
        $collection->addFieldToFilter('group_id', array('eq' => $this->_group_id));
        $group_config = $this->_getGroupLe();
        $width = $group_config['width'];
        $height = $group_config['height'];
        $group_status = $group_config['status'];
        $data = "";
        foreach ($collection as $image) {
            if ($image['status'] == 1 && $image['image'] != "" && $group_status == 1) {
                if ($image['link'] != "") {
                    $data .= '<a href="' . $image['link'].'"';
                    if($image['target'] == 1){
                        $data .= ' target="_blank"';
                    }
                    $data .= '>';
                }
                $image_src = Mage::getBaseUrl('media') . "leimageslider/image" . $image['image'];
                $data .= '<img src="' . $image_src . '" data-thumb="' . $image_src . '"  alt=""  title="' . $image['content'] . '" />';
                if ($image['link'] != "") {
                    $data .= '</a>';
                }
            }
        }
        return $data;
    }

    public function _getGroupLe() {
        $collection = Mage::getModel('leimageslider/group')->getCollection();
        $collection->addFieldToFilter('leimageslider_group_id', array('eq' => $this->_group_id));
        $group_le = $collection->getFirstItem();
        return $group_le;
    }

}