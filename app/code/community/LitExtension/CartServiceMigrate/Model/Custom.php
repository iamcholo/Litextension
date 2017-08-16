<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartServiceMigrate_Model_Custom{

    const DEMO_MODE = false;
    const DEV_MODE = false;
    const PRODUCT_REPORT = false;
    const PRODUCT_BESTSELLER = false;
    const TAX_CONVERT = false;
    const TAX_IMPORT = false;
    const TAX_AFTER_SAVE = false;
    const TAX_ADDITION = false;
    const MANUFACTURER_CONVERT = false;
    const MANUFACTURER_IMPORT = false;
    const MANUFACTURER_AFTER_SAVE = false;
    const MANUFACTURER_ADDITION = false;
    const CATEGORY_CONVERT = false;
    const CATEGORY_IMPORT = false;
    const CATEGORY_AFTER_SAVE = false;
    const CATEGORY_ADDITION = false;
    const PRODUCT_CONVERT = false;
    const PRODUCT_IMPORT = false;
    const PRODUCT_AFTER_SAVE = false;
    const PRODUCT_ADDITION = false;
    const CUSTOMER_CONVERT = false;
    const CUSTOMER_IMPORT = false;
    const CUSTOMER_AFTER_SAVE = false;
    const CUSTOMER_ADDITION = false;
    const ORDER_CONVERT = false;
    const ORDER_IMPORT = false;
    const ORDER_AFTER_SAVE = false;
    const ORDER_ADDITION = false;
    const REVIEW_CONVERT = false;
    const REVIEW_IMPORT = false;
    const REVIEW_AFTER_SAVE = false;
    const REVIEW_ADDITION = false;

    public function prepareImportTaxesCustom($cart){
        return false;
    }

    public function getTaxesExtCustom($cart, $taxes, $taxesExt){
        return false;
    }

    public function convertTaxCustom($cart, $tax, $taxExt){
        return false;
    }

    public function importTaxCustom($cart, $data, $tax, $taxesExt){
        return false;
    }

    public function afterSaveTaxCustom($cart, $tax_id_desc, $convert, $tax, $taxesExt){
        return false;
    }

    public function additionTaxCustom($cart, $convert, $tax, $taxesExt){
        return false;
    }

    public function prepareImportManufacturersCustom($cart){
        return false;
    }

    public function getManufacturersExtCustom($cart, $manufacturers, $manufacturersExt){
        return false;
    }

    public function convertManufacturerCustom($cart, $manufacturer, $manufacturersExt){
        return false;
    }

    public function importManufacturerCustom($cart, $data, $manufacturer, $manufacturersExt){
        return false;
    }

    public function afterSaveManufacturerCustom($cart, $manufacturer_id_desc, $convert, $manufacturer, $manufacturersExt){
        return false;
    }

    public function additionManufacturerCustom($cart, $convert, $manufacturer, $manufacturersExt){
        return false;
    }

    public function prepareImportCategoriesCustom($cart){
        return false;
    }

    public function getCategoriesExtCustom($cart, $categories, $categoriesExt){
        return false;
    }

    public function convertCategoryCustom($cart, $category, $categoriesExt){
        return false;
    }

    public function importCategoryCustom($cart, $data, $category, $categoriesExt){
        return false;
    }

    public function afterSaveCategoryCustom($cart, $category_id_desc, $convert, $category, $categoriesExt){
        return false;
    }

    public function additionCategoryCustom($cart, $convert, $category, $categoriesExt){
        return false;
    }

    public function prepareImportProductsCustom($cart){
        return false;
    }

    public function getProductsExtCustom($cart, $products, $productsExt){
        return false;
    }

    public function convertProductCustom($cart, $product, $productsExt){
        return false;
    }

    public function importProductCustom($cart, $data, $product, $productsExt){
        return false;
    }

    public function afterSaveProductCustom($cart, $product_id_desc, $convert, $product, $productsExt){
        return false;
    }

    public function additionProductCustom($cart, $convert, $product, $productsExt){
        return false;
    }

    public function prepareImportCustomersCustom($cart){
        return false;
    }

    public function getCustomerExtCustom($cart, $customers, $customersExt){
        return false;
    }

    public function convertCustomerCustom($cart, $customer, $customersExt){
        return false;
    }

    public function importCustomerCustom($cart, $data, $customer, $customersExt){
        return false;
    }

    public function afterSaveCustomerCustom($cart, $customer_id_desc, $convert, $customer, $customersExt){
        return false;
    }

    public function additionCustomerCustom($cart, $convert, $customer, $customersExt){
        return false;
    }

    public function prepareImportOrdersCustom($cart){
        return false;
    }

    public function getOrdersExtCustom($cart, $orders, $ordersExt){
        return false;
    }

    public function convertOrderCustom($cart, $order, $ordersExt){
        return false;
    }

    public function importOrderCustom($cart, $data, $order, $ordersExt){
        return false;
    }

    public function afterSaveOrderCustom($cart, $order_id_desc, $convert, $order, $ordersExt){
        return false;
    }

    public function additionOrderCustom($cart, $convert, $order, $ordersExt){
        return false;
    }

    public function prepareImportReviewsCustom($cart){
        return false;
    }

    public function getReviewsExtCustom($cart, $reviews, $reviewsExt){
        return false;
    }

    public function convertReviewCustom($cart, $review, $reviewsExt){
        return false;
    }

    public function importReviewCustom($cart, $data, $review, $reviewsExt){
        return false;
    }

    public function afterSaveReviewCustom($cart, $review_id_desc, $convert, $review, $reviewsExt){
        return false;
    }

    public function additionReviewCustom($cart, $convert, $review, $reviewsExt){
        return false;
    }

}