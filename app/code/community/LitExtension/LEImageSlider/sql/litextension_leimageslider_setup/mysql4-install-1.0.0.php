<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$this->startSetup();

$this->run("

-- DROP TABLE IF EXISTS {$this->getTable('leimageslider/leimageslider_group')};
CREATE TABLE {$this->getTable('leimageslider/leimageslider_group')} (
    `leimageslider_group_id` int(11) unsigned NOT NULL AUTO_INCREMENT ,
    `title` varchar( 255 ) ,
    `description` text  ,
    `autoplay` int(6),
    `width` int,
    `height` int,
    `dirnav` int,
    `controlnav` int,
    `pausehorver` int,
    `pretext` varchar( 255 ) ,
    `nexttext` varchar( 255 ) ,
    `textsize` int,
    `textcolor` varchar( 255 ) ,
    `textmargin` int,
    `bgcolor` varchar( 255 ) ,
    `bgtransparency` float,
    `theme` varchar( 255 ) ,
    `effect` varchar( 255 ) ,
    `animspeed` int,
    `pausetime` int,
    `startslide` int,
    `status` int  ,
    `created_at` datetime  ,
    `updated_at` datetime ,
    PRIMARY KEY ( `leimageslider_group_id` ) 
)ENGINE=InnoDB DEFAULT CHARSET=utf8;   
        
-- DROP TABLE IF EXISTS {$this->getTable('leimageslider/leimageslider_group_store')};
CREATE TABLE {$this->getTable('leimageslider/leimageslider_group_store')} (
    `leimageslider_group_id` int(11) unsigned NOT NULL default '0',
    `store_id` smallint(6) unsigned NOT NULL default '0',
    CONSTRAINT `FK_LE_IS_LEIMAGESLIDER_GROUP_ID`
        FOREIGN KEY (`leimageslider_group_id`) 
            REFERENCES `{$this->getTable('leimageslider/leimageslider_group')}` (`leimageslider_group_id`) 
            ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `FK_LE_IS_STORE_ID`
        FOREIGN KEY (`store_id`) 
            REFERENCES `{$this->getTable('core/store')}` (`store_id`) 
            ON UPDATE CASCADE ON DELETE CASCADE 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('leimageslider/leimageslider')};
CREATE TABLE {$this->getTable('leimageslider/leimageslider')} (
    `leimageslider_id` int(11) unsigned NOT NULL AUTO_INCREMENT ,
    `title` varchar( 255 ) ,
    `content` text  ,
    `link` varchar( 255 ) ,
    `image` varchar(255),
    `filethumbgrid` varchar(255),
    `group_id` int(11)  unsigned NOT NULL,
    `status` int  ,
    `created_at` datetime  ,
    `updated_at` datetime ,
    PRIMARY KEY ( `leimageslider_id` ) ,
    CONSTRAINT `FK_LE_IS_LEIMAGESLIDER_ID`
        FOREIGN KEY (`group_id`)
            REFERENCES `{$this->getTable('leimageslider/leimageslider_group')}` (`leimageslider_group_id`)
            ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
       
        INSERT INTO {$this->getTable('leimageslider/leimageslider_group')} (leimageslider_group_id, title, description, autoplay, width, height, dirnav, controlnav, pausehorver, pretext, nexttext, textsize, textcolor, bgcolor, bgtransparency, theme, effect, textmargin, animspeed, pausetime, startslide, status, created_at, updated_at)
                                                                    VALUES ( null,                'default', 'This is default group ',         1,      675,    350,    1,          1,       1,          'Prev', 'Next',      12 ,   'FFFFFF', '000000',     0.8,      'default', 'random',  5,     500,        3000,       0,         1, now(), now() );
        INSERT INTO {$this->getTable('leimageslider/leimageslider_group_store')} (leimageslider_group_id , store_id) VALUES (1, 0);
    ");

$this->endSetup();