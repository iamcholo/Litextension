<?php
class LitExtension_Onestepcheckout_Block_Adminhtml_System_Config_Editor extends Mage_Adminhtml_Block_System_Config_Form_Field implements Varien_Data_Form_Element_Renderer_Interface{
    protected function _prepareLayout() {
        parent::_prepareLayout();
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')
                ->setCanLoadExtJs(true)
                ->setCanLoadTinyMce(true)
//                ->addItem('js','tiny_mce/tiny_mce.js')
//                ->addItem('js','mage/adminhtml/wysiwyg/tiny_mce/setup.js')
                ->addItem('js','mage/adminhtml/variables.js')
//                ->addItem('js','mage/adminhtml/wysiwyg/widget.js')
                ->addJs('mage/adminhtml/browser.js')
                ->addJs('prototype/window.js')
                ->addJs('lib/flex.js')
                ->addJs('mage/adminhtml/flexuploader.js')
                ->addJs('mage/adminhtml/browser.js')
                ->addJs('lib/flex.js')
                ->addJs('lib/FABridge.js')
                ->addItem('js_css','prototype/windows/themes/default.css')
                ->addCss('lib/prototype/windows/themes/magento.css');
            $modules = Mage::getConfig()->getNode('modules')->children();
            $modulesArray = (array)$modules;

            Mage::helper('core')->isModuleEnabled('LitExtension_Wysiwyg');
            if(isset($modulesArray['LitExtension_Wysiwyg'])) {
                if(Mage::helper('core')->isModuleEnabled('LitExtension_Wysiwyg')){
                    if(Mage::getStoreConfig('lewysiwyg/general/enable') == false ){
                        $this->getLayout()->getBlock('head')
                            ->addItem('js','tiny_mce/tiny_mce.js')
                            ->addItem('js','mage/adminhtml/wysiwyg/tiny_mce/setup.js')
                            ->addItem('js','mage/adminhtml/wysiwyg/widget.js');
                    }
                }else{
                    $this->getLayout()->getBlock('head')
                        ->addItem('js','tiny_mce/tiny_mce.js')
                        ->addItem('js','mage/adminhtml/wysiwyg/tiny_mce/setup.js')
                        ->addItem('js','mage/adminhtml/wysiwyg/widget.js');
                }

            } else { //doesn't exist
                $this->getLayout()->getBlock('head')
                    ->addItem('js','tiny_mce/tiny_mce.js')
                    ->addItem('js','mage/adminhtml/wysiwyg/tiny_mce/setup.js')
                    ->addItem('js','mage/adminhtml/wysiwyg/widget.js');
            }

        }
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element){
        $element->setWysiwyg(true);
        $element->setConfig(Mage::getSingleton('cms/wysiwyg_config')->getConfig());
        return parent::_getElementHtml($element);
    }
}