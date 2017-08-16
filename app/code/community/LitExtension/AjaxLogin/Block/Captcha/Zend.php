<?php

/**
 * @project     AjaxLogin
 * @package LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_Block_Captcha_Zend extends Mage_Captcha_Block_Captcha_Zend
{
    protected function _toHtml()
    {
        $this->getCaptchaModel()->generate();
        if (!$this->getTemplate()) {
            return '';
        }
        $html = $this->renderView();
        return $html;
    }

    public function getRefreshUrl()
    {
        return Mage::getUrl(
            Mage::app()->getStore()->isAdmin() ? 'adminhtml/refresh/refresh' : 'ajaxlogin/captcha/refresh',
            array('_secure' => Mage::app()->getStore()->isCurrentlySecure())
        );
    }
}