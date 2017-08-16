<?php
class LitExtension_Onestepcheckout_Block_Agreements extends Mage_Core_Block_Template
{
    public function getDataTermsSystem($key){
        $data = Mage::getStoreConfig('onestepcheckout/agreements/'.$key);
        return $data;
    }
}
