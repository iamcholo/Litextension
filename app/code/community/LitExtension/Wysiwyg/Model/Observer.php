<?php
class LitExtension_Wysiwyg_Model_Observer{

    public function filterCmsContent($observer){
        $form = $observer->getForm();
        $contentField = $form->getElement("content");
        $contentValue = $contentField->getValue();

        $contentValue = Mage::helper('lewysiwyg')->cleanDirectives($contentValue);

        $contentField->setValue($contentValue);
    }

    public function filterProductContent($observer){
        $form = $observer->getForm();
        $group_fields = $form->getElements();

        if(count($group_fields) < 1) {
            return;
        }

        $contentField = "";

        foreach ($group_fields[0]->getElements() as $element) {
            if($element->getId() == "description"){
                $contentField = $element;
                break;
            }
        }

        if(empty($contentField)){
            return;
        }

        $contentValue = $contentField->getValue();

        $contentValue = Mage::helper('lewysiwyg')->cleanDirectives($contentValue);
        $contentField->setValue($contentValue);
    }

}