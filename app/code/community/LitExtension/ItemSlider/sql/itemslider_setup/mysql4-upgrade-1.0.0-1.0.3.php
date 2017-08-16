<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$this->startSetup();

$this->run("
     ALTER TABLE {$this->getTable('itemslider/itemslider')} ADD `tabs_order` INT(6) NOT NULL DEFAULT '0' ;
");

$this->endSetup();