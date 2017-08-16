<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Helper_Banner extends Mage_Core_Helper_Abstract {

    public function isHomePage(){
        $result = false;
        if(Mage::getBlockSingleton('page/html_header')->getIsHomePage() == 1){
            $result = true;
        }
        return $result;
    }

    public function isProductPage(){
        return $this->_checkByModudelAndController('catalog', 'product');
    }

    public function isCatalogPage(){
        return $this->_checkByModudelAndController('catalog','category');
    }

    public function isCmsPage(){
        return $this->_checkByModudelAndController('cms');
    }

    public function isCustomerPage(){
        return $this->_checkByModudelAndController('customer');
    }

    public function isCheckoutPage(){
        return $this->_checkByModudelAndController('checkout','onepage');
    }

    public function isCartPage(){
        return $this->_checkByModudelAndController('checkout','cart');
    }

    protected function _checkByModudelAndController($module_name, $controller_name= null){
        $result = false;
        $module = Mage::app()->getRequest()->getModuleName();
        $controller = Mage::app()->getRequest()->getControllerName();
        if($controller_name){
            if(($module == $module_name) && ($controller == $controller_name)){
                $result = true;
            }
        } else{
            if(($module == $module_name)){
                $result = true;
            }
        }

        return $result;
    }

    public function isMobile(){
        $regex_match = "/(nokia|iphone|android|motorola|^mot\-|softbank|foma|docomo|kddi|up\.browser|up\.link|"
            . "htc|dopod|blazer|netfront|helio|hosin|huawei|novarra|CoolPad|webos|techfaith|palmsource|"
            . "blackberry|alcatel|amoi|ktouch|nexian|samsung|^sam\-|s[cg]h|^lge|ericsson|philips|sagem|wellcom|bunjalloo|maui|"
            . "symbian|smartphone|mmp|midp|wap|phone|windows ce|iemobile|^spice|^bird|^zte\-|longcos|pantech|gionee|^sie\-|portalmmm|"
            . "jig\s browser|hiptop|^ucweb|^benq|haier|^lct|opera\s*mobi|opera\*mini|320x320|240x320|176x220"
            . ")/i";

        if (preg_match($regex_match, strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }

        if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
            return true;
        }

        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda ','xda-');

        if (in_array($mobile_ua, (array) $mobile_agents)) {
            return true;
        }

        if (isset($_SERVER['ALL_HTTP']) && strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini') > 0) {
            return true;
        }

        return false;
    }

    public function isMobileShow($config){
        $result = true;
        if($this->isMobile() && $config == 0){
            $result = false;
        }
        return $result;
    }
}