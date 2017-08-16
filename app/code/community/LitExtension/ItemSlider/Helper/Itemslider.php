<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Helper_Itemslider extends Mage_Core_Helper_Abstract{

	public function getUseBreadcrumbs(){
		return Mage::getStoreConfigFlag('itemslider/itemslider/breadcrumbs');
	}
}