<?php
/**
 * @project: CartServiceMigrate
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */
$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('lecsmg/import')}`(
        `domain` varchar(255),
        `type`  varchar(255),
        `id_src` bigint(20),
        `id_desc` int(11),
        `status` int(5),
        `value` text,
        INDEX (`domain`, `type`, `id_src`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$this->getTable('lecsmg/user')}`(
        `user_id` INT(11) UNIQUE NOT NULL,
        `notice`  TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$this->getTable('lecsmg/recent')}`(
        `domain` VARCHAR(255) UNIQUE NOT NULL,
        `notice`  TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();