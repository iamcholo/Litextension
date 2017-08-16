<?php
/**
 * @project     ItemSlider
 * @package LitExtension_ItemSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Block_Adminhtml_Itemslider_Grid extends Mage_Adminhtml_Block_Widget_Grid{

	public function __construct(){
		parent::__construct();
		$this->setId('itemsliderGrid');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}

	protected function _prepareCollection(){
		$collection = Mage::getModel('itemslider/group')->getCollection();
        $collection->getSelect()->joinInner(array('slides' => $collection->getTable('itemslider/slides')), 'main_table.slide_id = slides.slide_id', array('slide_title' => 'slide_name'));
		$this->setCollection($collection);
		return parent::_prepareCollection();
	}

	protected function _prepareColumns(){
        $this->addColumn('slide_title', array(
            'header' => Mage::helper('itemslider')->__('Slider'),
            'index' => 'slide_title',
            'filter_index' => '`slides`.`slide_name`',
            'type' => 'text',
            'width' => '200px',
        ));

		$this->addColumn('entity_id', array(
			'header'	=> Mage::helper('itemslider')->__('Id'),
			'index'		=> 'entity_id',
			'type'		=> 'number'
		));
		$this->addColumn('group_name', array(
			'header'=> Mage::helper('itemslider')->__('Slider Tab Name'),
			'index' => 'group_name',
			'type'	 	=> 'text',

		));
		$this->addColumn('item_type', array(
			'header'=> Mage::helper('itemslider')->__('Item Type'),
			'index' => 'item_type',
			'type'		=> 'options',
			'options'	=> array(
				'1' => Mage::helper('itemslider')->__('Category'),
				'0' => Mage::helper('itemslider')->__('Product'),
			)

		));
		$this->addColumn('item_ids', array(
			'header'=> Mage::helper('itemslider')->__('Item Ids'),
			'index' => 'item_ids',
			'type'	 	=> 'text',

		));
		$this->addColumn('enable_link', array(
			'header'=> Mage::helper('itemslider')->__('Enable Link'),
			'index' => 'enable_link',
			'type'		=> 'options',
			'options'	=> array(
				'1' => Mage::helper('itemslider')->__('Yes'),
				'0' => Mage::helper('itemslider')->__('No'),
			)
		));
        $this->addColumn('tabs_order', array(
            'header'=> Mage::helper('itemslider')->__('Order'),
            'index' => 'tabs_order',
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
		$this->setMassactionIdField('entity_id');
		$this->getMassactionBlock()->setFormFieldName('itemslider');
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