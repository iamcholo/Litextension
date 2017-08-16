<?php


class LitExtension_Onestepcheckout_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        if (isset($_POST['ajax']))
        {
            if ($_POST['ajax'] == 'login' && Mage::helper('customer')->isLoggedIn() != true)
            {
                $login = Mage::getSingleton('onestepcheckout/login_ajaxlogin');
                echo $login->getResult();
            }
        }
    }

    public function viewAction()
    {
    }
}

?>