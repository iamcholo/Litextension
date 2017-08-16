<?php

class LitExtension_Wysiwyg_Model_Product extends Mage_Catalog_Model_Product
{
	function getDescription()
	{
		$content = $this->getData('description');
        return Mage::helper('lewysiwyg/data')->applyTemplateFilter($content);
	}

	function getShortDescription()
	{
		$content = $this->getData('short_description');
        return Mage::helper('lewysiwyg/data')->applyTemplateFilter($content);
	}	
}
