<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Recent
    extends Mage_Core_Model_Abstract{

    public function _construct() {
        parent::_construct();
        $this->_init('lecamg/recent');
    }

    public function loadByDomain($domain){
        $collection = $this->getResourceCollection()
            ->addFieldToFilter('domain', $domain);
        foreach($collection as $object){
            return $object;
        }
        return false;
    }

    protected function _beforeSave(){
        parent::_beforeSave();
        $notice = $this->getData('notice');
        if(is_array($notice)){
            $notice = serialize($notice);
            $this->setData('notice',$notice);
        }
        return $this;
    }

    public function getNotice(){
        $notice = $this->getData('notice');
        $notice = unserialize($notice);
        return $notice;
    }
}