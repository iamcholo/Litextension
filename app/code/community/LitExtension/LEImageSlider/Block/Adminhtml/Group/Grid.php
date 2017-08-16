<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Group_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('leimagesliderGrid');
        $this->setDefaultSort('leimageslider_group_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('leimageslider/group')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('leimageslider')->__('Store Views'),
                'index' => 'store_id',
                'type' => 'store',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false,
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));
        }

        $this->addColumn('leimageslider_group_id', array(
            'header' => Mage::helper('leimageslider')->__('Id'),
            'index' => 'leimageslider_group_id',
            'type' => 'number'
        ));

        $this->addColumn('title', array(
            'header' => Mage::helper('leimageslider')->__('Title'),
            'index' => 'title',
            'type' => 'text',
        ));
        
        $this->addColumn('created_at', array(
			'header'	=> Mage::helper('leimageslider')->__('Created at'),
			'index' 	=> 'created_at',
			'width' 	=> '120px',
			'type'  	=> 'datetime',
		));
        $this->addColumn('updated_at', array(
                'header'	=> Mage::helper('leimageslider')->__('Updated at'),
                'index' 	=> 'updated_at',
                'width' 	=> '120px',
                'type'  	=> 'datetime',
        ));
                
        $this->addColumn('status', array(
            'header' => Mage::helper('leimageslider')->__('Status'),
            'index' => 'status',
            'width' => '100px',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('leimageslider')->__('Enabled'),
                '0' => Mage::helper('leimageslider')->__('Disabled'),
            )
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('leimageslider')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('leimageslider')->__('Edit'),
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
        $this->setMassactionIdField('leimageslider_group_id');
        $this->getMassactionBlock()->setFormFieldName('leimageslider');
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('leimageslider')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('leimageslider')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('leimageslider')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'status' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('leimageslider')->__('Status'),
                    'values' => array(
                        '1' => Mage::helper('leimageslider')->__('Enabled'),
                        '0' => Mage::helper('leimageslider')->__('Disabled'),
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