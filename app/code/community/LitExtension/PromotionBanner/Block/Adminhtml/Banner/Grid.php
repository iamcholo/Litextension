<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Block_Adminhtml_Banner_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('bannerGrid');
        $this->setDefaultSort('promotionbanner_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('promotionbanner/banner')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('promotionbanner_id', array(
            'header' => Mage::helper('promotionbanner')->__('ID'),
            'index' => 'promotionbanner_id',
            'type' => 'number'
        ));
        $this->addColumn('title', array(
            'header' => Mage::helper('promotionbanner')->__('Title'),
            'index' => 'title',
            'type' => 'text',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('promotionbanner')->__('Store Views'),
                'index' => 'store_id',
                'type' => 'store',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false,
				'width'=>'180px',
                'filter_condition_callback' => array($this, '_filterStoreCondition'),
            ));
        }
		$this->addColumn('status', array(
            'header' => Mage::helper('promotionbanner')->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('promotionbanner')->__('Enabled'),
                '0' => Mage::helper('promotionbanner')->__('Disabled'),
            ),
			'width'=>'120px',
        ));
        $this->addColumn('created_at', array(
            'header' => Mage::helper('promotionbanner')->__('Created at'),
            'index' => 'created_at',
            'width' => '120px',
            'type' => 'datetime',
        ));
        $this->addColumn('updated_at', array(
            'header' => Mage::helper('promotionbanner')->__('Updated at'),
            'index' => 'updated_at',
            'width' => '120px',
            'type' => 'datetime',
        ));
        $this->addColumn('action', array(
            'header' => Mage::helper('promotionbanner')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('promotionbanner')->__('Edit'),
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
        $this->setMassactionIdField('promotionbanner_id');
        $this->getMassactionBlock()->setFormFieldName('banner');
        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('promotionbanner')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('promotionbanner')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('promotionbanner')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'status' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('promotionbanner')->__('Status'),
                    'values' => array(
                        '1' => Mage::helper('promotionbanner')->__('Enabled'),
                        '0' => Mage::helper('promotionbanner')->__('Disabled'),
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