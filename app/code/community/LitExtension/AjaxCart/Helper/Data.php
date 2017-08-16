<?php

/**
 * @project     AjaxCart
 * @package	LitExtension_AjaxCart
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_AjaxCart_Helper_Data extends Mage_Core_Helper_Abstract {

    public function checkMageVersion() {
        $version = floatval(Mage::getVersion());
        if ($version < 1.5) {
            return false;
        } else {
            return true;
        }
    }

    public function getUrlRoute(){
        $request = Mage::app()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $result = $module.'_'.$controller.'_'.$action;
        return $result;
    }

    public function isBrowser($name){
        return strpos($_SERVER["HTTP_USER_AGENT"], $name) ? true : false;
    }
}