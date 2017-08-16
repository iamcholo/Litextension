<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_SimpleConfigurableProducts_Model_CatalogRule_Mysql4_Rule extends
    Mage_CatalogRule_Model_Mysql4_Rule

{
   public function applyToProduct($rule, $product, $websiteIds)
    {
        if (!$rule->getIsActive()) {
            return $this;
        }

        $ruleId = $rule->getId();
        $productId = $product->getId();

        $write = $this->_getWriteAdapter();
        $write->beginTransaction();

        $write->delete($this->getTable('catalogrule/rule_product'), array(
            $write->quoteInto('rule_id=?', $ruleId),
            $write->quoteInto('product_id=?', $productId),
        ));

        if (!$rule->getConditions()->validate($product)) {
            $write->commit();
            return $this;
        }

        $customerGroupIds = $rule->getCustomerGroupIds();

        $fromTime   = strtotime($rule->getFromDate());
        $toTime     = strtotime($rule->getToDate());
        $toTime     = $toTime ? $toTime+self::SECONDS_IN_DAY-1 : 0;

        $sortOrder      = (int)$rule->getSortOrder();
        $actionOperator = $rule->getSimpleAction();
        $actionAmount   = $rule->getDiscountAmount();
        $actionStop     = $rule->getStopRulesProcessing();

        $rows = array();
        $header = 'replace into '.$this->getTable('catalogrule/rule_product').' (
                rule_id,
                from_time,
                to_time,
                website_id,
                customer_group_id,
                product_id,
                action_operator,
                action_amount,
                action_stop,
                sort_order
            ) values ';
        try {
            foreach ($websiteIds as $websiteId) {
                foreach ($customerGroupIds as $customerGroupId) {
                    $rows[] = "(
                        '$ruleId',
                        '$fromTime',
                        '$toTime',
                        '$websiteId',
                        '$customerGroupId',
                        '$productId',
                        '$actionOperator',
                        '$actionAmount',
                        '$actionStop',
                        '$sortOrder'
                    )";
                    if (sizeof($rows)==100) {
                        $sql = $header.join(',', $rows);
                        $write->query($sql);
                        $rows = array();
                    }
                }
            }

            if (!empty($rows)) {
                $sql = $header.join(',', $rows);
                $write->query($sql);
            }
        } catch (Exception $e) {
            $write->rollback();
            throw $e;

        }
        $this->applyAllRulesForDateRange(null, null, $product);
        $write->commit();
        return $this;
    }
}
