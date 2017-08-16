<?php 

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$this->startSetup();
$this->run("
              
-- DROP TABLE IF EXISTS {$this->getTable('rotatingimageslider/rotatingimageslider')};
CREATE TABLE {$this->getTable('rotatingimageslider/rotatingimageslider')} (
    `rotatingimageslider_id` INT(11) unsigned NOT NULL AUTO_INCREMENT ,
    `name` VARCHAR( 255 ) ,
    `image` VARCHAR( 255 ) ,
    `filethumbgrid` VARCHAR( 255 ) ,
    `group_id` INT(6),
    `groupname` VARCHAR( 255 ) ,
    `link` VARCHAR( 255 ) ,
    `status` INT  ,
    `created_at` DATETIME  ,
    `updated_at` DATETIME ,
    PRIMARY KEY ( `rotatingimageslider_id` ) 
)ENGINE=InnoDB DEFAULT CHARSET=utf8;   
        
-- DROP TABLE IF EXISTS {$this->getTable('rotatingimageslider/rotatingimageslider_store')};
CREATE TABLE {$this->getTable('rotatingimageslider/rotatingimageslider_store')} (
    `rotatingimageslider_id` int(11) unsigned NOT NULL default '0',
    `store_id` smallint(6) unsigned NOT NULL default '0',
    INDEX (`rotatingimageslider_id`,`store_id`),
    CONSTRAINT `FK_LE_RIS_ROTATINGIMAGESLIDER_ID`
        FOREIGN KEY (`rotatingimageslider_id`) 
            REFERENCES `{$this->getTable('rotatingimageslider/rotatingimageslider')}` (`rotatingimageslider_id`) 
            ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_LE_RIS_STORE_ID`
        FOREIGN KEY (`store_id`) 
            REFERENCES `{$this->getTable('core/store')}` (`store_id`) 
            ON UPDATE CASCADE ON DELETE CASCADE     
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
       
    ");
$this->endSetup();