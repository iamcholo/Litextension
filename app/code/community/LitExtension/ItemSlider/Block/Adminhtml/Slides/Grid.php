<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Slides_Grid extends Mage_Adminhtml_Block_Widget_Grid{

	public function __construct(){
		parent::__construct();
		$this->setId('slidesGrid');
		$this->setDefaultSort('slide_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}

	protected function _prepareCollection(){
		$collection = Mage::getModel('itemslider/slides')->getCollection();
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns(){
		$this->addColumn('slide_id', array(
			'header'	=> Mage::helper('itemslider')->__('Id'),
			'index'		=> 'slide_id',
			'type'		=> 'number'
		));
		$this->addColumn('slide_name', array(
			'header'=> Mage::helper('itemslider')->__('Slider Name'),
			'index' => 'slide_name',
			'type'	 	=> 'text',

		));
		$this->addColumn('status', array(
			'header'	=> Mage::helper('itemslider')->__('Status'),
			'index'		=> 'status',
			'type'		=> 'options',
			'options'	=> array(
				'1' => Mage::helper('itemslider')->__('Enabled'),
				'0' => Mage::helper('itemslider')->__('Disabled'),
			)
		));
		$this->addColumn('created_at', array(
			'header'	=> Mage::helper('itemslider')->__('Created at'),
			'index' 	=> 'created_at',
			'width' 	=> '120px',
			'type'  	=> 'datetime',
		));
		$this->addColumn('updated_at', array(
			'header'	=> Mage::helper('itemslider')->__('Updated at'),
			'index' 	=> 'updated_at',
			'width' 	=> '120px',
			'type'  	=> 'datetime',
		));
		$this->addColumn('action',
			array(
				'header'=>  Mage::helper('itemslider')->__('Action'),
				'width' => '100',
				'type'  => 'action',
				'getter'=> 'getId',
				'actions'   => array(
					array(
						'caption'   => Mage::helper('itemslider')->__('Edit'),
						'url'   => array('base'=> '*/*/edit'),
						'field' => 'id'
					)
				),
				'filter'=> false,
				'is_system'	=> true,
				'sortable'  => false,
		));
		return parent::_prepareColumns();
	}

	protected function _prepareMassaction(){
		$this->setMassactionIdField('slide_id');
		$this->getMassactionBlock()->setFormFieldName('slider');
		$this->getMassactionBlock()->addItem('delete', array(
			'label'=> Mage::helper('itemslider')->__('Delete'),
			'url'  => $this->getUrl('*/*/massDelete'),
			'confirm'  => Mage::helper('itemslider')->__('Are you sure?')
		));
		$this->getMassactionBlock()->addItem('status', array(
			'label'=> Mage::helper('itemslider')->__('Change status'),
			'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
			'additional' => array(
				'status' => array(
						'name' => 'status',
						'type' => 'select',
						'class' => 'required-entry',
						'label' => Mage::helper('itemslider')->__('Status'),
						'values' => array(
								'1' => Mage::helper('itemslider')->__('Enabled'),
								'0' => Mage::helper('itemslider')->__('Disabled'),
						)
				)
			)
		));
		return $this;
	}

	public function getRowUrl($row){
		return $this->getUrl('*/*/edit', array('id' => $row->getId()));
	}

	public function getGridUrl(){
		return $this->getUrl('*/*/grid', array('_current'=>true));
	}

	protected function _afterLoadCollection(){
		$this->getCollection()->walk('afterLoad');
		parent::_afterLoadCollection();
	}

	protected function _filterStoreCondition($collection, $column){
		if (!$value = $column->getFilter()->getValue()) {
        	return;
		}
		$collection->addStoreFilter($value);
		return $this;
    }
}