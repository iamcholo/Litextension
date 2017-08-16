<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Slide_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('leimagesliderGrid');
        $this->setDefaultSort('leimageslider_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('leimageslider/slide')->getCollection();
        $collection->addFieldToFilter('group_id', array('eq'=>$this->getRequest()->getParam('id')));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        $this->addColumn('leimageslider_id', array(
            'header' => Mage::helper('leimageslider')->__('Id'),
            'index' => 'leimageslider_id',
            'type' => 'number'
        ));

        $this->addColumn('filethumbgrid', array(
            'header' => Mage::helper('leimageslider')->__('Thumbnail'),
            'align' => 'center',
            'index' => 'filethumbgrid',
            'type' => 'text',
            'width' => '160px',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('title', array(
            'header' => Mage::helper('leimageslider')->__('Title'),
            'index' => 'title',
            'type' => 'text',
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('leimageslider')->__('Status'),
            'index' => 'status',
            'width' => '100px',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('leimageslider')->__('Enabled'),
                '0' => Mage::helper('leimageslider')->__('Disabled'),
            ),
            'sortable' => false,
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('leimageslider')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('leimageslider')->__('Edit'),
                    'url' => array('base' => '*/leimageslider_slide/edit/', 'params' => array( 'group' => $this->getRequest()->getParam('id'))),
                    'field' => 'id'
                ),
                array(
                    'caption' => Mage::helper('leimageslider')->__('Delete'),
                    'url' => array('base' => '*/leimageslider_slide/delete/', 'params' => array( 'group' => $this->getRequest()->getParam('id'))),
                    'field' => 'id',
                    'confirm' => $this->__('Are you sure want to do this ?'),
                )
            ),
            'filter' => false,
            'is_system' => true,
            'sortable' => false,
            'renderer' => 'LitExtension_LEImageSlider_Block_Widget_Grid_Column_Renderer_Action',
        ));
        return parent::_prepareColumns();
    }

    public function getRowUrl($row) {
        //return $this->getUrl('*/leimageslider_slide/edit', array('id' => $row->getId(), 'group' => $this->getRequest()->getParam('id')));
        return ;
    }

    public function getGridUrl() {
        return $this->getUrl('*/leimageslider_slide/grid', array('_current' => true, 'group' => $this->getRequest()->getParam('id')));
    }

    protected function _afterLoadCollection() {
        $this->getCollection()->walk('afterLoad');
        parent::_afterLoadCollection();
    }

    protected function _filterStoreCondition($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->addStoreFilter($value);
        return $this;
    }

}