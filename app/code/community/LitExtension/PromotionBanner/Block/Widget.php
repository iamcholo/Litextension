<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Block_Widget extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface {

    protected $_htmlTemplate = 'le_promotionbanner/widget.phtml';
    protected $_banner_id;

    protected function _construct() {
        parent::_construct();
    }

    protected function _beforeToHtml() {
        $this->_banner_id = $this->getData('le_pb_banner_id');
        parent::_beforeToHtml();
    }

    protected function _toHtml() {
        $this->setTemplate($this->_htmlTemplate);

        $banner = $this->_getBanner($this->_banner_id);
        $meerkat_position = $this->_getPosition($banner['position']);
        $meerkat_wrap_position = $this->_getPositionWrap($banner['position']);
        $meerkat_css = $this->_getMeerkatCSS($banner['position']);
        $meerkat_js = $this->_checkPostionJS($banner['position']);
        $meerkat_mini_arrow = $this->_getMiniMeerkatClass($banner['position']);
        $meerkat_show_arrow = $this->_getShowMeerkatClass($banner['position']);
        $meerkat_show_css = $this->_getShowMeerkatCSS($banner['position']);
        $meerket_animate_effect = $this->_getAnimateEffect($banner['position']);
        $meerkat_mini_position = $this->_getMeerkatMiniPosition($banner['position']);
        $meerkat_show_position = $this->_getMeerkatShowPosition($banner['position']);
        $meerkat_border_width = $this->_getMeerkatBorderWidth($banner['position'], $banner['border_width'], $banner['width'], $banner['height']);
        $meerkat_show_border_width = $this->_getShowMeerkatBorderWidth($banner['position'], $banner['border_width']);
        $meerkat_theme_close = $this->_getThemeClass($banner['theme']);

        $helper = Mage::helper('cms');
        $processor = $helper->getPageTemplateProcessor();
        $banner['content'] = $processor->filter($banner['content']);

        $this->assign('banner', $banner);
        $this->assign('meerkat_position', $meerkat_position);
        $this->assign('meerkat_wrap_position', $meerkat_wrap_position);
        $this->assign('meerkat_css', $meerkat_css);
        $this->assign('meerkat_js', $meerkat_js);
        $this->assign('meerkat_mini_arrow', $meerkat_mini_arrow);
        $this->assign('meerkat_show_arrow', $meerkat_show_arrow);
        $this->assign('meerkat_show_css', $meerkat_show_css);
        $this->assign('meerket_animate_effect', $meerket_animate_effect);
        $this->assign('meerkat_mini_position', $meerkat_mini_position);
        $this->assign('meerkat_show_position', $meerkat_show_position);
        $this->assign('meerkat_border_width', $meerkat_border_width);
        $this->assign('meerkat_show_border_width', $meerkat_show_border_width);
        $this->assign('meerkat_theme_close', $meerkat_theme_close);

        return parent::_toHtml();
    }

    protected function _getBanner($promotionbanner_id) {
        $collection = Mage::getModel('promotionbanner/banner')->getCollection();
        $collection->addStoreFilter(Mage :: app()->getStore());
        foreach ($collection as $data) {
            if ($data['promotionbanner_id'] == $promotionbanner_id && $data['status'] == 1 && $this->_checkTimeUse($data['start_date'], $data['end_date']) == true) {
                return $data;
            }
        }
    }

    protected function _checkTimeUse($start_date, $end_date) {
        $now = strtotime(Mage::getSingleton('core/date')->gmtDate());
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        if ($start <= $now && $now <= $end) {
            return true;
        } else {
            return false;
        }
    }

    protected function _getPosition($position) {
        $result = '';
        switch ($position) {
            case "1":
            case "2":
            case "3":
                $result = 'top';
                break;
            case "4":
                $result = 'left';
                break;
            case "5":
                $result = 'center';
                break;
            case "6":
                $result = 'right';
                break;
            case "7":
            case "8":
            case "9":
                $result = 'bottom';
                break;
            default:
                $result = 'left';
        }
        return $result;
    }

    protected function _getPositionWrap($position) {
        $result = '';
        $meerkat_position = $this->_getPosition($position);
        switch ($meerkat_position) {
            case "top":
                $result = 'width: 100% !important; top: 0 !important; left: 0 !important;';
                break;
            case "left":
                $result = 'height: 100% !important; top: 0 !important; left: 0 !important;';
                break;
            case "center":
                $result = 'width: 100% !important; top: 0 !important; left: 0 !important; ';
                break;
            case "right":
                $result = ' height: 100% !important; top: 0 !important; right: 0 !important;';
                break;
            case "bottom":
                $result = 'width: 100% !important; left: 0 !important; bottom: 0 !important;';
                break;
        }
        return $result;
    }

    protected function _getMeerkatCSS($position) {
        $result = '';
        switch ($position) {
            case "1":
                $result = ' left: 0;';
                break;
            case "2":
                $result = '';
                break;
            case "3":
                $result = 'right: 0;';
                break;
            case "4":
                $result = 'left: 0;';
                break;
            case "5":
                $result = '';
                break;
            case "6":
                $result = 'right: 0;';
                break;
            case "7":
                $result = 'left: 0; bottom: 0;';
                break;
            case "8":
                $result = 'bottom: 0;';
                break;
            case "9":
                $result = 'right: 0;bottom: 0;';
                break;
        }
        return $result;
    }

    protected function _checkPostionJS($position) {
        if ($position == '4' || $position == '6') {
            return 1;
        } elseif ($position == '5') {
            return 2;
        } elseif ($position == '2' || $position == '8') {
            return 3;
        } else {
            return 0;
        }
    }

    protected function _getShowArrow($position) {
        $result = '';
        switch ($position) {
            case "1":
            case "2":
            case "3":
                $result = 'top';
                break;
            case "4":
                $result = 'left';
                break;
            case "5":
                $result = 'top';
                break;
            case "6":
                $result = 'right';
                break;
            case "7":
            case "8":
            case "9":
                $result = 'bottom';
                break;
        }
        return $result;
    }

    protected function _getMiniArrow($position) {
        $result = '';
        switch ($position) {
            case "1":
            case "2":
            case "3":
                $result = 'bottom';
                break;
            case "4":
                $result = 'right';
                break;
            case "5":
                $result = 'bottom';
                break;
            case "6":
                $result = 'left';
                break;
            case "7":
            case "8":
            case "9":
                $result = 'top';
                break;
        }
        return $result;
    }

    protected function _getShowMeerkatCSS($position) {
        $result = '';
        switch ($position) {
            case "1":
                $result = 'position: absolute; left: 0; top:0;';
                break;
            case "2":
            case "8":
                $result = 'position: absolute;';
                break;
            case "3":
                $result = 'position: absolute; right: 0; top: 0;';
                break;
            case "7":
                $result = 'position: absolute; left: 0; bottom:0;';
                break;
            case "9":
                $result = 'position: absolute; right: 0; bottom: 0;';
                break;
            case "4":
                $result = 'position: absolute; left: 0; bottom: 0;';
                break;
            case "5":
                $result = 'position: absolute; top: 0;';
                break;
            case "6":
                $result = 'position: absolute; right: 0; bottom: 0;';
                break;
        }
        return $result;
    }

    protected function _getAnimateEffect($position) {
        $result = '';
        switch ($position) {
            case "1":
            case "2":
            case "3":
            case "5":
                $result = 'top';
                break;
            case "4":
                $result = 'left';
                break;
            case "6":
                $result = 'right';
                break;
            case "7":
            case "8":
            case "9":
                $result = 'bottom';
                break;
        }
        return $result;
    }

    protected function _getMeerkatMiniPosition($position) {
        if(!$position){return "";}
        $data = array();
        $data['1'] = "bottom: 0; right: 15px;";
        $data['2'] = "bottom: 0; right: 15px;";
        $data['3'] = "bottom: 0; right: 15px;";
        $data['4'] = "bottom: 0; right: 0;";
        $data['5'] = "bottom: 0; right: 0;";
        $data['6'] = "top: 0; left: 0;";
        $data['7'] = "top: 0; left: 15px;";
        $data['8'] = "top: 0; left: 15px;";
        $data['9'] = "top: 0; left: 15px;";

        return $data[$position];
    }

    protected function _getMeerkatShowPosition($position) {
        if(!$position){return "";}
        $data = array();
        $data['1'] = 'right: 15px;';
        $data['2'] = 'right: 15px;';
        $data['3'] = 'right: 15px;';
        $data['4'] = 'bottom: 0;';
        $data['5'] = 'right: 0;';
        $data['6'] = 'top:0 ;';
        $data['7'] = 'left: 15px;';
        $data['8'] = 'left: 15px;';
        $data['9'] = 'left: 15px;';
        return $data[$position];
    }

    protected function _getThemeClass($theme){
        $result = 'le-meerkat-theme-'.$this->_convertThemeOption($theme);
        return $result;
    }

    protected function _convertThemeOption($theme){
        if(!$theme){return "";}
        $data = array();
        $data['1'] = 'icon';
        $data['2'] = 'text';
        return $data[$theme];
    }

    protected function _getMiniStyle() {
        return 'transparent';
    }

    protected function _getMiniMeerkatClass($position) {
        $result = $this->_getMiniArrow($position) . " " . $this->_getMiniStyle();
        return $result;
    }

    protected function _getShowMeerkatClass($position) {
        $result = $this->_getShowArrow($position) . " " . $this->_getMiniStyle();
        return $result;
    }

    protected function _getMeerkatBorderWidth($position, $border_width, $width, $height) {
        if(!$position){return "";}
        $data = array();
        if ($width !== '100%' && $height !== '100%') {
            $data[1] = '0px ' . $border_width . 'px ' . $border_width . 'px 0px';
            $data[2] = '0px ' . $border_width . 'px ' . $border_width . 'px';
            $data[3] = '0px 0px ' . $border_width . 'px ' . $border_width . 'px';
            $data[4] = $border_width . 'px ' . $border_width . 'px ' . $border_width . 'px 0px';
            $data[5] = $border_width . 'px';
            $data[6] = $border_width . 'px 0px ' . $border_width . 'px ' . $border_width . 'px';
            $data[7] = $border_width . 'px ' . $border_width . 'px 0px 0px';
            $data[8] = $border_width . 'px ' . $border_width . 'px 0px ' . $border_width . 'px';
            $data[9] = $border_width . 'px 0px 0px ' . $border_width . 'px';
        } elseif ($width === '100%' && $height !== '100%') {
            $data[1] = '0px 0px ' . $border_width . 'px 0px';
            $data[2] = '0px 0px ' . $border_width . 'px 0px';
            $data[3] = '0px 0px ' . $border_width . 'px 0px';
            $data[4] = $border_width . 'px 0px ';
            $data[5] = $border_width . 'px 0px';
            $data[6] = $border_width . 'px 0px';
            $data[7] = $border_width . 'px 0px 0px';
            $data[8] = $border_width . 'px 0px 0px';
            $data[9] = $border_width . 'px 0px 0px';
        } elseif ($width !== '100%' && $height === '100%') {
            $data[1] = '0px ' . $border_width . 'px 0px 0px';
            $data[2] = '0px ' . $border_width . 'px';
            $data[3] = '0px 0px 0px ' . $border_width . 'px';
            $data[4] = '0px ' . $border_width . 'px 0px 0px';
            $data[5] = '0px ' . $border_width . 'px';
            $data[6] = '0px 0px 0px ' . $border_width . 'px';
            $data[7] = '0px ' . $border_width . 'px 0px 0px';
            $data[8] = '0px ' . $border_width . 'px';
            $data[9] = '0px 0px 0px ' . $border_width . 'px';
        } else {
            $data[1] = $data[2] = $data[3] = $data[4] = $data[5] = $data[6] = $data[7] = $data[8] = $data[9] = '0px';
        }
        return $data[$position];
    }

    protected function _getShowMeerkatBorderWidth($position, $border_width) {
        if(!$position){return "";}
        $data = array();
        $data[1] = '0px ' . $border_width . 'px ' . $border_width . 'px';
        $data[2] = '0px ' . $border_width . 'px ' . $border_width . 'px';
        $data[3] = '0px ' . $border_width . 'px ' . $border_width . 'px';
        $data[4] = $border_width . 'px ' . $border_width . 'px ' . $border_width . 'px 0px';
        $data[5] = '0px ' . $border_width . 'px ' . $border_width . 'px';
        $data[6] = $border_width . 'px 0px ' . $border_width . 'px ' . $border_width . 'px';
        $data[7] = $border_width . 'px ' . $border_width . 'px 0px';
        $data[8] = $border_width . 'px ' . $border_width . 'px 0px';
        $data[9] = $border_width . 'px ' . $border_width . 'px 0px';
        return $data[$position];
    }

}
