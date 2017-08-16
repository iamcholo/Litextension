<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Block_Adminhtml_Index
    extends Mage_Adminhtml_Block_Template{

    protected $_seo_plugin = array(
        'oscommerce' => array(
            'seo_oscommerce_ultimate' => 'Ultimate SEO',
            'seo_oscommerce_power' => 'Power SEO',
            'seo_oscommerce_default' => 'OsCommerce SEO',
            'seo_oscommerce_custom' => 'Custom',
        ),
        'zencart' => array(
            'seo_zencart_ultimate' => 'Ultimate SEO',
            'seo_zencart_ceon' => 'Ceon URI Mapping',
            'seo_zencart_default' => 'ZenCart SEO',
            'seo_zencart_custom' => 'Custom'
        ),
        'virtuemart' => array(
            'seo_virtuemart_default' => 'VirtueMart SEO',
            'seo_virtuemart_custom' => 'Custom'
        ),
        'woocommerce' => array(
            'seo_woocommerce_default' => 'WordPress SEO',
            'seo_woocommerce_custom' => 'Custom'
        ),
        'xtcommerce' => array(
            'seo_xtcommerce_default' => 'xt:Commerce SEO',
            'seo_xtcommerce_custom' => 'Custom'
        ),
        'xcart' => array(
            'seo_xcart_default' => 'X-Cart SEO',
            'seo_xcart_cdseopro' => 'CDSeoPro',
            'seo_xcart_custom' => 'Custom',
        ),
        'opencart' => array(
            'seo_opencart_default' => 'OpenCart SEO',
            'seo_opencart_custom' => 'Custom',
            'seo_opencart_mijoShop' => 'MijoShop'
        ),
        'wpecommerce' => array(
            'seo_wpecommerce_default' => 'WordPress SEO',
            'seo_wpecommerce_custom' => 'Custom'
        ),
        'loaded' => array(
            'seo_loaded_default' => 'Loaded SEO',
            'seo_loaded_custom' => 'Custom',
            'seo_loaded_power' => 'M1 Power'
        ),
        'cscart' => array(
            'seo_cscart_default' => 'Default',
            'seo_cscart_custom' => 'Custom',
            'seo_cscart_extra' => 'Extra'
        ),
        'prestashop' => array(
            'seo_prestashop_default' => 'PrestaShop SEO',
            'seo_prestashop_custom' => 'Custom'
        ),
        'magento' => array(
            'seo_magento_default' => 'Magento SEO',
            'seo_magento_custom' => 'Custom'
        ),
        'interspire' => array(
            'seo_interspire_default' => 'Interspire SEO',
            'seo_interspire_custom' => 'Custom'
        ),
        'cubecart' => array(
            'seo_cubecart_default' => 'CubeCart SEO',
            'seo_cubecart_custom' => 'Custom'
        ),
        'ubercart' => array(
            'seo_ubercart_default' => 'UberCart SEO',
            'seo_ubercart_custom' => 'Custom'
        ),
        'drupalcommerce' => array(
            'seo_drupal_default' => 'DrupalCommerce SEO',
            'seo_drupal_custom' => 'Custom'
        ),
        'oxideshop' => array(
            'seo_oxideshop_default' => 'Oxid-eShop SEO',
            'seo_oxideshop_custom' => 'Custom'
        ),
        'pinnaclecart' => array(
            'seo_pinnaclecart_default' => 'PinnacleCart SEO',
            'seo_pinnaclecart_custom' => 'Custom'
        ),
    );

    /**
     * Get list seo plugin is available in package
     */
    public function getSeoPlugin($cart_type){
        $plugin_exists = array();
        if($cart_type){
            $seo_plugin = $this->_seo_plugin[$cart_type];
            if(!empty($seo_plugin)){
                foreach($seo_plugin as $key => $plugin){
                    if($this->checkSeoPluginExists($key)){
                        $plugin_exists[] = array(
                            'value' => $key,
                            'label' => $plugin
                        );
                    }
                }
            }
        }
        $data = array(
            array('value' => '', 'label' => $this->__('Select Plugin'))
        );
        $data = array_merge($data, $plugin_exists);
        return $data;
    }

    /**
     * Check seo plugin is available
     */
    public function checkSeoPluginExists($name){
        $model_name = 'lecamg/'.$name;
        $model = @Mage::getModel($model_name);
        if($model){
            return true;
        }
        return false;
    }

    /**
     * Convert result of function toOptionArray to option of element select
     */
    protected function _convertOptions($options, $value){
        $html = '';
        if($options){
            foreach($options as $option){
                $html .='<option value="'.$option['value'].'"';
                if($option['value'] == $value){
                    $html .= 'selected="selected"';
                }
                $html .= '>'.$option['label'].'</option>';
            }
        }
        return $html;
    }

    /**
     * Get select cart type
     */
    protected function getCartsOption($value){
        $carts = Mage::getModel('lecamg/system_config_source_types')->toOptionArray();
        return $this->_convertOptions($carts, $value);
    }

    /**
     * Get select stores
     */
    protected function getStoresOption($value){
        $stores = Mage::getModel('adminhtml/system_config_source_store')->toOptionArray();
        $data = array();
        foreach($stores as $store){
            $result = array();
            $result['value'] = $store['value'];
            $_store = Mage::getModel('core/store')->load($store['value']);
            $result['label'] = $store['label'].' ('.Mage::getStoreConfig('general/locale/code', $_store).')';
            $data[] = $result;
        }
        return $this->_convertOptions($data,$value);
    }

    /**
     * Get select root categories
     */
    protected function getCategoriesOption($value){
        $categories = Mage::getModel('adminhtml/system_config_source_category')->toOptionArray(false);
        return $this->_convertOptions($categories, $value);
    }

    /**
     * Get select currencies
     */
    protected function getCurrenciesOption($value){
        $currencies = Mage::getModel('adminhtml/system_config_source_currency')->toOptionArray(false);
        return $this->_convertOptions($currencies, $value);
    }

    /**
     * Get select order status
     */
    protected function getOrderStatusOption($value){
        $html = '';
        $statuses = Mage::getModel('sales/order_status')->getCollection();
        if($statuses){
            foreach($statuses as $status){
                $html .='<option value="'.$status['status'].'"';
                if($status['status'] == $value){
                    $html .= 'selected="selected"';
                }
                $html .= '>'.$status['label'].'</option>';
            }
        }
        return $html;
    }

    /**
     * Get select attribute set
     */
    protected function getAttributesOption($value){
        $data = array();
        $entityTypeId = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attributes = Mage::getModel('eav/entity_attribute_set')->getCollection()->setEntityTypeFilter($entityTypeId);
        foreach($attributes as $attribute){
            $info = $attribute->getData();
            $data[] = array(
                'value' => $info['attribute_set_id'],
                'label' => $info['attribute_set_name']
            );
        }
        return $this->_convertOptions($data, $value);
    }

    protected function getCountriesOption($value){
        $data = array();
        $countries = Mage::getModel('directory/country')->getCollection();
        foreach($countries as $country){
            $data[] = array(
                'value' => $country->getId(),
                'label' => $country->getName()
            );
        }
        return $this->_convertOptions($data, $value);
    }

    protected function getCustomerGroupOption($value){
        $data = array();
        $groups = Mage::getModel('customer/group')->getCollection();
        foreach($groups as $group){
            $data[] = array(
                'value' => $group->getCustomerGroupId(),
                'label' => $group->getCustomerGroupCode()
            );
        }
        return $this->_convertOptions($data, $value);
    }

    /**
     * Get name cart type show in select
     */
    protected function getCartTypeByValue($value){
        $carts = Mage::getModel('lecamg/system_config_source_types')->toOptionArray();
        foreach($carts as $cart){
            if($cart['value'] == $value){
                return $cart['label'];
            }
        }
        return "No Cart";
    }

    /**
     * Get store name show in select
     */
    protected function getStoreNameById($value){
        if(!$value){
            return '';
        }
        $store = Mage::getModel('core/store')->load($value);
        $label = $store->getName().' ('.Mage::getStoreConfig('general/locale/code', $store).')';
        return $label;
    }

    /**
     * Get root category name show in select
     */
    protected function getCategoryNameById($value){
        $label = '';
        if(!$value){
            return '';
        }
        $model = Mage::getModel('adminhtml/system_config_source_category');
        $categories = $model->toOptionArray();
        foreach($categories as $category){
            if($category['value'] == $value){
                $label = $category['label'];
                break ;
            }
        }
        return $label;
    }

    /**
     * Get attribute set name show in select
     */
    protected function getAttributeSetNameById($value){
        if(!$value){
            return '';
        }
        $label = '';
        $entityTypeId = Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId();
        $attributes = Mage::getModel('eav/entity_attribute_set')->getCollection()->setEntityTypeFilter($entityTypeId);
        foreach($attributes as $attribute){
            $info = $attribute->getData();
            if($info['attribute_set_id'] == $value){
                $label = $info['attribute_set_name'];
                break ;
            }
        }
        return $label;
    }

    /**
     * Get currency name show in select
     */
    protected function getCurrencyNameByCode($value){
        if(!$value){
            return '';
        }
        $label = '';
        $currencies = Mage::getModel('adminhtml/system_config_source_currency')->toOptionArray(false);
        if($currencies){
            foreach($currencies as $currency){
                if($currency['value'] == $value){
                    $label = $currency['label'];
                    break;
                }
            }
        }
        return $label;
    }

    protected function getCountryNameById($value){
        $name = "";
        $country = Mage::getModel('directory/country')->load($value);
        if($country){
            $name = $country->getName();
        }
        return $name;
    }

    protected function getCustomerGroupCodeById($value){
        $group = Mage::getModel('customer/group')->load($value);
        return $group->getCustomerGroupCode();
    }

    /**
     * Get order status name show in select
     */
    protected function getOrderStatusByValue($value){
        if(!$value){
            return '';
        }
        $label = '';
        $statuses = Mage::getModel('sales/order_status')->getCollection();
        if($statuses){
            foreach($statuses as $status){
                if($status['status'] == $value){
                    $label = $status['label'];
                    break ;
                }
            }
        }
        return $label;
    }

    protected function _checkFolderMediaPermission(){
        $path = Mage::getBaseDir('media');
        if(is_writable($path)){
            return true;
        } else {
            return false;
        }
    }

    protected function _checkAllowUrlFOpen(){
        if(ini_get('allow_url_fopen')){
            return true;
        } else {
            return false;
        }
    }

    protected function _checkShowWarning(){
        if(!$this->_checkFolderMediaPermission() || !$this->_checkAllowUrlFOpen() || !Mage::getStoreConfig('system/smtp/disable')){
            return true;
        } else {
            return false;
        }
    }

    protected function isRecent()
    {
        $cart = Mage::getModel('lecamg/cart');
        $result = $cart->selectTable('lecamg_recent');
        return $result;
    }
}