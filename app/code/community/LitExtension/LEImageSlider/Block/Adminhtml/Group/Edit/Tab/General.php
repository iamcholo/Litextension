<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Block_Adminhtml_Group_Edit_Tab_General extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('leimageslider_');
        $form->setFieldNameSuffix('leimageslider');
        $this->setForm($form);
        $fieldset = $form->addFieldset('leimageslider_form', array('legend' => Mage::helper('leimageslider')->__('Style Setting')));
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig();

        $fieldset->addField('autoplay', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Auto Play'),
            'name' => 'autoplay',
            'required'=> true,
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('leimageslider')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('leimageslider')->__('No'),
                ),
            ),
        ));

        $fieldset->addField('width', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Banner Width'),
            'name' => 'width',
            'required' => true,
            'class' => 'validate-le-widthheight',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Unit is px or %. (Example: 100px or 10%) ') . '</p>'
        ));

        $fieldset->addField('dirnav', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Show Direction Navigation'),
            'name' => 'dirnav',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('leimageslider')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('leimageslider')->__('No'),
                ),
            ),
        ));

        $fieldset->addField('controlnav', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Show Control Navigation'),
            'name' => 'controlnav',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('leimageslider')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('leimageslider')->__('No'),
                ),
            ),
        ));
        $fieldset->addField('pausehorver', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Pause On Hover '),
            'name' => 'pausehorver',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('leimageslider')->__('Yes'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('leimageslider')->__('No'),
                ),
            ),
        ));

        $fieldset->addField('pretext', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Previous Text'),
            'name' => 'pretext',
            'required' => true,
            'class' => 'required-entry',
        ));

        $fieldset->addField('nexttext', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Next Text'),
            'name' => 'nexttext',
            'required' => true,
            'class' => 'required-entry',
        ));
        
        $fieldset_text = $form->addFieldset('leimageslider_form_text', array('legend' => Mage::helper('leimageslider')->__('Text Setting')));
        
        $fieldset_text->addField('textsize', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Text Size'),
            'name' => 'textsize',
            'required' => true,
            'class' => 'required-entry validate-number',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Unit is px ') . '</p>'
        ));

        $fieldset_text->addField('textcolor', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Text Color'),
            'name' => 'textcolor',
            'required' => true,
            'class' => 'validate-le-hexcolor color{required:false, adjust:false, hash:false}',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__(' Hex code color') . '</p>',
        ));

        $fieldset_text->addField('textmargin', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Text Margin'),
            'name' => 'textmargin',
            'required' => true,
            'class' => 'required-entry validate-number',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Unit is px ') . '</p>'
        ));

        $fieldset_text->addField('bgcolor', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Text Background Color'),
            'name' => 'bgcolor',
            'required' => true,
            'class' => 'validate-le-hexcolor color{required:false, adjust:false, hash:false}',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__(' Hex code color') . '</p>'
        ));

        $fieldset_text->addField('bgtransparency', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Text Background Transparency'),
            'name' => 'bgtransparency',
            'required' => true,
            'class' => 'required-entry validate-number validate-number-range number-range-0-1',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Range 0 > 1') . '</note>'
        ));
        
        $fieldset_transition = $form->addFieldset('leimageslider_form_transition', array('legend' => Mage::helper('leimageslider')->__('Transition Setting')));
        $effect_options = Mage::getModel('leimageslider/system_config_source_transition_effect')->toOptionArray();
        $fieldset_transition->addField('effect', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Slider Effect'),
            'name' => 'effect',
            'values' => $effect_options,
        ));

        $fieldset_transition->addField('animspeed', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Transition Speed'),
            'name' => 'animspeed',
            'class' => 'required-entry validate-number',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Unit is ms ') . '</p>'
        ));

        $fieldset_transition->addField('pausetime', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Transition Delay Time'),
            'name' => 'pausetime',
            'class' => 'required-entry validate-number',
            'after_element_html' => '<p class="note">' . Mage::helper('leimageslider')->__('Unit is ms ') . '</p>'
        ));

        $fieldset_transition->addField('startslide', 'text', array(
            'label' => Mage::helper('leimageslider')->__('Start Slide'),
            'name' => 'startslide',
            'class' => 'required-entry validate-number',
        ));
        
        
        $fieldset_theme = $form->addFieldset('leimageslider_form_theme', array('legend' => Mage::helper('leimageslider')->__('Theme Setting')));
        
        $theme_options = Mage::getModel('leimageslider/system_config_source_theme_theme')->toOptionArray();

        $fieldset_theme->addField('theme', 'select', array(
            'label' => Mage::helper('leimageslider')->__('Theme slide'),
            'name' => 'theme',
            'values' => $theme_options,
        ));
        
        $leimagesliderId = $this->getRequest()->getParam('id');
        if ($leimagesliderId != null) {
            if (Mage::getSingleton('adminhtml/session')->getLeimagesliderData()) {
                $form->setValues(Mage::getSingleton('adminhtml/session')->getLeimagesliderData());
                Mage::getSingleton('adminhtml/session')->setLeimagesliderData(null);
            } elseif (Mage::registry('current_leimageslider')) {

                $form->setValues(Mage::registry('current_leimageslider')->getData());
            }
        } else {
            $default = Mage::getModel('leimageslider/system_config_source_default')->getDefaultConfig();
            $form->setValues($default);
        }
        return parent::_prepareForm();
    }

}