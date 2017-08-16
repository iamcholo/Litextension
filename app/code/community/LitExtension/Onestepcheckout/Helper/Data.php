<?php
class LitExtension_Onestepcheckout_Helper_Data extends Mage_Core_Helper_Abstract{

    private $_version = 'CE';

    const XML_PATH_VAT_FRONTEND_VISIBILITY = 'customer/create_account/vat_frontend_visibility';

    const XML_PATH_SHIPPING_VISIBILITY = 'onestepcheckout/default/show_shipping';

    const XML_PATH_TERMS_TYPE = 'onestepcheckout/agreements/terms_type';

    const XML_PATH_COMMENT = 'onestepcheckout/fields_settings/comment';

    const XML_PATH_DELIVERY_DATE = 'onestepcheckout/default/delivery_date';

    public function isAvailableVersion(){

        $mage  = new Mage();
        if (!is_callable(array($mage, 'getEdition'))){
            $edition = 'Community';
        }else{
            $edition = Mage::getEdition();
        }
        unset($mage);

        if ($edition=='Enterprise' && $this->_version=='CE'){
            return false;
        }
        return true;

    }

    public function isEnable(){
        $status = Mage::getStoreConfig('onestepcheckout/global/status');
        return $status;
    }

    public function skipShoppingCartPage(){
        $status = Mage::getStoreConfig('onestepcheckout/default/skip_shopping_cart_page');
        return $status;
    }

    /**
     * Get string with frontend validation classes for attribute
     *
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeValidationClass($attributeCode){
        /** @var $attribute Mage_Customer_Model_Attribute */
        $attribute = isset($this->_attributes[$attributeCode]) ? $this->_attributes[$attributeCode]
            : Mage::getSingleton('eav/config')->getAttribute('customer_address', $attributeCode);
        $class = $attribute ? $attribute->getFrontend()->getClass() : '';

        if (in_array($attributeCode, array('firstname', 'middlename', 'lastname', 'prefix', 'suffix', 'taxvat'))) {
            if ($class && !$attribute->getIsVisible()) {
                $class = ''; // address attribute is not visible thus its validation rules are not applied
            }

            /** @var $customerAttribute Mage_Customer_Model_Attribute */
            $customerAttribute = Mage::getSingleton('eav/config')->getAttribute('customer', $attributeCode);
            $class .= $customerAttribute && $customerAttribute->getIsVisible()
                ? $customerAttribute->getFrontend()->getClass() : '';
            $class = implode(' ', array_unique(array_filter(explode(' ', $class))));
        }

        return $class;
    }

    public function isVatAttributeVisible(){
        return (bool)Mage::getStoreConfig(self::XML_PATH_VAT_FRONTEND_VISIBILITY);
    }


    public function isEnterprise(){
        return Mage::getConfig()->getModuleConfig('Enterprise_Enterprise') && Mage::getConfig()->getModuleConfig('Enterprise_AdminGws') && Mage::getConfig()->getModuleConfig('Enterprise_Checkout') && Mage::getConfig()->getModuleConfig('Enterprise_Customer');
    }


    public function isShowShippingForm(){
        return (bool) Mage::getStoreConfig(self::XML_PATH_SHIPPING_VISIBILITY);
    }

    public function getTermsType(){
        return Mage::getStoreConfig(self::XML_PATH_TERMS_TYPE);
    }

    public function isShowComment(){
        return Mage::getStoreConfig(self::XML_PATH_COMMENT);
    }

    public function isOrderDeliveryEnabled(){
        return Mage::getStoreConfig(self::XML_PATH_DELIVERY_DATE);
    }

    public function styleManagement(){
        return Mage::getStoreConfig('onestepcheckout/template_opc/style_managerment');
    }

    public function getFields()
    {
        $fields = array();
        foreach (Mage::getStoreConfig('onestepcheckout') as $field => $config) {
            if (!strstr($field, 'le_field')) {
                continue;
            }
            if (isset($config['options']) && !empty($config['options'])) {
                $options = array();
                foreach (unserialize($config['options']) as $optionArr) {
                    $options[] = current($optionArr);
                }
                $config['options'] = $options;
            } else {
                $config['options'] = array();
            }
            $fields[$field] = $config;
        }
        return $fields;
    }

    /**
     * Retrieve enabled fields with their configuration
     *
     * @return array
     */
    public function getEnabledFields()
    {
        $fields = array();
        foreach ($this->getFields() as $field => $config) {
            if (!$config['status']) {
                continue;
            }
            $fields[$field] = $config;
        }
        return $fields;
    }

    public function getLoadingHtml($id){
        $color = Mage::helper('onestepcheckout')->styleManagement();
        if(Mage::helper('onestepcheckout')->getThemesManagement() == 'flat')  $color = 'ffffff';
        if($id == 'osc-loader-checkout') $color = 'ffffff';
        $result = "<div id='".$id."'></div>
        <script type='text/javascript'>
            var cl = new CanvasLoader('".$id."');
            cl.setColor('#".$color."');
            cl.setShape('spiral');
            cl.setDiameter(26);
            cl.setDensity(26);
            cl.setRange(1);
            cl.setFPS(23);
            cl.show();
        </script>";
        return $result;
    }

    public function showDiscountCode(){
        return Mage::getStoreConfig('onestepcheckout/default/discount_code');
    }

    public function showCheckoutLoginLink(){
        return Mage::getStoreConfig('onestepcheckout/checkout_login/login_link');
    }

    public function getThemesManagement(){
        return Mage::getStoreConfig('onestepcheckout/template_opc/theme_manager');
    }
}