<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

$this->startSetup();
$this->run("
-- DROP TABLE IF EXISTS {$this->getTable('itemslider/slides')};
CREATE TABLE {$this->getTable('itemslider/slides')} (
    `slide_id` int(11) unsigned NOT NULL AUTO_INCREMENT ,
    `slide_name` varchar( 255 ) ,
    `status` int  ,
    `created_at` datetime  ,
    `updated_at` datetime ,
PRIMARY KEY ( `slide_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('itemslider/itemslider')};
CREATE TABLE {$this->getTable('itemslider/itemslider')} (
    `entity_id` int(11) unsigned NOT NULL AUTO_INCREMENT ,
    `slide_id` int(11) unsigned NOT NULL ,
    `group_name` varchar( 255 ) ,
    `item_type` int ,
    `item_ids` varchar( 255 ) ,
    `enable_link` int  ,
    `status` int  ,
    `created_at` datetime  ,
    `updated_at` datetime ,
PRIMARY KEY ( `entity_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->endSetup();