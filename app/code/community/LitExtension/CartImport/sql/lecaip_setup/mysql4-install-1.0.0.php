<?php
/**
 * @project: CartImport
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS `{$this->getTable('lecaip/import')}`(
        `folder` VARCHAR(255),
        `domain` VARCHAR(255),
        `type`  VARCHAR(255),
        `id_src` BIGINT,
        `id_desc` BIGINT,
        `status` INT(5),
        `value` TEXT,
        INDEX (`folder`,`domain`, `type`, `id_src`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE IF NOT EXISTS `{$this->getTable('lecaip/user')}`(
        `user_id` INT(11) UNIQUE NOT NULL,
        `notice`  TEXT
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();