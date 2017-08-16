<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Block_Widget extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface {

    protected $_htmlTemplate = 'le_rotatingimageslider/widget.phtml';
    protected $_serializer = null;
    protected $_group_id;

    protected function _construct() {
        $this->_serializer = new Varien_Object();
        parent::_construct();
    }

    protected function _beforeToHtml() {
        parent::_beforeToHtml();
    }

    protected function _toHtml() {
        $this->setTemplate($this->_htmlTemplate);

        $data = $this->getRMData();
        $ul_tag = $this->getUlTag();
        $label = $this->getLabel();
        $ris_config = $this->getRISConfig();
        $image_width = $this->getImageWidth();
        $image_height = $this->getImageHeight();

        $this->assign('data', $data);
        $this->assign('ul_tag', $ul_tag);
        $this->assign('label', $label);
        $this->assign('ris_config', $ris_config);
        $this->assign('image_width', $image_width);
        $this->assign('image_height', $image_height);
        return parent::_toHtml();
    }

    protected function _getDivPart($group_id) {

        $collection = Mage::getModel('rotatingimageslider/slide')->getCollection()->addStoreFilter(Mage :: app()->getStore());
        $collection->addFieldToFilter('group_id', array('eq' => $group_id));
        $data = '<div id="rm_container_' . $group_id . '">';
        foreach ($collection as $item) {
            $link = "";
            if ($item['link'] != "") {
                $link .= 'onclick="window.location=' . "'" . $item['link'] . "'" . '" style="cursor:pointer;"';
            }
            if ($item['status'] == 1 && $item['image'] != "") {
                $image_src = Mage::getBaseUrl('media') . "rotatingimageslider/image" . $item['image'];
                $data .='<img src="' . $image_src . '" ' . $link . '/>';
            }
        }
        $data .= '</div>';
        return $data;
    }

    public function getRMData() {
        $data = '<div style="display:none;">';
        for ($i = 1; $i <= 4; $i++) {
            $data .= $this->_getDivPart($i);
        }
        $data .= '</div>';
        return $data;
    }

    public function getLiTag($group_id) {
        switch ($group_id) {
            case "1":
                $data_rotation = "-15";
                break;
            case "2":
                $data_rotation = "-5";
                break;
            case "3":
                $data_rotation = "5";
                break;
            case "4":
                $data_rotation = "15";
                break;
            default:
                $data_rotation = "-5";
        }
        $collection = Mage::getModel('rotatingimageslider/slide')->getCollection()->addStoreFilter(Mage :: app()->getStore());
        $collection->addFieldToFilter('group_id', array('eq' => $group_id));
        $image = $collection->getFirstItem();
        $image_src = Mage::getBaseUrl('media') . "rotatingimageslider/image" . $image['image'];
        $data_image = 'rm_container_' . $group_id;
        $data = '<li data-images="' . $data_image . '" data-rotation="' . $data_rotation . '">';
        $link = "";
        if ($image['link'] != "") {
            $link .= 'onclick="window.location=' . "'" . $image['link'] . "'" . '" style="cursor:pointer;"';
        }
        if ($image['image'] != "") {
            $data .= '<img src="' . $image_src . '" '.$link.'/>';
        }
        $data .= '</li>';
        return $data;
    }

    public function getUlTag() {
        $data = '<ul>';
        for ($i = 1; $i <= 4; $i++) {
            $data .= $this->getLiTag($i);
        }
        $data .= '</ul>';
        return $data;
    }

    public function getRISConfig() {
        $data = Mage::getStoreConfig('rotatingimageslider/rotatingimageslider');
        return $data;
    }

    public function getImageWidth() {
        $ris_config = $this->getRISConfig();
        $wrap_width = $ris_config['width'];
        $image_width = $wrap_width * 310 / 1160;
        return $image_width;
    }

    public function getImageHeight() {
        $ris_config = $this->getRISConfig();
        $wrap_height = $ris_config['height'];
        $image_height = $wrap_height * 465 / 530;
        return $image_height;
    }

    public function getLabel() {
        $ris_config = $this->getRISConfig();
        return $ris_config['label'];
    }

}