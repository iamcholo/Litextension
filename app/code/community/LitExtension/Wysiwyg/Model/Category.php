<?php

class LitExtension_Wysiwyg_Model_Category extends Mage_Catalog_Model_Category
{
	function getDescription()
	{
		$content = $this->getData('description');
        return Mage::helper('lewysiwyg')->applyTemplateFilter($content);
	}
}
