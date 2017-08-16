<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('customer', 'le_sociallogin_aid', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_atoken', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_pid', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->addAttribute('customer', 'le_sociallogin_ptoken', array(
    'type' => 'text',
    'visible' => false,
    'required' => false,
    'user_defined' => false
));

$installer->endSetup();