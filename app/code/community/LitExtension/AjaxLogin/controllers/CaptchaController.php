<?php
/**
 * @project     AjaxLogin
 * @package LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_CaptchaController extends Mage_Core_Controller_Front_Action
{


    public function refreshAction()
    {
        $formId = $this->getRequest()->getPost('formId', false);
        if ($formId) {
            $captchaModel = Mage::helper('captcha')->getCaptcha($formId);
            $this->getLayout()->createBlock('ajaxlogin/captcha_zend')->setFormId($formId)->setIsAjax(true)->toHtml();
            $this->getResponse()->setBody(json_encode(array('imgSrc' => $captchaModel->getImgSrc())));
        }
        $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
    }

}