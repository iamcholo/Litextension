<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Item extends Mage_Core_Block_Template implements Mage_Widget_Block_Interface
{

    protected $_htmlTemplate = 'le_itemslider/widget.phtml';
    protected $_serializer = null;
    protected $_group_id;

    protected function _construct()
    {
        $this->_serializer = new Varien_Object();
        parent::_construct();
    }

    protected function _beforeToHtml()
    {
        $this->_group_id = $this->getData('le_slide_id');
        parent::_beforeToHtml();
    }

    protected function _toHtml()
    {
        $this->setTemplate($this->_htmlTemplate);

        $groups = Mage::getModel('itemslider/group')->getCollection()
            ->addEnableFilter(LitExtension_ItemSlider_Model_Status::STATUS_ENABLED)
            ->addFieldToFilter('slide_id', array('eq' => $this->_group_id))
            ->setOrder('tabs_order',Varien_Db_Select::SQL_ASC)
            ->load();
        foreach ($groups as $group) {
            $groupId = $group->getId();
            break;
        }

        $cf_style1 = Mage::getStoreConfig("itemslider/style1");
        $cf_style2 = Mage::getStoreConfig("itemslider/style2");
        $config = Mage::getStoreConfig("itemslider/itemslider");
        $config["_target"] = "";
        if ($config["linktype"] == 0) {
            $config["_target"] = "blank";
        }
        $this->assign('groupId', $groupId);
        $this->assign('groups', $groups);
        $this->assign('config', $config);
        $this->assign('cf_style1', $cf_style1);
        $this->assign('cf_style2', $cf_style2);

        return parent::_toHtml();
    }

}
