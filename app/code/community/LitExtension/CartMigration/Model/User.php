<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_User
    extends Mage_Core_Model_Abstract{

    public function _construct() {
        parent::_construct();
        $this->_init('lecamg/user');
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

    public function loadByUserId($user_id){
        $collection = $this->getResourceCollection()
                ->addFieldToFilter('user_id', $user_id);
        foreach($collection as $object){
                return $object;
        }
        return false;
    }
}