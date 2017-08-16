<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_System_Config_Source_Transition_Effect {

    public function toOptionArray() {
        return array(
            array('value' => 'sliceDown', 'label' => Mage::helper('leimageslider')->__('SliceDown')),
            array('value' => 'sliceDownLeft', 'label' => Mage::helper('leimageslider')->__('SliceDownLeft')),
            array('value' => 'sliceUp', 'label' => Mage::helper('leimageslider')->__('SliceUp')),
            array('value' => 'sliceUpLeft', 'label' => Mage::helper('leimageslider')->__('SliceUpLeft')),
            array('value' => 'sliceUpDown', 'label' => Mage::helper('leimageslider')->__('SliceUpDown')),
            array('value' => 'sliceUpDownLeft', 'label' => Mage::helper('leimageslider')->__('SliceUpDownLeft')),
            array('value' => 'fold', 'label' => Mage::helper('leimageslider')->__('Fold')),
            array('value' => 'fade', 'label' => Mage::helper('leimageslider')->__('Fade')),
            array('value' => 'random', 'label' => Mage::helper('leimageslider')->__('Random')),
            array('value' => 'slideInRight', 'label' => Mage::helper('leimageslider')->__('SlideInRight')),
            array('value' => 'slideInLeft', 'label' => Mage::helper('leimageslider')->__('SlideInLeft')),
            array('value' => 'boxRandom', 'label' => Mage::helper('leimageslider')->__('BoxRandom')),
            array('value' => 'boxRain', 'label' => Mage::helper('leimageslider')->__('BoxRain')),
            array('value' => 'boxRainReverse', 'label' => Mage::helper('leimageslider')->__('BoxRainReverse')),
            array('value' => 'boxRainGrow', 'label' => Mage::helper('leimageslider')->__('BoxRainGrow')),
            array('value' => 'boxRainGrowReverse', 'label' => Mage::helper('leimageslider')->__('BoxRainGrowReverse')),
        );
    }

}
