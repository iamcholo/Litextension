<?php

/**
 * @project     RotatingImageSlider
 * @package	LitExtension_RotatingImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_RotatingImageSlider_Block_Adminhtml_Slide_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('rotatingimagesliderGrid');
        $this->setDefaultSort('rotatingimageslider_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('rotatingimageslider/slide')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        $this->addColumn('groupname', array(
            'header' => Mage::helper('rotatingimageslider')->__('Group'),
            'index' => 'groupname',
            'type' => 'text',
            'width' => '120px',
        ));

        $this->addColumn('rotatingimageslider_id', array(
            'header' => Mage::helper('rotatingimageslider')->__('Id'),
            'index' => 'rotatingimageslider_id',
            'type' => 'number',
        ));

        $this->addColumn('filethumbgrid', array(
            'header' => Mage::helper('rotatingimageslider')->__('Thumbnail'),
            'align' => 'center',
            'index' => 'filethumbgrid',
            'type' => 'text',
            'width' => '150px',
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('rotatingimageslider')->__('Name '),
            'index' => 'name',
            'type' => 'text',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('rotatingimageslider')->__('Store Views'),
                'index' => 'store_id',
                'type' => 'store',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false,
                'width' => '200px',
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('status', array(
            'header' => Mage::helper('rotatingimageslider')->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'width' => '80px',
            'options' => array(
                '1' => Mage::helper('rotatingimageslider')->__('Enabled'),
                '0' => Mage::helper('rotatingimageslider')->__('Disabled'),
            )
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('rotatingimageslider')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('rotatingimageslider')->__('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'id'
                )
            ),
            'filter' => false,
            'is_system' => true,
            'sortable' => false,
        ));
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction() {
        $this->setMassactionIdField('rotatingimageslider_id');
        $this->getMassactionBlock()->setFormFieldName('rotatingimageslider');
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('rotatingimageslider')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('rotatingimageslider')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('rotatingimageslider')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'status' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('rotatingimageslider')->__('Status'),
                    'values' => array(
                        '1' => Mage::helper('rotatingimageslider')->__('Enabled'),
                        '0' => Mage::helper('rotatingimageslider')->__('Disabled'),
                    )
                )
            )
        ));
        return $this;
    }

    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current' => true));
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