<?php

class LitExtension_Wysiwyg_Model_Agreement extends Mage_Checkout_Model_Agreement
{
    /**
     * Add parsing filter to agreement box content.
     *
     * @return string
     */
    public function getContent()
    {
        $content = $this->getData('content');
        return Mage::helper('lewysiwyg')->applyTemplateFilter($content);
    }

    /**
     * Add parsing filter to agreement checkbox text.
     *
     * @return string
     */
    public function getCheckboxText()
    {
        $content = $this->getData('checkbox_text');
        return Mage::helper('lewysiwyg')->applyTemplateFilter($content);
    }
}
