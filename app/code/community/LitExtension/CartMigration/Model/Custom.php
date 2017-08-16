<?php
/**
 * @project: CartMigration
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_CartMigration_Model_Custom{

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
    const CART_CONVERT = false;
    const CART_IMPORT = false;
    const CART_AFTER_SAVE = false;
    const CART_ADDITION = false;
    const REVIEW_CONVERT = false;
    const REVIEW_IMPORT = false;
    const REVIEW_AFTER_SAVE = false;
    const REVIEW_ADDITION = false;
    const PAGE_CONVERT = false;
    const PAGE_IMPORT = false;
    const PAGE_AFTER_SAVE = false;
    const PAGE_ADDITION = false;
    const BLOCK_CONVERT = false;
    const BLOCK_IMPORT = false;
    const BLOCK_AFTER_SAVE = false;
    const BLOCK_ADDITION = false;
    const WIDGET_CONVERT = false;
    const WIDGET_IMPORT = false;
    const WIDGET_AFTER_SAVE = false;
    const WIDGET_ADDITION = false;
    const POLL_CONVERT = false;
    const POLL_IMPORT = false;
    const POLL_AFTER_SAVE = false;
    const POLL_ADDITION = false;
    const TRANSACTION_CONVERT = false;
    const TRANSACTION_IMPORT = false;
    const TRANSACTION_AFTER_SAVE = false;
    const TRANSACTION_ADDITION = false;
    const NEWSLETTER_CONVERT = false;
    const NEWSLETTER_IMPORT = false;
    const NEWSLETTER_AFTER_SAVE = false;
    const NEWSLETTER_ADDITION = false;
    const USER_CONVERT = false;
    const USER_IMPORT = false;
    const USER_AFTER_SAVE = false;
    const USER_ADDITION = false;
    const RULE_CONVERT = false;
    const RULE_IMPORT = false;
    const RULE_AFTER_SAVE = false;
    const RULE_ADDITION = false;
    const CARTRULE_CONVERT = false;
    const CARTRULE_IMPORT = false;
    const CARTRULE_AFTER_SAVE = false;
    const CARTRULE_ADDITION = false;

    public function prepareImportTaxesCustom($cart){
        return false;
    }

    public function getTaxesExtQueryCustom($cart, $taxes){
        return false;
    }

    public function getTaxesExtRelQueryCustom($cart, $taxes, $taxesExt){
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

    public function getManufacturersExtQueryCustom($cart, $manufacturers){
        return false;
    }

    public function getManufacturersExtRelQueryCustom($cart, $manufacturers, $manufacturersExt){
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

    public function getCategoriesExtQueryCustom($cart, $categories){
        return false;
    }

    public function getCategoriesExtRelQueryCustom($cart, $categories, $categoriesExt){
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

    public function getProductsExtQueryCustom($cart, $products){
        return false;
    }

    public function getProductsExtRelQueryCustom($cart, $products, $productsExt){
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

    public function getCustomersExtQueryCustom($cart, $customers){
        return false;
    }

    public function getCustomerExtRelQueryCustom($cart, $customers, $customersExt){
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

    public function getOrdersExtQueryCustom($cart, $orders){
        return false;
    }

    public function getOrdersExtRelQueryCustom($cart, $orders, $ordersExt){
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
    
    public function prepareImportCartsCustom($cart){
        return false;
    }

    public function getCartsExtQueryCustom($cart, $orders){
        return false;
    }

    public function getCartsExtRelQueryCustom($cart, $orders, $ordersExt){
        return false;
    }

    public function convertCartCustom($cart, $order, $ordersExt){
        return false;
    }

    public function importCartCustom($cart, $data, $order, $ordersExt){
        return false;
    }

    public function afterSaveCartCustom($cart, $order_id_desc, $convert, $order, $ordersExt){
        return false;
    }

    public function additionCartCustom($cart, $convert, $order, $ordersExt){
        return false;
    }

    public function prepareImportReviewsCustom($cart){
        return false;
    }

    public function getReviewsExtQueryCustom($cart, $reviews){
        return false;
    }

    public function getReviewsExtRelQueryCustom($cart, $reviews, $reviewsExt){
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

    public function prepareImportPagesCustom($cart){
        return false;
    }

    public function getPagesExtQueryCustom($cart, $pages){
        return false;
    }

    public function getPagesExtRelQueryCustom($cart, $pages, $pagesExt){
        return false;
    }

    public function convertPageCustom($cart, $page, $pagesExt){
        return false;
    }

    public function importPageCustom($cart, $data, $page, $pagesExt){
        return false;
    }

    public function afterSavePageCustom($cart, $page_id_desc, $convert, $page, $pagesExt){
        return false;
    }

    public function additionPageCustom($cart, $convert, $page, $pagesExt){
        return false;
    }

    public function prepareImportBlocksCustom($cart){
        return false;
    }

    public function getBlocksExtQueryCustom($cart, $blocks){
        return false;
    }

    public function getBlocksExtRelQueryCustom($cart, $blocks, $blocksExt){
        return false;
    }

    public function convertBlockCustom($cart, $block, $blocksExt){
        return false;
    }

    public function importBlockCustom($cart, $data, $block, $blocksExt){
        return false;
    }

    public function afterSaveBlockCustom($cart, $block_id_desc, $convert, $block, $blocksExt){
        return false;
    }

    public function additionBlockCustom($cart, $convert, $block, $blocksExt){
        return false;
    }

    public function prepareImportWidgetsCustom($cart){
        return false;
    }

    public function getWidgetsExtQueryCustom($cart, $widgets){
        return false;
    }

    public function getWidgetsExtRelQueryCustom($cart, $widgets, $widgetsExt){
        return false;
    }

    public function convertWidgetCustom($cart, $widget, $widgetsExt){
        return false;
    }

    public function importWidgetCustom($cart, $data, $widget, $widgetsExt){
        return false;
    }

    public function afterSaveWidgetCustom($cart, $widget_id_desc, $convert, $widget, $widgetsExt){
        return false;
    }

    public function additionWidgetCustom($cart, $convert, $widget, $widgetsExt){
        return false;
    }

    public function prepareImportPollsCustom($cart){
        return false;
    }

    public function getPollsExtQueryCustom($cart, $polls){
        return false;
    }

    public function getPollsExtRelQueryCustom($cart, $polls, $pollsExt){
        return false;
    }

    public function convertPollCustom($cart, $poll, $pollsExt){
        return false;
    }

    public function importPollCustom($cart, $data, $poll, $pollsExt){
        return false;
    }

    public function afterSavePollCustom($cart, $poll_id_desc, $convert, $poll, $pollsExt){
        return false;
    }

    public function additionPollCustom($cart, $convert, $poll, $pollsExt){
        return false;
    }

    public function prepareImportTransactionsCustom($cart){
        return false;
    }

    public function getTransactionsExtQueryCustom($cart, $transactions){
        return false;
    }

    public function getTransactionsExtRelQueryCustom($cart, $transactions, $transactionsExt){
        return false;
    }

    public function convertTransactionCustom($cart, $transaction, $transactionsExt){
        return false;
    }

    public function importTransactionCustom($cart, $data, $transaction, $transactionsExt){
        return false;
    }

    public function afterSaveTransactionCustom($cart, $transaction_id_desc, $convert, $transaction, $transactionsExt){
        return false;
    }

    public function additionTransactionCustom($cart, $convert, $transaction, $transactionsExt){
        return false;
    }

    public function prepareImportNewslettersCustom($cart){
        return false;
    }

    public function getNewslettersExtQueryCustom($cart, $newsletters){
        return false;
    }

    public function getNewslettersExtRelQueryCustom($cart, $newsletters, $newslettersExt){
        return false;
    }

    public function convertNewsletterCustom($cart, $newsletter, $newslettersExt){
        return false;
    }

    public function importNewsletterCustom($cart, $data, $newsletter, $newslettersExt){
        return false;
    }

    public function afterSaveNewsletterCustom($cart, $newsletter_id_desc, $convert, $newsletter, $newslettersExt){
        return false;
    }

    public function additionNewsletterCustom($cart, $convert, $newsletter, $newslettersExt){
        return false;
    }

    public function prepareImportUsersCustom($cart){
        return false;
    }

    public function getUsersExtQueryCustom($cart, $users){
        return false;
    }

    public function getUsersExtRelQueryCustom($cart, $users, $usersExt){
        return false;
    }

    public function convertUserCustom($cart, $user, $usersExt){
        return false;
    }

    public function importUserCustom($cart, $data, $user, $usersExt){
        return false;
    }

    public function afterSaveUserCustom($cart, $user_id_desc, $convert, $user, $usersExt){
        return false;
    }

    public function additionUserCustom($cart, $convert, $user, $usersExt){
        return false;
    }

    public function prepareImportRulesCustom($cart){
        return false;
    }

    public function getRulesExtQueryCustom($cart, $rules){
        return false;
    }

    public function getRulesExtRelQueryCustom($cart, $rules, $rulesExt){
        return false;
    }

    public function convertRuleCustom($cart, $rule, $rulesExt){
        return false;
    }

    public function importRuleCustom($cart, $data, $rule, $rulesExt){
        return false;
    }

    public function afterSaveRuleCustom($cart, $rule_id_desc, $convert, $rule, $rulesExt){
        return false;
    }

    public function additionRuleCustom($cart, $convert, $rule, $rulesExt){
        return false;
    }

    public function prepareImportCartrulesCustom($cart){
        return false;
    }

    public function getCartrulesExtQueryCustom($cart, $rules){
        return false;
    }

    public function getCartrulesExtRelQueryCustom($cart, $rules, $rulesExt){
        return false;
    }

    public function convertCartruleCustom($cart, $rule, $rulesExt){
        return false;
    }

    public function importCartruleCustom($cart, $data, $rule, $rulesExt){
        return false;
    }

    public function afterSaveCartruleCustom($cart, $rule_id_desc, $convert, $rule, $rulesExt){
        return false;
    }

    public function additionCartruleCustom($cart, $convert, $rule, $rulesExt){
        return false;
    }
}