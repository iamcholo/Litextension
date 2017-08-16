<?php
/**
 * @project     AjaxLogin
 * @package LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */


class LitExtension_AjaxLogin_Block_Captcha extends Mage_Captcha_Block_Captcha
{

    protected function _toHtml()
    {
        $blockPath = 'ajaxlogin/captcha_zend';
        $block = $this->getLayout()->createBlock($blockPath);
        $block->setData($this->getData());
        return $block->toHtml();
    }
}