<?php
/**
 * @project     SocialLoginLite
 * @package     LitExtension_SocialLoginLite
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
$installer = $this;
/* @var $installer Mage_Customer_Model_Entity_Setup */

$installer->startSetup();

$installer->addAttribute('customer', 'le_sociallogin_fid', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));


$installer->addAttribute('customer', 'le_sociallogin_ftoken', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_gid', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_gtoken', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_tid', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_ttoken', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->endSetup();
