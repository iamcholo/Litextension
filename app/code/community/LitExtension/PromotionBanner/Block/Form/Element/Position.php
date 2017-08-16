<?php
/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */

class LitExtension_PromotionBanner_Block_Form_Element_Position extends Varien_Data_Form_Element_Abstract{

    public function __construct($attributes=array())
    {
        parent::__construct($attributes);
        $this->setType('position');
        $this->setExtType('combobox');
    }

    public function getElementHtml()
    {
        $this->addClass('position');
        $value = $this->getValue();
        if(!isset($value)){
            $value = 1;
        }
        $html = '<div class="le-position-content">';
        $html .= '<input type="hidden" class="le-position-value" value="'.$value.'" id ="'.$this->getHtmlId().'" name="'.$this->getName().'" '.$this->serialize($this->getHtmlAttributes()).'/>';
        $html .= '<input type="text" class="le-position-show input-text" onmouseover="showLePositionMark(this);" value="'.$this->_getPostionName($value).'"/>';
        $html .= '<div class="le-position-mark" onclick="showLePositionUl(this);" onmouseout="hideLePositionMark(this);" >'.Mage::helper('promotionbanner')->__('click here to select').'</div>';
        $html .= $this->_getPositionOptionsHtml($value);
        $html .= '</div>';
        $html.= $this->getAfterElementHtml();
        return $html;
    }

    public function getHtmlAttributes()
    {
        return array('title', 'class', 'style', 'onclick', 'onchange', 'disabled', 'readonly', 'tabindex');
    }

    protected function _getPositionValues(){
        $data = Mage::getModel('promotionbanner/system_config_source_position')->toOptionArray();
        return $data;
    }

    protected function _getPositionOptionsHtml($select){
        $options = $this->_getPositionValues();
        $html = '<ul class="le-position-ul" onmouseover="showLePositionMark(this);" onmouseout="hideLePositionMark(this);">';
        foreach ($options as $option) {
            if($option['value'] == $select){$class = 'selected';}else{$class ='';}
            $html .= '<li class="'.$class.' le-position-li" onclick="hideLePositionUl(this);" position="'.$option['value'].'" option="'.$option['label'].'"></li>';
        }
        $html .= '</ul>';
        return $html;
    }

    protected function _getPostionName($select){
        $options = $this->_getPositionValues();
        $data = null;
        foreach( $options as $option){
            if($option['value'] == $select){
                $data = $option['label'];
                break;
            }
        }
        return $data;
    }
}