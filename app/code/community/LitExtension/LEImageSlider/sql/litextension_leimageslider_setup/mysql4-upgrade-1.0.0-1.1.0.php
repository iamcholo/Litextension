<?php
/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$this->startSetup();

$this->run("
     ALTER TABLE {$this->getTable('leimageslider/leimageslider_group')} CHANGE `width` `width` VARCHAR(255) ;
     ALTER TABLE {$this->getTable('leimageslider/leimageslider_group')} DROP `height` ;
");

$this->endSetup();