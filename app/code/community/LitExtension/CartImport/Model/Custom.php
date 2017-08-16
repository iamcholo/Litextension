<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartImport_Model_Custom{

    const FOLDER_UPLOAD = false;
    const CLEAR_IMPORT = true;
    const DEMO_MODE = false;
    const DEV_MODE = false;
    const PRODUCT_REPORT = false;
    const PRODUCT_BESTSELLER = false;
    const CSV_STORAGE = false;
    const CUSTOM_SETUP = 0;
    const CSV_IMPORT = false;
    const TAX_CONVERT = false;
    const TAX_IMPORT = false;
    const TAX_AFTER_SAVE = false;
    const MANUFACTURER_CONVERT = false;
    const MANUFACTURER_IMPORT = false;
    const MANUFACTURER_AFTER_SAVE = false;
    const CATEGORY_CONVERT = false;
    const CATEGORY_IMPORT = false;
    const CATEGORY_AFTER_SAVE = false;
    const PRODUCT_CONVERT = false;
    const PRODUCT_IMPORT = false;
    const PRODUCT_AFTER_SAVE = false;
    const CUSTOMER_CONVERT = false;
    const CUSTOMER_IMPORT = false;
    const CUSTOMER_AFTER_SAVE = false;
    const ORDER_CONVERT = false;
    const ORDER_IMPORT = false;
    const ORDER_AFTER_SAVE = false;
    const REVIEW_CONVERT = false;
    const REVIEW_IMPORT = false;
    const REVIEW_AFTER_SAVE = false;

    public function storageCsvCustom($cart){
        return false;
    }

    public function getListTableDropCustom($tables){
        return false;
    }

    public function prepareImportTaxesCustom($cart){
        return false;
    }

    public function convertTaxCustom($cart, $tax){
        return false;
    }

    public function importTaxCustom($cart, $data, $tax){
        return false;
    }

    public function afterSaveTaxCustom($cart, $tax_id_desc, $convert, $tax){
        return false;
    }

    public function prepareImportManufacturersCustom($cart){
        return false;
    }

    public function convertManufacturerCustom($cart, $manufacturer){
        return false;
    }

    public function importManufacturerCustom($cart, $data, $manufacturer){
        return false;
    }

    public function afterSaveManufacturerCustom($cart, $manufacturer_id_desc, $convert, $manufacturer){
        return false;
    }

    public function prepareImportCategoriesCustom($cart){
        return false;
    }

    public function convertCategoryCustom($cart, $category){
        return false;
    }

    public function importCategoryCustom($cart, $data, $category){
        return false;
    }

    public function afterSaveCategoryCustom($cart, $category_id_desc, $convert, $category){
        return false;
    }

    public function prepareImportProductsCustom($cart){
        return false;
    }

    public function convertProductCustom($cart, $product){
        return false;
    }

    public function importProductCustom($cart, $data, $product){
        return false;
    }

    public function afterSaveProductCustom($cart, $product_id_desc, $convert, $product){
        return false;
    }

    public function prepareImportCustomersCustom($cart){
        return false;
    }

    public function convertCustomerCustom($cart, $customer){
        return false;
    }

    public function importCustomerCustom($cart, $data, $customer){
        return false;
    }

    public function afterSaveCustomerCustom($cart, $customer_id_desc, $convert, $customer){
        return false;
    }

    public function prepareImportOrdersCustom($cart){
        return false;
    }

    public function convertOrderCustom($cart, $order){
        return false;
    }

    public function importOrderCustom($cart, $data, $order){
        return false;
    }

    public function afterSaveOrderCustom($cart, $order_id_desc, $convert, $order){
        return false;
    }

    public function prepareImportReviewsCustom($cart){
        return false;
    }

    public function convertReviewCustom($cart, $review){
        return false;
    }

    public function importReviewCustom($cart, $data, $review){
        return false;
    }

    public function afterSaveReviewCustom($cart, $review_id_desc, $convert, $review){
        return false;
    }
}