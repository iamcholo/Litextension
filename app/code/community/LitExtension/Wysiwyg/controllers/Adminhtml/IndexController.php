<?php
class LitExtension_Wysiwyg_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction(){
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('Mage_Adminhtml_Block_Template', 'ok_man', array('template' => 'test.phtml')));
        $this->renderLayout();
    }
}