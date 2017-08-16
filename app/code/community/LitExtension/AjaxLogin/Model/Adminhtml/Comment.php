<?php

class LitExtension_AjaxLogin_Model_Adminhtml_Comment{
    public function getCommentText(){
        return '<a href="'.Mage::helper("adminhtml")->getUrl("adminhtml/checkout_agreement/index/").'" target="_blank" title="Manage Terms and Conditions">Manage Terms and Conditions</a>';
    }
}