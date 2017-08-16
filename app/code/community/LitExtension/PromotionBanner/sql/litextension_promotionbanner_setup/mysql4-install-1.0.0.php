<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$this->startSetup();
$this->run("
       
-- DROP TABLE IF EXISTS {$this->getTable('promotionbanner/promotionbanner')};
CREATE TABLE {$this->getTable('promotionbanner/promotionbanner')} (
    `promotionbanner_id` INT(11) unsigned NOT NULL AUTO_INCREMENT ,
    `title` VARCHAR( 255 ) NOT NULL DEFAULT '',
    `content` TEXT ,
    `width` VARCHAR( 255 ) NOT NULL DEFAULT '0',
    `height` VARCHAR( 255 ) NOT NULL DEFAULT '0',
    `position` INT(5) NOT NULL DEFAULT '0',
    `bgcolor` VARCHAR( 255 ) NOT NULL DEFAULT ' ',
    `border_width` INT(11) NOT NULL DEFAULT '0',
    `border_color` VARCHAR( 255 ) NOT NULL DEFAULT ' ',
    `shadow_color` VARCHAR( 255 ) NOT NULL DEFAULT ' ',
    `dont_show` INT(5) NOT NULL DEFAULT '0',
    `show_close` INT(5) NOT NULL DEFAULT '0',
    `close_effect` INT(5) ,
    `theme` INT(5),
    `overlay` INT(5) NOT NULL DEFAULT '0',
    `autohide` INT(11) NOT NULL DEFAULT '0',
    `easing_in_id` INT(11) NOT NULL DEFAULT '0',
    `easing_out_id` INT(11) NOT NULL DEFAULT '0',
    `easing_in` VARCHAR( 255 ) NOT NULL DEFAULT ' ',
    `easing_out` VARCHAR( 255 ) NOT NULL DEFAULT ' ',
    `start_date` DATE,
    `end_date` DATE,
    `autohide_effect` INT NULL,
    `show_at` VARCHAR(255),
    `status` INT NOT NULL DEFAULT '0' ,
    `created_at` DATETIME ,
    `updated_at` DATETIME ,
    PRIMARY KEY ( `promotionbanner_id` ) 
)ENGINE=InnoDB DEFAULT CHARSET=utf8;   
        
-- DROP TABLE IF EXISTS {$this->getTable('promotionbanner/promotionbanner_store')};
CREATE TABLE {$this->getTable('promotionbanner/promotionbanner_store')} (
    `promotionbanner_id` int(11) unsigned NOT NULL default '0',
    `store_id` smallint(6) unsigned NOT NULL default '0',
    INDEX (`promotionbanner_id`,`store_id`),
    CONSTRAINT `FK_LE_PB_PROMOTIONBANNER_ID` 
        FOREIGN KEY (`promotionbanner_id`) 
            REFERENCES `{$this->getTable('promotionbanner/promotionbanner')}` (`promotionbanner_id`) 
            ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_LE_PB_STORE_ID` 
        FOREIGN KEY (`store_id`) 
            REFERENCES `{$this->getTable('core/store')}` (`store_id`) 
            ON UPDATE CASCADE ON DELETE CASCADE     
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
       
    ");
$this->endSetup();