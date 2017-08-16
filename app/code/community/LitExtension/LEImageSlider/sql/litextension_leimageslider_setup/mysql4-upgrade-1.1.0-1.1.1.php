<?php
/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$this->startSetup();

$this->run("
     ALTER TABLE {$this->getTable('leimageslider/leimageslider')} ADD `target` INT(5) ;
");

$this->endSetup();