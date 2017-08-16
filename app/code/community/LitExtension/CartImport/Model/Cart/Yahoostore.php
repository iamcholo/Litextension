<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Cart_Yahoostore
extends LitExtension_CartImport_Model_Cart{
	
	const ABC_PRO       = 'lecaip_aabaco_product';	
	const ABC_ORD       = 'lecaip_aabaco_order';
	const ABC_ORD_ITEM  = 'lecaip_aabaco_order_item';
	const ABC_ORD_ITEM_OPT = 'lecaip_aabaco_order_item_option';	
	
	protected $_demo_limit = array(
			'exchangeRates' => 100,
			'taxes' => 100,
			'manufacturers' => 100,
			'categories' => 100,
			'products' => 100,
			'optionCategories' => 100,
			'options' => 100,
			'kits' => 100,
			'kitLinks' => 100,
			'customers' => 100,
			'orders' => 100,
			'orderDetails' => 100,
			'reviews' => 0
	);
	
	public function __construct(){
		parent::__construct();
	}
	
	public function getListUpload(){
		$product_abc_guide = '
            Log into Yahoo Merchant/Aabaco admin panel.
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>Click <b>Catalog Manager</b> link in the <b>Edit</b> column.. Click on “Customize the table information you see” button.</li>
                <li>In the popup window, add all columns from left pane to right pane, click “Apply changes” to close the popup.</li>
                <li>Click on “Download table as .zip file”.</li>
                <li>Unzip the file to have .csv file. </li>
                <li>Upload this data to Migration Wizard and proceed to the next step.</li>
              </ol>
            </div>';
    	$customer_abc_guide = '
            Log into Yahoo Merchant/Aabaco admin panel.
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li> Click <b>Customer Manager</b> link in the <b>Process</b> column.</li>
                <li> Choose to export all customers in csv format. </li>
              </ol>
            </div>';
    	$order_abc_guide = '
            Log into Yahoo Merchant/Aabaco admin panel.
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>Click the <b>Orders</b> link in the <b>Process</b> column.</li>
                <li>Enter a range of orders to expor.</li>
                <li>Select an export format from the pull-down menu. Choose .csv.</li>
                <li>Click the <b>Export</b> button. Access and Generic CSV format, click the Download link for Orders.csv and save the file.</li>
                <li>Upload this data to Migration Wizard and proceed to the next step.</li>
              </ol>
            </div>';
    	$order_item_guide = '
            Log into Yahoo Merchant/Aabaco admin panel.
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>Click the <b>Orders</b> link in the <b>Process</b> column.</li>
                <li>Enter a range of orders to expor.</li>
                <li>Select an export format from the pull-down menu. Choose .csv.</li>
                <li>Click the <b>Export</b> button. Access and Generic CSV format, click the Download link for Items.csv and save the file.</li>
                <li>Upload this data to Migration Wizard and proceed to the next step.</li>
              </ol>
            </div>';
    	$order_item_option_guide = '
            Log into Yahoo Merchant/Aabaco admin panel.
            <div class="notice_message">
              <ol style="list-style: square; padding-left: 20px;">
                <li>Click the <b>Orders</b> link in the <b>Process</b> column.</li>
                <li>Enter a range of orders to expor.</li>
                <li>Select an export format from the pull-down menu. Choose .csv.</li>
                <li>Click the <b>Export</b> button. Access and Generic CSV format, click the Download link for Options.csv and save the file.</li>
                <li>Upload this data to Migration Wizard and proceed to the next step.</li>
              </ol>
            </div>';	
		$upload = array(				
				array('value' => 'products', 'label' => "Products"),
				array('value' => 'guide', 'label' => $product_abc_guide),
				array('value' => 'customers', 'label' => "Customers"),
				array('value' => 'guide', 'label' => $customer_abc_guide),
				array('value' => 'orders', 'label' => "Orders"),
				array('value' => 'guide', 'label' => $order_abc_guide),
				array('value' => 'orderItems', 'label' => "Order Items"),
				array('value' => 'guide', 'label' => $order_item_guide),
				array('value' => 'orderItemOptions', 'label' => "Order Item Options"),
				array('value' => 'guide', 'label' => $order_item_option_guide),
		);
		return $upload;
	}
	
	public function clearPreSection(){		
		$tables = array(
				self::ABC_PRO,
				self::ABC_ORD,
				self::ABC_ORD_ITEM,
				self::ABC_ORD_ITEM_OPT,				
		);
		$folder = $this->_folder;
		foreach($tables as $table){
			$table_name = $this->getTableName($table);
			$query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
			$this->writeQuery($query);
		}
	}
	
	public function getAllowExtensions()
	{
		return array('csv');
	}
	
	/**
	 * Get file name upload by value list upload
	 */
	public function getUploadFileName($upload_name)
	{
		return $upload_name . '.csv';
	}
	
	public function getUploadInfo($up_msg){
		$files = array_filter($this->_notice['config']['files']);
		if(!empty($files)){
			if(!$this->_notice['config']['files']['orders']){
				//$this->_notice['config']['config_support']['order_status_map'] = false;
				$this->_notice['config']['import_support']['orders'] = false;
				$this->_notice['config']['import_support']['customers'] = false;
			}
			if(!$this->_notice['config']['files']['products']){
				$this->_notice['config']['import_support']['products'] = false;
			}
			foreach($files as $type => $upload){
				if($upload){
					$func_construct = $type . "TableConstruct";
					$construct = $this->$func_construct();
					$validate = isset($construct['validation']) ? $construct['validation'] : false;
					$csv_file = Mage::getBaseDir('media'). self::FOLDER_SUFFIX . $this->_notice['config']['folder'] . '/' . $type . '.csv';
					$readCsv = $this->readCsv($csv_file, 0, 1, false);
					if($readCsv['result'] == 'success'){
						foreach($readCsv['data'] as $item){							
							if($validate){
								foreach($validate as $row){
									if(!in_array($row, $item['title'])){
										$up_msg[$type] = array(
												'elm' => '#ur-' . $type,
												'msg' => "<div class='uir-warning'> File uploaded has incorrect structure</div>"
										);
									}
								}
							}
						}
					}
	
				}
			}			
			$this->_notice['csv_import']['function'] = '_setupStorageCsv';
		}
		return array(
				'result' => 'success',
				'msg' => $up_msg
		);
	}
	
	public function displayConfig(){
		$parent = parent::displayConfig();
		if($parent["result"] != "success"){
			return $parent;
		}
		$response = array();
		$category_data = array("Root category");
		$attribute_data = array("Root attribute set");
		$languages_data = array(1 => "Default language");
		$order_status_data = array();		
		$this->_notice['config']['default_lang'] = 1;
		$this->_notice['config']['category_data'] = $category_data;
		$this->_notice['config']['attribute_data'] = $attribute_data;
		$this->_notice['config']['languages_data'] = $languages_data;
		$this->_notice['config']['order_status_data'] = $order_status_data;
		$this->_notice['config']['config_support']['order_status_map'] = false;
		$this->_notice['config']['config_support']['currency_map'] = false;
		$this->_notice['config']['config_support']['country_map'] = false;
		$this->_notice['config']['import_support']['taxes'] = false;
		$this->_notice['config']['import_support']['categories'] = false;
		$this->_notice['config']['import_support']['reviews'] = false;	
		$this->_notice['config']['import_support']['manufacturers'] = false;
		$response['result'] = 'success';		
		return $response;
	}
	
	public function displayConfirm($params){
		parent::displayConfirm($params);
		return array(
				'result' => 'success'
		);
	}
	
	public function displayImport(){		
		$product_table = $this->getTableName(self::ABC_PRO);
		$table = $this->getTableName(self::ABC_ORD);
		$queries = array(				
				'products' => "SELECT COUNT(1) AS count FROM {$product_table} WHERE folder = '{$this->_folder}'",
				'customers' => "SELECT COUNT(1) AS count FROM {$table} WHERE folder = '{$this->_folder}'",
				'orders' => "SELECT COUNT(1) AS count FROM {$table} WHERE folder = '{$this->_folder}'",
			
		);
		$data = array();
		foreach($queries as $type => $query){
			$read = $this->readQuery($query);
			if($read['result'] != 'success'){
				return $this->errorDatabase();
			}
			$count = $this->arrayToCount($read['data'], 'count');
			$data[$type] = $count;
		}
		$data = $this->_limit($data);
		foreach($data as $type => $count){
			$this->_notice[$type]['total'] = $count;
		}
		if(LitExtension_CartImport_Model_Custom::CLEAR_IMPORT){
			$del = $this->deleteTable(self::TABLE_IMPORT, array(
					'folder' => $this->_folder
			));
			if(!$del){
				return $this->errorDatabase();
			}
		}
		return array(
				'result' => 'success'
		);
	}
	
	public function storageCsv(){
		if(LitExtension_CartImport_Model_Custom::CSV_STORAGE){
			return $this->_custom->storageCsvCustom($this);
		}
		$function = $this->_notice['csv_import']['function'];
		if(!$function){
			return array(
					'result' => 'success',
					'msg' => ''
			);
		}
		return $this->$function();
	}
	
	/**
	 * Config currency
	 */
	public function configCurrency(){
		return array(
				'result' => 'success'
		);
	}
	
	public function prepareImportProducts(){
		parent::prepareImportProducts();
		$this->_notice['extend']['website_ids']= $this->getWebsiteIdsByStoreIds($this->_notice['config']['languages']);
	}
	
	public function getProducts(){
		$id_src = $this->_notice['products']['id_src'];
		$limit = $this->_notice['setting']['products'];
		$product_table = $this->getTableName(self::ABC_PRO);
		$query = "SELECT * FROM {$product_table} WHERE folder = '{$this->_folder}' AND product_id > {$id_src} ORDER BY product_id ASC LIMIT {$limit}";
		$result = $this->readQuery($query);
		if($result['result'] != 'success'){
			return $this->errorDatabase(true);
		}
		if($this->_notice['config']['add_option']['seo_url'] && $this->_notice['config']['add_option']['seo_plugin']){
		    $seo_model = 'lecaip/' . $this->_notice['config']['add_option']['seo_plugin'];
		    $this->_seo = Mage::getModel($seo_model);
		}
		return $result;
	}
	
	public function getProductId($product){
		return $product['product_id'];
	}
	
	public function checkProductImport($product){
		$product_code = $product['code'];
		return $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $product_code);
	}
	
	public function convertProduct($product){
		if(LitExtension_CartImport_Model_Custom::PRODUCT_CONVERT){
			return $this->_custom->convertProductCustom($this, $product);
		}		
		$pro_data['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;		
		$pro_convert = $this->_convertProduct($product);
		if($pro_convert['result'] != 'success'){
			return $pro_convert;
		}
		$pro_data = array_merge($pro_data, $pro_convert['data']);
		return array(
				'result' => "success",
				'data' => $pro_data
		);
	}
	
	public function importProduct($data, $product){
		if(LitExtension_CartImport_Model_Custom::PRODUCT_IMPORT){
			return $this->_custom->importProductCustom($this, $data, $product);
		}
		$id_src = $this->getProductId($product);
		$productIpt = $this->_process->product($data);
		if($productIpt['result'] == 'success'){
			$id_desc = $productIpt['mage_id'];
			$this->productSuccess($id_src, $id_desc, $product['code']);
		} else {
			$productIpt['result'] = 'warning';
			$msg = "Product code = {$product['code']} import failed. Error: " . $productIpt['msg'];
			$productIpt['msg'] = $this->consoleWarning($msg);
		}
		return $productIpt;
	}
	
	public function afterSaveProduct($product_mage_id, $data, $product){
		if(parent::afterSaveProduct($product_mage_id, $data, $product)){
			return ;
		}
	}
	
	public function getCustomers(){
		$id_src = $this->_notice['customers']['id_src'];
		$limit = $this->_notice['setting']['customers'];
		$customer_table = $this->getTableName(self::ABC_ORD);
		$query = "SELECT * FROM {$customer_table} WHERE folder = '{$this->_folder}' AND Order_ID > '{$id_src}' ORDER BY Order_ID ASC LIMIT {$limit}";
		$result = $this->readQuery($query);
		if($result['result'] != 'success'){
			return $this->errorDatabase(true);
		}
		return $result;
	}
	
	public function getCustomerId($customer){
		return $customer['Order_ID'];
	}
	
	public function convertCustomer($customer){
		if(LitExtension_CartImport_Model_Custom::CUSTOMER_CONVERT){
			return $this->_custom->convertCustomerCustom($this, $customer);
		}
		$cus_data = array();
		if($this->_notice['config']['add_option']['pre_cus']){
			$cus_data['id'] = $customer['Order_ID'];
		}
		$cus_data['website_id'] = $this->_notice['config']['website_id'];
		$cus_data['email'] = $customer['Email'];
		$cus_data['firstname'] = $customer['Ship_Name'];
		$cus_data['created_at'] = $customer['Date'] ? date('Y-m-d H:i:s', strtotime($customer['Date'])) : null;
		$cus_data['is_subscribed'] = 0;
		$cus_data['group_id'] = 1;
		$custom = $this->_custom->convertCustomerCustom($this, $customer);
		if($custom){
			$cus_data = array_merge($cus_data, $custom);
		}
		return array(
				'result' => 'success',
				'data' => $cus_data
		);
	}
	
	/**
	 * Process after one customer import successful
	 *
	 * @param int $customer_mage_id : Id of customer import to magento
	 * @param array $data : Data of function convertCustomer
	 * @param array $customer : One row of object function getCustomers
	 * @return boolean
	 */
	public function afterSaveCustomer($customer_mage_id, $data, $customer){
		if(parent::afterSaveCustomer($customer_mage_id, $data, $customer)){
			return ;
		}
		$address = array();
		$country_cus_code = 'US';
		$address['firstname'] = $customer['Ship_Name'];
		$address['country_id'] = $country_cus_code;
		$address['street'] = $customer['Ship_Address_1'] . "\n" . $customer['Ship_Address_2'];
		$address['postcode'] = $customer['Ship_Zip'];
		$address['city'] = $customer['Ship_City'];
		$address['telephone'] = $customer['Ship_Phone'];
		$address['company'] = $customer['Ship_Company'];
		if($customer['Ship_State']){
			$region_id = false;
			if(strlen($customer['Ship_State']) == 2){
				$region = Mage::getModel('directory/region')->loadByCode($customer['Ship_State'], $country_cus_code);
				if($region->getId()){
					$region_id = $region->getId();
				}
			} else {
				$region_id = $this->getRegionId($customer['Ship_State'], $country_cus_code);
			}
			if($region_id){
				$address['region_id'] = $region_id;
			}
			$address['region'] = $customer['Ship_State'];
		} else {
			$address['region_id'] = 0;
		}
		$address_ipt = $this->_process->address($address, $customer_mage_id);
		if($address_ipt['result'] == 'success'){
			try{
				$cus = Mage::getModel('customer/customer')->load($customer_mage_id);
				$cus->setDefaultBilling($address_ipt['mage_id']);
				$cus->setDefaultShipping($address_ipt['mage_id']);
			}catch (Exception $e){}
		}
	}
	
	public function getOrders(){
		$id_src = $this->_notice['orders']['id_src'];
		$limit = $this->_notice['setting']['orders'];
		$order_table = $this->getTableName(self::ABC_ORD);
		$query = "SELECT * FROM {$order_table} WHERE folder = '{$this->_folder}' AND Order_ID > '{$id_src}' ORDER BY Order_ID ASC LIMIT {$limit}";
		$result = $this->readQuery($query);
		if($result['result'] != 'success'){
			return $this->errorDatabase(true);
		}
		return $result;
	}
	
	public function getOrderId($order){
		return $order['Order_ID'];
	}
	
	public function convertOrder($order){
		if(LitExtension_CartImport_Model_Custom::ORDER_CONVERT){
			return $this->_custom->convertOrderCustom($this, $order);
		}	
		$data = $address_billing = $address_shipping = $carts = array();
		$country_bill_code = $country_ship_code = 'US';
		$address_billing['firstname'] = $order['Bill_Name'];
		$address_billing['company'] = $order['Bill_Company'];
		$address_billing['email'] = $order['Email'];
		$address_billing['country_id'] = 'US';
		$address_billing['street'] = $order['Bill_Address_1'] . "\n" . $order['Bill_Address_2'];
		$address_billing['postcode'] = $order['Bill_Zip'];
		$address_billing['city'] = $order['Bill_City'];
		$address_billing['telephone'] = $order['Bill_Phone'];
		if($order['Bill_State']){
			$bil_region_id = false;
			if(strlen($order['Bill_State']) == 2){
				$region = Mage::getModel('directory/region')->loadByCode($order['Bill_State'], $country_bill_code);
				if($region->getId()){
					$bil_region_id = $region->getId();
				}
			} else {
				$bil_region_id = $this->getRegionId($order['Bill_State'], $country_bill_code);
			}
			if($bil_region_id){
				$address_billing['region_id'] = $bil_region_id;
			}
			$address_billing['region'] = $order['Bill_State'];
		} else {
			$address_billing['region_id'] = 0;
		}
		
		$address_shipping['firstname'] = $order['Ship_Name'];
		$address_shipping['company'] = $order['Ship_Company'];
		$address_shipping['email'] = $order['Email'];
		$address_shipping['country_id'] = 'US';
		$address_shipping['street'] = $order['Ship_Address_1'] . "\n" . $order['Ship_Address_2'];
		$address_shipping['postcode'] = $order['Ship_Zip'];
		$address_shipping['city'] = $order['Ship_City'];
		$address_shipping['telephone'] = $order['Ship_Phone'];
		if($order['Ship_State']){
			$ship_region_id = false;
			if(strlen($order['Ship_State']) == 2){
				$region = Mage::getModel('directory/region')->loadByCode($order['Ship_State'], $country_ship_code);
				if($region->getId()){
					$ship_region_id = $region->getId();
				}
			} else {
				$ship_region_id = $this->getRegionId($order['Ship_State'], $country_ship_code);
			}
			if($ship_region_id){
				$address_shipping['region_id'] = $ship_region_id;
			}
			$address_shipping['region'] = $order['Ship_State'];
		} else {
			$address_shipping['region_id'] = 0;
		}
		$ordDtlSrc = $this->readQuery("SELECT * FROM " . self::ABC_ORD_ITEM . " WHERE folder = '{$this->_folder}' AND Order_ID = {$order['Order_ID']}");
		$ordDtl = ($ordDtlSrc['result'] == 'success') ? $ordDtlSrc['data'] : array();
		if($ordDtl){
			foreach($ordDtl as $order_detail){				
				$cart = array();
				if ($order_detail['Product_Code'] != 'Shipping' && $order_detail['Product_Code'] != 'Tax'){
					$prdSrc = $this->selectTable(self::ABC_PRO, array(
							'folder' => $this->_folder,
							'id' => $order_detail['Product_ID'],
					));
					$product_name = '';
					if ($prdSrc){
						$product_name = $prdSrc[0]['name'];
					}
					$product_id = $this->_getLeCaIpImportIdDescByValue(self::TYPE_PRODUCT, $order_detail['Product_Code']);
					if($product_id){
						$cart['product_id'] = $product_id;
					}
					$cart['type_id'] = Mage_Catalog_Model_Product_Type::TYPE_SIMPLE;
					$cart['name'] = $product_name;
					$cart['sku'] = $this->_clearCharPrice($order_detail['Product_Code']);
					$cart['price'] = $this->_clearCharPrice($order_detail['Unit_Price']);
					$cart['original_price'] = $this->_clearCharPrice($order_detail['Unit_Price']);
					$cart['qty_ordered'] = $order_detail['Quantity'];
					$cart['row_total'] = $this->_clearCharPrice($order_detail['Unit_Price']) * $order_detail['Quantity'];
					$ordItemOptionSrc = $this->readQuery("SELECT * FROM " . self::ABC_ORD_ITEM_OPT . " WHERE folder = '{$this->_folder}' AND Order_ID = {$order['Order_ID']} AND Product_Code = '{$order_detail['Product_Code']}'");
					$ordItemOption = ($ordItemOptionSrc['result'] == 'success') ? $ordItemOptionSrc['data'] : array();
					if($ordItemOption){
						$product_opt = array();					
						foreach($ordItemOption as $key => $option){
							$optVal = str_replace(':', '', $option['Option_Name']);
							$opt_data = array(
									'label' => $optVal,
									'value' => $option['Option_Value'],
									'print_value' => $option['Option_Value'],
									'option_id' => 'option_' . $key,
									'option_type' => 'drop_down',
									'option_value' => 0,
									'custom_view' => false
							);
							$product_opt[] = $opt_data;
						}
						$cart['product_options'] = serialize(array('options' => $product_opt));
					}
					$carts[]= $cart;
				}				
			}
		}
		
		$customer_id = $this->getIdDescCustomer($order['Order_ID']);		
		$order_status_id = 'complete';		
		$tax_amount = $this->_clearCharPrice($order['Tax_Charge']);
		$discount_amount = $order['Promotion_Discount'] ? $this->_clearCharPrice($order['Promotion_Discount']) : 0;
		$store_id = $this->_notice['config']['languages'][1];
		$store_currency = $this->getStoreCurrencyCode($store_id);
		$ship_amount = $this->_clearCharPrice($order['Shipping_Charge']);
		$sub_total = $this->_clearCharPrice($order['Total']) - $tax_amount + $discount_amount - $ship_amount;
		
		$order_data = array();
		$order_data['store_id'] = $store_id;
		if($customer_id){
			$order_data['customer_id'] = $customer_id;
			$order_data['customer_is_guest'] = false;
		} else {
			$order_data['customer_is_guest'] = true;
		}
		$order_data['customer_email'] = $order['Email'];
		$order_data['customer_firstname'] = $order['Ship_Name'];
		$order_data['customer_group_id'] = 1;
		$order_data['status'] = $order_status_id;
		$order_data['state'] = $this->getOrderStateByStatus($order_status_id);
		$order_data['subtotal'] = $sub_total;
		$order_data['base_subtotal'] =  $order_data['subtotal'];
		$order_data['shipping_amount'] = $ship_amount;
		$order_data['base_shipping_amount'] = $ship_amount;
		$order_data['base_shipping_invoiced'] = $ship_amount;
		$order_data['shipping_description'] = "Shipping";
		$order_data['tax_amount'] = $tax_amount;
		$order_data['base_tax_amount'] = $tax_amount;
		$order_data['discount_amount'] = $discount_amount;
		$order_data['base_discount_amount'] = $discount_amount;
		$order_data['grand_total'] = $this->_clearCharPrice($order['Total']);
		$order_data['base_grand_total'] = $order_data['grand_total'];
		$order_data['base_total_invoiced'] = $order_data['grand_total'];
		$order_data['total_paid'] = $order_data['grand_total'];
		$order_data['base_total_paid'] = $order_data['grand_total'];
		$order_data['base_to_global_rate'] = true;
		$order_data['base_to_order_rate'] = true;
		$order_data['store_to_base_rate'] = true;
		$order_data['store_to_order_rate'] = true;
		$order_data['base_currency_code'] = $store_currency['base'];
		$order_data['global_currency_code'] = $store_currency['base'];
		$order_data['store_currency_code'] = $store_currency['base'];
		$order_data['order_currency_code'] = $store_currency['base'];
		$order_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['Date']));
		
		$data['address_billing'] = $address_billing;
		$data['address_shipping'] = $address_shipping;
		$data['order'] = $order_data;
		$data['carts'] = $carts;
		$data['order_src_id'] = $order['Order_ID'];
		$custom = $this->_custom->convertOrderCustom($this, $order);
		if($custom){
			$data = array_merge($data, $custom);
		}
		return array(
				'result' => 'success',
				'data' => $data
		);		
	}
	
	public function afterSaveOrder($order_mage_id, $data, $order){
		if(parent::afterSaveOrder($order_mage_id, $data, $order)){
			return ;
		}
		$order_status_data = array();
		$order_status_id = 'complete';
		$order_status_data['status'] = $order_status_id;
		$order_status_data['state'] = $this->getOrderStateByStatus($order_status_id);
		$order_status_data['comment'] = "<b>Reference order #".$order['Order_ID']."</b><br />";
		$order_status_data['comment'] .= "<b>Payment method Id: </b>".$order['Payment_Method']."<br />";
		$order_status_data['comment'] .= "<b>Shipping method Id: </b> ".$order['Shipping']."<br />";
		$order_status_data['comment'] .= "<b>Order Notes: </b>".$order['Comments'];
		$order_status_data['is_customer_notified'] = 1;
		$order_status_data['updated_at'] = date('Y-m-d H:i:s', strtotime($order['Date']));
		$order_status_data['created_at'] = date('Y-m-d H:i:s', strtotime($order['Date']));
		$this->_process->ordersComment($order_mage_id, $order_status_data);
	}
	
	protected function _convertProduct($product){
	    $pro_data = array();
		$pro_data['website_ids'] = $this->_notice['extend']['website_ids'];
		$pro_data['store_ids'] = array_values($this->_notice['config']['languages']);
		$pro_data['attribute_set_id'] = $this->_notice['config']['attribute_set_id'];
		$pro_data['sku'] = $product['code'];
		$pro_data['name'] = $product['name'];		
		$pro_data['weight'] = $product['ship_weight'];
		$pro_data['status'] = 1;
		$pro_data['created_at'] = date('Y-m-d H:i:s');
		$pro_data['updated_at'] = date('Y-m-d H:i:s');
		$qty = $manage_stock = 0;
		$pro_data['stock_data'] = array(
				'is_in_stock' => 1,
				'manage_stock' => $manage_stock,
				'use_config_manage_stock' => 0,
				'qty' => $qty,
		);
		$pro_data['price'] = $product['price'] ? $product['price'] : 0;
		if($this->_seo){
		    $seo = $this->_seo->convertProductSeo($this, $product);
		    if($seo){
		        $pro_data['seo_url'] = $seo;
		    }
		}
		$custom = $this->_custom->convertProductCustom($this, $product);
		if ($custom) {
			$pro_data = array_merge($pro_data, $custom);
		}
		return array(
				'result' => 'success',
				'data' => $pro_data
		);
	}
	
	protected function _setupStorageCsv(){
		$custom_setup = $this->_custom->storageCsvCustom($this);
        if($custom_setup && $custom_setup['result'] == 'error'){
            return $custom_setup;
        }
        $setup = true;
        $tableDrop = $this->getListTableDrop();
        foreach ($tableDrop as $table_drop) {
            $this->dropTable($table_drop);
        }
        $tables = $queries = array();
        $creates = array(
            'productsTableConstruct',
        	'ordersTableConstruct',
        	'orderItemsTableConstruct',
        	'orderItemOptionsTableConstruct',
        );
        foreach ($creates as $create) {
            $tables[] = $this->$create();
        }
        foreach ($tables as $table) {
            $table_query = $this->arrayToCreateSql($table);
            if ($table_query['result'] != 'success') {
                $table_query['msg'] = $this->consoleError($table_query['msg']);
                return $table_query;
            }
            $queries[] = $table_query['query'];
        }
        foreach ($queries as $query) {
            if (!$this->writeQuery($query)) {
                $setup = false;
            }
        }
        if ($setup) {
            //
        } else {
            return array(
                'result' => 'error',
                'msg' => $this->consoleError("Could not created table to storage data.")
            );
        }
        $this->_notice['csv_import']['result'] = 'process';
        $this->_notice['csv_import']['function'] = '_clearStorageCsv';
        $this->_notice['csv_import']['msg'] = "";
        return $this->_notice['csv_import'];
	}
	
	public function getListTableDrop(){
		$tables = $this->_getTablesTmp();
		$custom = $this->_custom->getListTableDropCustom($tables);
		$result = $custom ? $custom : $tables;
		return $result;
	}
	
	public function productsTableConstruct(){
		return array(
				'table' => self::ABC_PRO,
				'rows' => array(
						'product_id' => 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY',
						'folder' => 'VARCHAR(255)',
						'domain' => 'VARCHAR(255)',
						'id' => 'TEXT',
						'name' => 'TEXT',
						'code' => 'TEXT',
						'price' => 'TEXT',
						'sale_price' => 'TEXT',
						'orderable' => 'TEXT',
						'ship_weight' => 'TEXT',
						'taxable' => 'TEXT',
				),
				'validation' => array('code')
		);
	}
	
	public function ordersTableConstruct(){
		return array(
				'table' => self::ABC_ORD,
				'rows' => array(
						'folder' => 'VARCHAR(255)',
						'domain' => 'VARCHAR(255)',
						'Order_ID' => 'VARCHAR(255)',
						'Date' => 'TEXT',
						'Ship_Name' => 'TEXT',
						'Ship_Address_1' => 'TEXT',
						'Ship_Address_2' => 'TEXT',
						'Ship_City' => 'TEXT',
						'Ship_State' => 'TEXT',
						'Ship_Country' => 'TEXT',
						'Ship_Zip' => 'TEXT',
						'Ship_Phone' => 'TEXT',
						'Bill_Name' => 'TEXT',
						'Bill_Address_1' => 'TEXT',
						'Bill_Address_2' => 'TEXT',
						'Bill_City' => 'TEXT',
						'Bill_State' => 'TEXT',
						'Bill_Country' => 'TEXT',
						'Bill_Zip' => 'TEXT',
						'Bill_Phone' => 'TEXT',
						'Email' => 'TEXT',
						'Referring_Page' => 'TEXT',
						'Entry_Point' => 'TEXT',
						'Shipping' => 'TEXT',
						'Payment_Method' => 'TEXT',
						'Card_Number' => 'TEXT',
						'Comments' => 'TEXT',
						'Total' => 'TEXT',
						'Auth_Code' => 'TEXT',
						'AVS_Code' => 'TEXT',
						'CVV_Code' => 'TEXT',
						'PayPal_Auth' => 'TEXT',
						'PayPal_TxID' => 'TEXT',
						'PayPal_Merchant_Email' => 'TEXT',
						'PayPal_Payer_Status' => 'TEXT',
						'PayPal_Address_Status' => 'TEXT',
						'PayPal_Seller_Protection' => 'TEXT',
						'Ship_Company' => 'TEXT',
						'Bill_Company' => 'TEXT',
						'Tax_Charge' => 'TEXT',
						'Shipping_Charge' => 'TEXT',
						'Promotion_Discount' => 'TEXT',
						'Promotion_ID' => 'TEXT',
						'Promotion_Type' => 'TEXT',						
				),
				'validation' => array('Order_ID')
		);
	}
	
	public function orderItemsTableConstruct(){
		return array(
				'table' => self::ABC_ORD_ITEM,
				'rows' => array(
						'folder' => 'VARCHAR(255)',
						'domain' => 'VARCHAR(255)',
						'Order_ID' => 'VARCHAR(255)',
						'Line_ID' => 'TEXT',
						'Product_ID' => 'TEXT',
						'Product_Code' => 'TEXT',
						'Quantity' => 'TEXT',
						'Unit_Price' => 'TEXT',
				),
				'validation' => array('Order_ID', 'Line_ID', 'Product_ID')
		);
	}
	
	public function orderItemOptionsTableConstruct(){
		return array(
				'table' => self::ABC_ORD_ITEM_OPT,
				'rows' => array(
						'folder' => 'VARCHAR(255)',
						'domain' => 'VARCHAR(255)',
						'Order_ID' => 'VARCHAR(255)',
						'Line_ID' => 'TEXT',
						'Product_ID' => 'TEXT',
						'Product_Code' => 'TEXT',
						'Option_Name' => 'TEXT',
						'Option_Value' => 'TEXT',
				),
				'validation' => array('Order_ID', 'Line_ID', 'Product_ID')
		);
	}
	
	protected function _clearStorageCsv(){
		$tables = $this->_getTablesTmp();
		$folder = $this->_folder;
		foreach($tables as $table){
			$table_name = $this->getTableName($table);
			$query = "DELETE FROM {$table_name} WHERE folder = '{$folder}'";
			$this->writeQuery($query);
		}
		$this->_notice['csv_import']['function'] = '_storageCsvProducts';
		return array(
				'result' => 'process',
				'msg' => ''
		);
	}
	
	protected function _storageCsvProducts(){
		return $this->_storageCsvByType('products', 'orders', false, false, array('product_id'));
	}
	
	protected function _storageCsvOrders(){
		return $this->_storageCsvByType('orders', 'orderItems');
	}
	
	protected function _storageCsvOrderItems(){
		return $this->_storageCsvByType('orderItems', 'orderItemOptions');
	}
	
	protected function _storageCsvOrderItemOptions(){
		return $this->_storageCsvByType('orderItemOptions', 'orderItemOptions', false, true);
	}
	
	protected function _getLeCaIpImportIdDescByValue($type, $value){
		$result = $this->selectTableRow(self::TABLE_IMPORT, array(
				'folder' => $this->_folder,
				'type' => $type,
				'value' => $value
		));
		if(!$result){
			return false;
		}
		return (isset($result['id_desc'])) ? $result['id_desc'] : false;
	}
	
	protected function _getTablesTmp(){
		return array(
				self::ABC_PRO,				
				self::ABC_ORD,
				self::ABC_ORD_ITEM,
				self::ABC_ORD_ITEM_OPT,
		);
	}
	
	protected function _clearCharPrice($price){
		return str_replace('$', '', $price);
	}
}