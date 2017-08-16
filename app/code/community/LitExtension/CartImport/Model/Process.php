<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Process{

    protected $_reservedAttr = array();

    public function __construct(){
        $this->_reservedAttr = Mage::getModel('catalog/product')->getReservedAttributes();
    }

    /**
     * Change index model automatic to manually
     */
    public function stopIndexes(){
        $pCollection = Mage::getSingleton('index/indexer')->getProcessesCollection();
        foreach ($pCollection as $process) {
            $process->setMode(Mage_Index_Model_Process::MODE_MANUAL)->save();
        }
    }

    /**
     * Reindex all type
     */
    public function reIndexes(){
        $result = true;
        $indexingProcesses = Mage::getSingleton('index/indexer')->getProcessesCollection();
        foreach ($indexingProcesses as $process) {
            try{
                $process->reindexEverything();
            } catch(LitExtension_CartImport_Exception $e){
                $result = false;
            }catch(Exception $e){
                $result = false;
            }
        }
        if($result){
            $response['result'] = 'success';
        } else{
            $response['result'] = 'error';
            $response['msg'] = 'An issue occurred while reindexing, please manually reindex in Index Management.';
        }
        return $response;
    }

    /**
     * Clear magento cache
     */
    public function clearCache(){
        $response = array();
        try {
            Mage::app()->cleanCache();
            $response['result'] = 'success';
        } catch (LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = 'An issue occurred while refreshing cache, please manually flush cache in Cache Management.';
        } catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = 'An issue occurred while refreshing cache, please manually flush cache in Cache Management.';
        }
        return $response;
    }

    /**
     * Process clear store with entity select
     */
    public function clearStore($cart){
        $notice = $cart->getNotice();
        $function = $notice['clear_info']['function'];
        return $this->$function($cart, $notice);
    }

    /**
     * Clear product with limit per batch
     */
    protected function _clearProducts($cart, $notice){
        if(!$notice['config']['import']['products']){
            return array(
                'result' => 'process',
                'function' => '_clearCategories'
            );
        }
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('catalog/product')
            ->setStoreIds(array_values($notice['config']['languages']))
            ->getCollection()
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $tables = array(
                'tag',
                'tag_properties',
                'tag_relation',
                'tag_summary'
            );
            foreach($tables as $table){
                $table_name = $cart->getTableName($table);
                $cart->writeQuery('DELETE FROM ' . $table_name );
            }
            $response['function'] = '_clearSeoProducts';
        } else{
            foreach($collection as $product){
                try{
                    $product->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Product Id = {$product->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch (Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Product Id = {$product->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearProducts';
        }
        return $response;
    }

    /**
     * Clear product seo by previous import
     */
    protected function _clearSeoProducts($cart, $notice){
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $table = $resource->getTableName("core_url_rewrite");
        $query = "DELETE FROM {$table} WHERE id_path LIKE 'cm_product%' AND is_system = 0";
        try{
            $write->query($query);
        }catch (Exception $e){
        }
        return array(
            'result' => "process",
            'msg' => "",
            'function' => "_clearCategories"
        );
    }

    /**
     * Clear category with limit per batch
     */
    protected function _clearCategories($cart, $notice){
        if(!$notice['config']['import']['categories']){
            return array(
                'result' => 'process',
                'function' => '_clearCustomers'
            );
        }
        $response = array(
            'result' => 'process'
        );
        if(!$notice['config']['root_category_id']){
            return array(
                'result' => 'success',
                'function' => '_clearCustomers',
                'msg' => ''
            );
        }
        $collection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addFieldToFilter('parent_id', $notice['config']['root_category_id'])
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearSeoCategories';
        } else {
            foreach($collection as $category){
                try{
                    $category->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Category Id = {$category->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Category Id = {$category->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearCategories';
        }
        return $response;
    }

    /**
     * Clear product seo by previous import
     */
    protected function _clearSeoCategories($cart, $notice){
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        $table = $resource->getTableName("core_url_rewrite");
        $query = "DELETE FROM {$table} WHERE id_path LIKE 'cm_category%' AND is_system = 0";
        try{
            $write->query($query);
        }catch (Exception $e){
        }
        return array(
            'result' => "process",
            'msg' => "",
            'function' => "_clearCustomers"
        );
    }

    /**
     * Clear customer with limit per batch
     */
    protected function _clearCustomers($cart, $notice){
        if(!$notice['config']['import']['customers']){
            return array(
                'result' => 'process',
                'function' => '_clearOrders'
            );
        }
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('customer/customer')
            ->setWebsiteId($notice['config']['website_id'])
            ->getCollection()
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearOrders';
        } else {
            foreach($collection as $customer){
                try{
                    $customer->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Customer Id = {$customer->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Customer Id = {$customer->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearCustomers';
        }
        return $response;
    }

    /**
     * Clear order with limit per batch
     */
    protected function _clearOrders($cart, $notice){
        if(!$notice['config']['import']['orders']){
            return array(
                'result' => 'process',
                'function' => '_clearReviews'
            );
        }
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('sales/order')
            ->setStoreId($notice['config']['website_id'])
            ->getCollection()
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearReviews';
        } else {
            foreach($collection as $order){
                try{
                    $order->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Order Id = {$order->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Order Id = {$order->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearOrders';
        }
        return $response;
    }

    /**
     * Clear review with limit per batch
     */
    protected function _clearReviews($cart, $notice){
        if(!$notice['config']['import']['reviews']){
            return array(
                'result' => 'process',
                'function' => '_clearTaxRules'
            );
        }
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('review/review')
            ->setStoreId($notice['config']['website_id'])
            ->getCollection()
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearTaxRules';
        } else {
            foreach($collection as $review){
                try{
                    $review->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Review Id = {$review->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Review Id = {$review->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearReviews';
        }
        return $response;
    }

    /**
     * Clear tax rule with limit per batch
     */
    protected function _clearTaxRules($cart, $notice){
        if(!$notice['config']['import']['taxes']){
            return array(
                'result' => 'success',
                'function' => ''
            );
        }
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('tax/calculation_rule')
            ->setStoreIds(array_values($notice['config']['languages']))
            ->getCollection()
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearTaxCustomers';
        } else{
            foreach($collection as $tax_rule){
                try{
                    $tax_rule->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax rule Id = {$tax_rule->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax rule Id = {$tax_rule->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearTaxRules';
        }
        return $response;
    }

    /**
     * Clear tax customer with limit per batch
     */
    protected function _clearTaxCustomers($cart, $notice){
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('tax/class')
            ->setStoreIds(array_values($notice['config']['languages']))
            ->getCollection()
            ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER)
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearTaxProducts';
        } else {
            foreach($collection as $tax_customer){
                try{
                    $tax_customer->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax customer Id = {$tax_customer->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax customer Id = {$tax_customer->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearTaxCustomers';
        }
        return $response;
    }

    /**
     * Clear tax product with limit per batch
     */
    protected function _clearTaxProducts($cart, $notice){
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('tax/class')
            ->setStoreIds(array_values($notice['config']['languages']))
            ->getCollection()
            ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['function'] = '_clearTaxRates';
        } else {
            foreach($collection as $tax_product){
                try{
                    $tax_product->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax product Id = {$tax_product->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax product Id = {$tax_product->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearTaxProducts';
        }
        return $response;
    }

    /**
     * Clear tax rate with limit per batch
     */
    protected function _clearTaxRates($cart, $notice){
        $response = array(
            'result' => 'process'
        );
        $collection = Mage::getModel('tax/calculation_rate')
            ->setStoreIds(array_values($notice['config']['languages']))
            ->getCollection()
            ->setPageSize($notice['clear_info']['limit'])
            ->setCurPage(1);
        if(!count($collection)){
            $response['result'] = 'success';
            $response['function']  = '';
        } else {
            foreach($collection as $tax_rate){
                try{
                    $tax_rate->delete();
                }catch (LitExtension_CartImport_Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax rate Id = {$tax_rate->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }catch(Exception $e){
                    $response['result'] = 'error';
                    $response['msg'] = "Tax rate Id = {$tax_rate->getId()} delete failed. Error: ".$e->getMessage();
                    break ;
                }
            }
            $response['function'] = '_clearTaxRates';
        }
        return $response;
    }

    /**
     * Import tax rule
     *
     * @param array $data : Data of class Mage_Tax_Model_Calculation_Rule
     * @return array
     */
    public function taxRule($data){
        $response = array();
        $taxRule = Mage::getModel('tax/calculation_rule');
        $taxRule->addData($data);
        try{
            $taxRule->save();
            $response['result'] = 'success';
            $response['mage_id'] = $taxRule->getId();
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * Import tax customer
     *
     * @param array $data : Data of class Mage_Tax_Model_Class
     * @return array
     */
    public function taxCustomer($data){
        $response = array();
        $customerTax = Mage::getModel('tax/class')
            ->getCollection()
            ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER)
            ->addFieldToFilter('class_name', $data['class_name'])
            ->getFirstItem();
        if($customerTax->getId()){
            $response['result'] = 'success';
            $response['mage_id'] = $customerTax->getId();
        } else {
            $newCustomerTax = Mage::getModel('tax/class');
            $newCustomerTax->addData($data);
            $newCustomerTax->setClassType(Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER);
            try{
                $newCustomerTax->save();
                $response['result'] = 'success';
                $response['mage_id'] = $newCustomerTax->getId();
            } catch(LitExtension_CartImport_Exception $e){
                $response['result'] = 'error';
                $response['msg'] = $e->getMessage();
            } catch(Exception $e){
                $response['result'] = 'error';
                $response['msg'] = $e->getMessage();
            }
        }
        return $response;
    }

    /**
     * Import tax product
     *
     * @param array $data : Data of class Mage_Tax_Model_Class
     * @return array
     */
    public function taxProduct($data){
        $response = array();
        $productTax = Mage::getModel('tax/class')
            ->getCollection()
            ->addFieldToFilter('class_type', Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)
            ->addFieldToFilter('class_name', $data['class_name'])
            ->getFirstItem();
        if($productTax->getId()){
            $response['result'] = 'success';
            $response['mage_id'] = $productTax->getId();
        } else {
            $newProductTax = Mage::getModel('tax/class');
            $newProductTax->addData($data);
            $newProductTax->setClassType(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT);
            try{
                $newProductTax->save();
                $response['result'] = 'success';
                $response['mage_id'] = $newProductTax->getId();
            } catch(LitExtension_CartImport_Exception $e){
                $response['result'] = 'error';
                $response['msg'] = $e->getMessage();
            } catch(Exception $e){
                $response['result'] = 'error';
                $response['msg'] = $e->getMessage();
            }
        }
        return $response;
    }

    /**
     * Import tax rate
     *
     * @param array $data : Data of class Mage_Tax_Model_Calculation_Rate
     * @return array
     */
    public function taxRate($data){
        $response = array();
        $taxRate = Mage::getModel('tax/calculation_rate');
        $taxRate->addData($data);
        try{
            $taxRate->save();
            $response['result'] = 'success';
            $response['mage_id'] = $taxRate->getId();
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * Import manufacturer option
     *
     * @param array $data : Data of class Mage_Eav_Model_Entity_Attribute_Option
     * @param int $store_id
     * @return array
     */
    public function manufacturer($data, $store_id = 0){
        $response = array();
        $attribute_option = $data['value']['option'][$store_id];
        $check_exist = $this->_checkAndGetOptionValue($attribute_option, $store_id);
        if($check_exist['result'] == 'success'){
            $response['result'] = 'success';
            $response['mage_id'] = $check_exist['mage_id'];
        } else {
            $setup = Mage::getModel('eav/entity_setup', 'core_setup');
            try{
                $setup->addAttributeOption($data);
                $option = $this->_checkAndGetOptionValue($attribute_option, $store_id);
                if($option['result'] == 'success'){
                    $response['result'] = 'success';
                    $response['mage_id'] = $option['mage_id'];
                } else {
                    $response['result'] = 'error';
                }
            } catch(Exception $e){
                $response['result'] = 'error';
                $response['msg'] = $e->getMessage();
            }
        }
        return $response;
    }

    /**
     * Check manufacturer option exists
     */
    protected function _checkAndGetOptionValue($attribute_option, $store_id){
        $response = array();
        $check = false;
        $options = Mage::getModel("eav/config")
            ->getAttribute("catalog_product", LitExtension_CartImport_Model_Cart::MANUFACTURER_CODE)
            ->setStoreId($store_id)
            ->getSource()
            ->getAllOptions(false);
        foreach($options as $option){
            if($option['label'] == $attribute_option){
                $check = true;
                $response['result'] = 'success';
                $response['mage_id'] = $option['value'];
                break;
            }
        }
        if($check == false){
            $response['result'] = 'error';
        }
        return $response;
    }

    /**
     * Import Category
     *
     * @param array $data : Data of class Mage_Catalog_Model_Category
     * @return array
     */
    public function category($data){
        $response = $multi_stores = $seo_url = array();
        if(isset($data['multi_store'])){
            $multi_stores = $data['multi_store'];
            unset($data['multi_store']);
        }
        if(isset($data['seo_url'])){
            $seo_url = $data['seo_url'];
            unset($data['seo_url']);
        }
        $_categories = Mage::getModel('catalog/category');
        $_categories->addData($data);
        $_categories->setStoreId(0);
        try{
            $_categories->save();
            $response['result'] = 'success';
            $category_id = $_categories->getId();
            $response['mage_id'] = $category_id;
            if($seo_url){
                foreach($seo_url as $key => $url){
                    $urlRewrite = Mage::getModel('core/url_rewrite');
                    $urlRewrite->addData($url);
                    $urlRewrite
                        ->setIsSystem(0)
                        ->setIdPath('cm_category/'.$category_id."-".$key)
                        ->setTargetPath('catalog/category/view/id/'.$category_id);
                    try{
                        $urlRewrite->save();
                    } catch(LitExtension_CartImport_Exception $e){
                        // do nothing
                    } catch(Exception $e){
                        // do nothing
                    }
                }
            }
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        } catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        if($response['result'] == 'success' && $multi_stores && !empty($multi_stores)){
            foreach($multi_stores as $store_data){
                $_category = Mage::getModel('catalog/category')->load($response['mage_id']);
                try{
                    $_category->addData($store_data);
                    $_category->save();
                } catch(LitExtension_CartImport_Exception $e){
                    // do nothing
                } catch(Exception $e){
                    // do nothing
                }

            }
        }
        return $response;
    }

    /**
     * Import product
     *
     * @param array $data
     * @return array
     */
    public function product($data){
        $response = $multi_store = $image_import_path = $galleries = $seo_url = array();
        if(isset($data['multi_store'])){
            $multi_store = $data['multi_store'];
            unset($data['multi_store']);
        }
        if(isset($data['image_import_path'])){
            $image_import_path = $data['image_import_path'];
            unset($data['image_import_path']);
        }
        if(isset($data['image_gallery'])){
            $galleries = $data['image_gallery'];
            unset($data['image_gallery']);
        }
        if(isset($data['seo_url'])){
            $seo_url = $data['seo_url'];
            unset($data['seo_url']);
        }
        try{
            $_product = Mage::getModel('catalog/product');
            $_product->addData($data);
            if($image_import_path && isset($image_import_path['path']) && file_exists($image_import_path['path'])){
                $_product->addImageToMediaGallery($image_import_path['path'] ,array('thumbnail', 'small_image', 'image'), true, false, $image_import_path['label']);
            }
            if($galleries){
                foreach($galleries as $gallery){
                    if(file_exists($gallery['path'])){
                        $_product->addImageToMediaGallery($gallery['path'], array(), true, false, $gallery['label']);
                    }
                }
            }
            $_product->save();
            $product_id = $_product->getId();
            if($seo_url){
                foreach($seo_url as $key => $url){
                    $urlRewrite = Mage::getModel('core/url_rewrite');
                    $urlRewrite->addData($url);
                    $urlRewrite
                        ->setIsSystem(0)
                        ->setIdPath('cm_product/' . $product_id . "-" . $key)
                        ->setTargetPath('catalog/product/view/id/'.$product_id);
                    try{
                        $urlRewrite->save();
                    } catch(LitExtension_CartImport_Exception $e){
                        // do nothing
                    } catch(Exception $e){
                        // do nothing
                    }
                }
            }

            $response['result'] = 'success';
            $response['mage_id'] = $product_id;
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        if($response['result'] == 'success' && $multi_store && !empty($multi_store)){
            foreach($multi_store as $store_data){
                try{
                    $product = Mage::getModel('catalog/product')->setStoreId($store_data['store_id'])->load($response['mage_id']);
                    $product->addData($store_data);
                    $product->save();
                } catch(LitExtension_CartImport_Exception $e){
                    //do nothing
                } catch(Exception $e){
                    //do nothing
                }
            }
        }
        return $response;
    }

    /**
     * Import customer
     *
     * @param array $data : Data of class Mage_Customer_Model_Customer
     * @return array
     */
    public function customer($data){
        $response = array();
        try{
            $cus_id = false;
            if(isset($data['id'])){
                $cus_id = $data['id'];
                unset($data['id']);
            }
            $customer = Mage::getModel('customer/customer');
            if($cus_id){
                $customer->setId($cus_id);
            }
            $customer->addData($data);
            $customer->setPassword($customer->generatePassword());
            $customer->setConfirmation(null);
            $customer->save();
            $response['result'] = 'success';
            $response['mage_id'] = $customer->getId();
        }catch (LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        } catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * Import customer
     *
     * @param array $data
     * @param boolean $pre_ord
     * @return array
     */
    public function order($data, $pre_ord){
        $response = array();
        $address_billing = $data['address_billing'];
        $address_shipping = $data['address_shipping'];
        $carts = $data['carts'];
        $orderData = $data['order'];
        $order_src_id = $data['order_src_id'];
        $store_id = $orderData['store_id'];
        try{
            if($pre_ord){
                $increment_id = $order_src_id;
            } else {
                $increment_id = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($store_id);
            }
            $order = Mage::getModel('sales/order')
                ->setIncrementId($increment_id)
                ->setStoreId($store_id)
                ->setQuoteId(0);
            $billingAddress = Mage::getModel('sales/order_address')->addData($address_billing);
            $shippingAddress = Mage::getModel('sales/order_address')->addData($address_shipping);
            $order->setBillingAddress($billingAddress);
            $order->setShippingAddress($shippingAddress)
                ->setShippingMethod('flatrate_flatrate');
            $orderPayment = Mage::getModel('sales/order_payment')
                ->setStoreId($store_id)
                ->setMethod('checkmo');
            $order->setPayment($orderPayment);
            foreach ($carts as $item) {
                if(isset($item['product_id'])){
                    if(LitExtension_CartImport_Model_Custom::PRODUCT_REPORT){
                        $this->_addProductIsView($item['product_id'], $orderData['customer_id']);
                    }
                    if(LitExtension_CartImport_Model_Custom::PRODUCT_BESTSELLER){
                        $this->_addProductIsBestseller($orderData['created_at'], $item);
                    }
                }
                $orderItem = Mage::getModel('sales/order_item')
                    ->setStoreId($store_id)
                    ->setQuoteItemId(0)
                    ->setQuoteParentItemId(NULL);
                $orderItem->addData($item);
                $order->addItem($orderItem);
            }
            $order->addData($orderData);
            $order->save();
            $response['result'] = 'success';
            $response['mage_id'] = $order->getId();
        }catch (Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    /**
     * Import review
     *
     * @param array $data : Data of class Mage_Review_Model_Review
     * @param array $review_options
     * @return array
     */
    public function review($data, $review_options){
        $response = $rating = array();
        if(isset($data['rating'])){
            $rating = $data['rating'];
            unset($data['rating']);
        }
        $created_at = $data['created_at'];
        $_review = Mage::getModel('review/review');
        $_review->addData($data);
        try{
            $_review->save();
            $_review->setCreatedAt($created_at);
            $_review->save();
            $review_id = $_review->getId();
            if($rating){
                foreach($review_options as $rating_id => $option_ids){
                    $_rating = Mage::getModel('rating/rating')
                        ->setRatingId($rating_id)
                        ->setReviewId($review_id)
                        ->addOptionVote($option_ids[$rating -1],$data['entity_pk_value']);
                }
            }
            $response['result'] = 'success';
            $response['mage_id'] = $review_id;
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        } catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    // extend function

    /**
     * Import attribute
     */
    public function attribute($data, $attr_edit = array(), $rename_code_if_exists = true, $store_id = 0){
        $data = $this->_prepareAttributeData($data);
        if(!$rename_code_if_exists){
            $mode = $this->_checkAttributeSync($data);
        } else {
            $data = $this->_validAttrCodeReserve($data);
            $data = $this->_createAttributeCodeIfInvalid($data);
            $mode = $data['check'];
            unset($data['check']);
        }

        $attrModel = Mage::getModel('catalog/resource_eav_attribute');
        if($mode == 'valid'){
            $data_no_option = $data;
            unset($data_no_option['option']);
            $option = $data['option'];
            $option = $this->_uniqueOptionValue($option, $store_id);
            $attrModel->addData($data_no_option);
            $attrModel->setOption($option);
        } else if($mode == 'sync'){
            $attrModel->loadByCode($data['entity_type_id'], $data['attribute_code']);
            if($attr_edit && !empty($attr_edit)){
                $attrModel->addData($attr_edit);
            }
            if($data['option']){
                $option = $data['option'];
                $option = $this->_uniqueOptionValue($option, $store_id);
                $option = $this->_duplicateAttributeOptionExists($attrModel, $option, $store_id);
                $attrModel->setOption($option);
            }
        } else {
            return false;
        }
        try{
            $attrModel->save();
            $attr_id = $attrModel->getId();
        }catch (Exception $e){
            return false;
        }
        if($data['attribute_set_id']){
            $setup = Mage::getModel('eav/entity_setup', 'core_setup');
            try{
                $setup->addAttributeToSet(Mage_Catalog_Model_Product::ENTITY, $data['attribute_set_id'], '', $attr_id);
            }catch (Exception $e){}
        }
        $result['attribute_id'] = $attr_id;
        $result['attribute_code'] = $data['attribute_code'];
        $optionValues = $this->getAttributeOptionValueByListOption($data, array(), $store_id);
        $result['option_ids'] = $optionValues;
        return $result;
    }

    protected function _prepareAttributeData($data){
        $default_data = array(
            'attribute_set_id'              => null,
            'attribute_group_id'            => null,
            'is_global'                     => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
            'default_value_text'            => '',
            'default_value_yesno'           => false,
            'default_value_date'            => '',
            'default_value_textarea'        => '',
            'is_unique'                     => false,
            'is_required'                   => false,
            'frontend_class'                => '',
            'is_configurable'               => false,
            'is_searchable'                 => false,
            'is_visible_in_advanced_search' => false,
            'is_comparable'                 => false,
            'is_filterable'                 => false,
            'is_filterable_in_search'       => false,
            'is_used_for_promo_rules'       => false,
            'is_html_allowed_on_front'      => true,
            'is_visible_on_front'           => false,
            'used_in_product_listing'       => false,
            'used_for_sort_by'              => false,
            'apply_to'                      => array(),
            'is_user_defined'               => true,
        );
        $data = array_merge($default_data, $data);
        $data['source_model'] = Mage::helper('catalog/product')->getAttributeSourceModelByInputType($data['frontend_input']);
        $data['backend_model'] = Mage::helper('catalog/product')->getAttributeBackendModelByInputType($data['frontend_input']);
        $data['backend_type'] = Mage::getModel('catalog/resource_eav_attribute')->getBackendTypeByInput($data['frontend_input']);
        $defaultValueField = Mage::getModel('catalog/resource_eav_attribute')->getDefaultValueByInput($data['frontend_input']);
        if ($defaultValueField) {
            $data['default_value'] = $data[$defaultValueField];
        }
        return $data;
    }

    protected function _checkAttributeSync($data){
        $result = 'valid';
        $entityTypeId = $data['entity_type_id'];
        $attributeCode = $data['attribute_code'];
        $frontend_input = $data['frontend_input'];
        $backend_type = $data['backend_type'];
        $attr = Mage::getModel('catalog/resource_eav_attribute');
        $attr->loadByCode($entityTypeId,$attributeCode);
        if($attr->getId()){
            $attr_frontend_input = $attr->getFrontendInput();
            $attr_backend_type = $attr->getBackendType();
            if($attr_frontend_input == $frontend_input && $attr_backend_type == $backend_type){
                $result = 'sync';
            } else{
                $result = 'invalid';
            }
        }
        return $result;
    }

    protected function _validAttrCodeReserve($data){
        $attributeCode = $data['attribute_code'];
        $suffix = 'a';
        while(in_array($data['attribute_code'], $this->_reservedAttr)){
            $data['attribute_code'] = $attributeCode . "_" . $suffix;
            $suffix++;
        }
        return $data;
    }

    protected function _uniqueOptionValue($optionValues, $store_id = 0){
        $result = array();
        $unique = array();
        foreach($optionValues['value'] as $key => $value){
            if(!in_array($value[$store_id], $unique)){
                $result[$key] = $value;
                $unique[] = $value[$store_id];
            }
        }
        return array('value' => $result);
    }

    protected function _createAttributeCodeIfInvalid($data){
        $attributeCode = $data['attribute_code'];
        $suffix = 'a';
        $result = $this->_checkAttributeSync($data);
        while($result == 'invalid'){
            $data['attribute_code'] = $attributeCode.'_'.$suffix;
            $result = $this->_checkAttributeSync($data);
            $suffix++;
        }
        $data['check'] = $result;
        return $data;
    }

    protected function _duplicateAttributeOptionExists(Mage_Catalog_Model_Resource_Eav_Attribute $object, $attrOptions, $store_id = 0){
        $options = $object->setStoreId($store_id)->getSource()->getAllOptions(false);
        if($attrOptions && $attrValues = $attrOptions['value']){
            foreach($attrValues as $option_key => $attr_value){
                foreach($options as $option){
                    if($attr_value[$store_id] == $option['label']){
                        unset($attrValues[$option_key]);
                        break ;
                    }
                }
            }
        }
        $attrOptions['value'] = $attrValues;
        return $attrOptions;
    }

    public function getAttributeOptionValueByListOption($data, $attrOptions = array(), $store_id = 0){
        $object = Mage::getModel('catalog/resource_eav_attribute');
        $object->loadByCode($data['entity_type_id'], $data['attribute_code']);
        $options = $object->setStoreId($store_id)->getSource()->getAllOptions(false);
        $optionIds = array();
        if(!$attrOptions) $attrOptions = $data['option'];
        if($attrOptions && $attrValues = $attrOptions['value']){
            foreach($attrValues as $option_key => $attr_value){
                foreach($options as $option){
                    if($attr_value[$store_id] == $option['label']){
                        $optionIds[$option_key] = $option['value'];
                        break ;
                    }
                }
            }
        }
        return $optionIds;
    }

    /**
     * Import address
     */
    public function address($data, $customer_id){
        $response = array();
        try{
            $address = Mage::getModel('customer/address');
            $address->setCustomerId($customer_id);
            $address->addData($data);
            $address->save();
            $response['result'] = 'success';
            $response['mage_id'] = $address->getId();
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        } catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    protected function _addProductIsView($product_id, $customer_id = false){
        if(!$customer_id) $customer_id = 0;
        $data = array(
            'event_type_id' => 1,
            'object_id' => $product_id,
            'subject_id' => $customer_id,
            'subtype' => 1,
            'store_id' => 0,
        );
        try{
            $eventModel = Mage::getModel('reports/event');
            $eventModel->addData($data);
            $eventModel->save();
        }catch (Exception $e){}
    }

    protected function _addProductIsBestseller($create_at, $item){
        try{
            $resource = Mage::getSingleton('core/resource');
            $write = $resource->getConnection('core_write');
            $tables = array(
                'sales/bestsellers_aggregated_daily',
                'sales/bestsellers_aggregated_monthly',
                'sales/bestsellers_aggregated_yearly'
            );
            $period = $this->_getDateFromDateTime($create_at);
            foreach($tables as $table){
                $table_name = $resource->getTableName($table);
                $query = "INSERT INTO `{$table_name}` (`id`, `period`, `store_id`, `product_id`, `product_name`, `product_price`, `qty_ordered`, `rating_pos`)
                                VALUES (null, '{$period}', 0, '{$item['product_id']}', '{$item['name']}', '{$item['original_price']}', '{$item['qty_ordered']}', 1)";
                $write->query($query);
            }
        }catch (Exception $e){}
        return ;
    }

    protected function _getDateFromDateTime($datetime){
        $dt = new DateTime($datetime);
        $date = $dt->format('Y-m-d');
        return $date;
    }

    public function ordersComment($order_id, $comment){
        try{
            $order = Mage::getModel('sales/order')->load($order_id);
            $history = Mage::getModel('sales/order_status_history')
                ->setStatus($comment['status'])
                ->setComment($comment['comment'])
                ->setEntityName(Mage_Sales_Model_Order::HISTORY_ENTITY_NAME)
                ->setIsCustomerNotified($comment['is_customer_notified'])
                ->setCreatedAt($comment['created_at']);
            $order->addStatusHistory($history);
            if($comment['updated_at']){
                $order->setUpdatedAt($comment['updated_at'])
                    ->setStatus($comment['status']);
                if($comment['status']){
                    $order->setData('state', $comment['status']);
                }
            }
            $order->save();
        } catch(Exception $e){
            // do nothing
        }
        return ;
    }

    public function currencyAllow($allow_currency){
        $response = array();
        $config = Mage::getModel('core/config');
        try{
            $config->saveConfig('currency/options/allow', $allow_currency);
            $response['result'] = 'success';
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    public function currencyDefault($default_currency){
        $response = array();
        $config = Mage::getModel('core/config');
        try{
            $config->saveConfig('currency/options/default', $default_currency);
            $response['result'] = 'success';
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }

    public function currencyRate($data){
        $response = array();
        try{
            Mage::getModel('directory/currency')->saveRates($data);
            $response['result'] = 'success';
        } catch(LitExtension_CartImport_Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        } catch(Exception $e){
            $response['result'] = 'error';
            $response['msg'] = $e->getMessage();
        }
        return $response;
    }


}