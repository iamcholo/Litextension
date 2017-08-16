<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxCart_Block_Ajaxcart extends Mage_Core_Block_Template {

    public function getLEAJCConfig() {
        $data = Mage::getStoreConfig('leajct/general');
        return $data;
    }

    protected function getClassTheme(){
        $class = 'le_ajaxcart_theme_'.Mage::getStoreConfig('leajct/general/theme');
        return $class;
    }

}

