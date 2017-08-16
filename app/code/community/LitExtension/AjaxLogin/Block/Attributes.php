<?php
/**
 * @project     AjaxLogin
 * @package     LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_Block_Attributes extends Mage_Core_Block_Template{

    protected $_htmlTemplate = 'le_ajaxlogin/ajaxlogin/attributes.phtml';
    protected $_serializer = null;
    protected $_section;

    protected function _construct()
    {
        $this->_serializer = new Varien_Object();
        parent::_construct();
    }

    protected function getSectionAttribute($section){
        $this->_section = $section;
        return $this->_section;
    }

    protected function _toHtml()
    {
        $this->setTemplate($this->_htmlTemplate);
        $this->assign('section', $this->_section);

        return parent::_toHtml();
    }

}