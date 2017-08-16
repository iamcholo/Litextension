<?php 

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Helper_Leimageslider extends Mage_Core_Helper_Abstract{

	public function getUseBreadcrumbs(){
		return Mage::getStoreConfigFlag('leimageslider/leimageslider/breadcrumbs');
	}
}