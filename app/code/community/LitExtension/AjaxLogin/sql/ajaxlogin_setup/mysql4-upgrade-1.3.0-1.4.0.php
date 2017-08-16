<?php
/**
 * @project     AjaxLogin
 * @package     LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$this->startSetup();
$this->run("
-- DROP TABLE IF EXISTS {$this->getTable('ajaxlogin/attributes')};
CREATE TABLE {$this->getTable('ajaxlogin/attributes')} (
  `ajaxlogin_id` int(11) NOT NULL auto_increment,
  `attribute_id` int(11) unsigned NOT NULL,
  `section` int(11),
  `show_on_customer_grid` int(11),
  PRIMARY KEY (`ajaxlogin_id`),
  CONSTRAINT `FK_AJAXLOGIN_ATTRIBUTE` FOREIGN KEY (`attribute_id`)
  REFERENCES `{$this->getTable('eav_attribute')}` (`attribute_id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$this->endSetup();