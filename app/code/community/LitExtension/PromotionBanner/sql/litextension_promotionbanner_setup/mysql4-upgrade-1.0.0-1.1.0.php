<?php

/**
 * @project     PromotionBanner
 * @package	    LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE {$installer->getTable('promotionbanner/promotionbanner')} ADD (`width_type` INT(5) NOT NULL DEFAULT 0, `height_type` INT(5) NOT NULL DEFAULT 0, `mobile_show` INT(5) NOT NULL DEFAULT 0);
");

$installer->endSetup();