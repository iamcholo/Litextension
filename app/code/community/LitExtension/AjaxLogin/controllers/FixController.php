<?php
/**
 * @project     AjaxLogin
 * @package     LitExtension_AjaxLogin
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_AjaxLogin_FixController extends Mage_Core_Controller_Front_Action {

    public function indexAction(){
        $this->loadLayout();
//        $this->getLayout()->getBlock('root')->setTemplate('lit_ajaxlogin/fix.phtml');
        $this->renderLayout();
        return $this;
    }

    public function connectAction(){

        $attributeModel = Mage::getModel('eav/entity_attribute');
        $fid = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_fid');
        $ftoken = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_ftoken');

        $gid = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_gid');
        $gtoken = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_gtoken');

        $tid = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_tid');
        $ttoken = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_ttoken');

        $lid = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_lid');
        $ltoken = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_ltoken');

        $yid = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_yid');
        $ytoken = $attributeModel->getIdByCode('customer', 'lit_ajaxlogin_ytoken');

        if($fid == false || $ftoken == false ||
            $gid == false || $gtoken == false ||
            $tid == false || $ttoken  == false ||
            $lid == false || $ltoken == false ||
            $yid == false || $ytoken == false
        ){

            $setup = Mage::getModel('customer/entity_setup','core_setup');
            if($fid == false){
                echo 'lit_ajaxlogin_fid not exits <br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_fid', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_fid setup ok<br />';
            }
            if($ftoken == false){
                echo 'lit_ajaxlogin_ftoken not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_ftoken', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_ftoken setup ok<br />';
            }
            if($gid == false){
                echo 'lit_ajaxlogin_gid not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_gid', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_gid setup ok<br />';
            }
            if($gtoken == false){
                echo 'lit_ajaxlogin_gtoken not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_gtoken', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_gtoken setup ok<br />';
            }
            if($tid == false){
                echo 'lit_ajaxlogin_tid not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_tid', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_tid setup ok<br />';
            }
            if($ttoken == false){
                echo 'lit_ajaxlogin_ttoken not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_ttoken', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_ttoken setup ok<br />';
            }
            if($lid == false){
                echo 'lit_ajaxlogin_lid not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_lid', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_lid setup ok<br />';
            }
            if($ltoken == false){
                echo 'lit_ajaxlogin_ltoken not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_ltoken', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_ltoken setup ok<br />';
            }
            if($yid == false){
                echo 'lit_ajaxlogin_yid not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_yid', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_yid setup ok<br />';
            }
            if($ytoken == false){
                echo 'lit_ajaxlogin_ytoken not exits<br />';
                $setup->addAttribute('customer', 'lit_ajaxlogin_ytoken', array(
                    'type' => 'text',
                    'visible' => 0,
                    'required' => 0,
                    'user_defined' => 0,
                ));
                echo 'lit_ajaxlogin_ytoken setup ok<br />';
            }

            if (version_compare(Mage::getVersion(), '1.6.0', '<='))
            {
                $customer = Mage::getModel('customer/customer');
                $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
                $setup->addAttributeToSet('customer', $attrSetId, 'General', 'lit_ajaxlogin_fid');
            }
            if (version_compare(Mage::getVersion(), '1.4.2', '>='))
            {
                Mage::getSingleton('eav/config')
                    ->getAttribute('customer', 'lit_ajaxlogin_fid')
                    ->save();
            }

            echo "Setup complete<br />";
        } else {
            echo 'All attr exits. Nothing to do.';
        }
    }
}