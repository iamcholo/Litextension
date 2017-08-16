<?php

/**
 * @project     PromotionBanner
 * @package	LitExtension_PromotionBanner
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_PromotionBanner_Block_Adminhtml_Banner_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected function _prepareForm() {
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method' => 'post',
            'enctype' => 'multipart/form-data'
                )
        );
        $form->setUseContainer(true);
        $form->setHtmlIdPrefix('banner_');
        $form->setFieldNameSuffix('banner');
        $this->setForm($form);

        $fieldset = $form->addFieldset('banner_form', array('legend' => Mage::helper('promotionbanner')->__('Banner Information')));
        $wysiwygConfig = Mage::getSingleton('cms/wysiwyg_config')->getConfig();

        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Title'),
            'name' => 'title',
            'required' => true,
            'class' => 'required-entry',
        ));

        $fieldset->addField('content', 'editor', array(
            'label' => Mage::helper('promotionbanner')->__('Content'),
            'name' => 'content',
            'required' => true,
            'config' => $wysiwygConfig,
            'style' => '',
        ));

        $fieldset->addField('store_id', 'multiselect', array(
            'name' => 'stores[]',
            'label' => Mage::helper('promotionbanner')->__('Store Views'),
            'title' => Mage::helper('promotionbanner')->__('Store Views'),
            'required' => true,
            'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
        ));

        $fieldset->addField('show_at', 'multiselect', array(
            'name' => 'shows[]',
            'label' => Mage::helper('promotionbanner')->__('Show At'),
            'title' => Mage::helper('promotionbanner')->__('Show At'),
            'values' => Mage::getModel('promotionbanner/system_config_source_page')->toOptionArray(),
            'value' => array(1),
        ));

        $fieldset->addField('mobile_show', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Show in Mobile'),
            'name' => 'mobile_show',
            'values' => Mage::getModel('promotionbanner/system_config_source_yesno')->toOptionArray(),
        ));

        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Status'),
            'name' => 'status',
            'values' => array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('promotionbanner')->__('Enabled'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('promotionbanner')->__('Disabled'),
                ),
            ),
        ));

        $fieldset_config = $form->addFieldset('banner_config_form', array('legend' => Mage::helper('promotionbanner')->__('Banner Setting')));
        $fieldset_config->addType('position', Mage::getConfig()->getBlockClassName('promotionbanner/form_element_position'));
        $option_yesno = Mage::getModel('promotionbanner/system_config_source_yesno')->toOptionArray();
        $option_easing = Mage::getModel('promotionbanner/system_config_source_easing')->toOptionArray();
        $close_effect = Mage::getModel('promotionbanner/system_config_source_closeeffect')->toOptionArray();
        $theme = Mage::getModel('promotionbanner/system_config_source_theme')->toOptionArray();

        $fieldset_config->addField('width', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Banner Width'),
            'name' => 'width',
            'class' => 'validate-le-widthheight ',
            'required' => true,
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Unit is px or %. (Example: 100px or 10%)') . '</p>',
        ));
        $fieldset_config->addField('width_type', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Width Type'),
            'name' => 'width_type',
            'values' => Mage::getModel('promotionbanner/system_config_source_widthtype')->toOptionArray(),
        ));

        $fieldset_config->addField('height', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Banner Height'),
            'name' => 'height',
            'class' => 'validate-le-widthheight',
            'required' => true,
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Unit is px or %. (Example: 100px or 10%)') . '</p>',
        ));

        $fieldset_config->addField('height_type', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Height Type'),
            'name' => 'height_type',
            'values' => Mage::getModel('promotionbanner/system_config_source_heighttype')->toOptionArray(),
        ));

        $fieldset_config->addField('position', 'position', array(
            'label' => Mage::helper('promotionbanner')->__('Banner Position'),
            'name' => 'position',
        ));

        $fieldset_config->addField('bgcolor', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Background Color'),
            'name' => 'bgcolor',
            'class' => 'validate-le-hexcolor-none color{required:false, adjust:false, hash:false}',
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Hex code of color (Example: FFFFFF) ') . '</p>',
        ));

        $fieldset_config->addField('border_width', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Border Width'),
            'name' => 'border_width',
            'class' => 'validate-number',
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Unit is px') . '</p>',
        ));

        $fieldset_config->addField('border_color', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Border Color'),
            'name' => 'border_color',
            'class' => 'validate-le-hexcolor-none color{required:false, adjust:false, hash:false}',
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Hex code of color (Example: FFFFFF)') . '</p>',
        ));

        $fieldset_config->addField('shadow_color', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Shadow Color'),
            'name' => 'shadow_color',
            'class' => 'validate-le-hexcolor-none color{required:false, adjust:false, hash:false}',
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Hex code of color (Example: FFFFFF)') . '</p>',
        ));

        $fieldset_config->addField('theme', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Theme Style'),
            'name' => 'theme',
            'values' => $theme,
        ));

        $fieldset_config->addField('overlay', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Enable Overlay'),
            'name' => 'overlay',
            'values' => $option_yesno,
        ));

        $fieldset_config->addField('dont_show', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Enable Don\'t Show Again'),
            'name' => 'dont_show',
            'values' => $option_yesno,
        ));

        $fieldset_config->addField('show_close', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Enable Close'),
            'name' => 'show_close',
            'values' => $option_yesno,
        ));

        $fieldset_config->addField('close_effect', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('On Click Close'),
            'name' => 'close_effect',
            'values' => $close_effect,
        ));

        $fieldset_config->addField('autohide', 'text', array(
            'label' => Mage::helper('promotionbanner')->__('Auto Hide'),
            'name' => 'autohide',
            'after_element_html' => '<p class="note">' . Mage::helper('promotionbanner')->__('Auto-hide after specified seconds, blank is off ') . '</p>',
        ));

        $fieldset_config->addField('autohide_effect', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Action After Autohide'),
            'name' => 'autohide_effect',
            'values' => array(
                array(
                    'value' => 0,
                    'label' => Mage::helper('promotionbanner')->__('Minimize'),
                ),
                array(
                    'value' => 1,
                    'label' => Mage::helper('promotionbanner')->__('Close'),
                ),
            ),
        ));

        $fieldset_config->addField('easing_in_id', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Easing In'),
            'name' => 'easing_in_id',
            'values' => $option_easing,
        ));

        $fieldset_config->addField('easing_out_id', 'select', array(
            'label' => Mage::helper('promotionbanner')->__('Easing Out'),
            'name' => 'easing_out_id',
            'values' => $option_easing,
        ));

        $fieldset_config->addField('start_date', 'date', array(
            'label' => Mage::helper('promotionbanner')->__('Start Date'),
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
            'name' => 'start_date',
            'class' => 'required-entry',
            'required' => true,
        ));

        $fieldset_config->addField('end_date', 'date', array(
            'label' => Mage::helper('promotionbanner')->__('End Date'),
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
            'format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT),
            'name' => 'end_date',
            'class' => 'required-entry',
            'required' => true,
        ));

        $bannerId = (int) $this->getRequest()->getParam('id');
        if ($bannerId != null) {
            if (Mage::getSingleton('adminhtml/session')->getBannerData()) {
                $form->setValues(Mage::getSingleton('adminhtml/session')->getBannerData());
                Mage::getSingleton('adminhtml/session')->setBannerData(null);
            } elseif (Mage::registry('current_banner')) {
                $form->setValues(Mage::registry('current_banner')->getData());
            }
        } else {
            $default = Mage::getModel('promotionbanner/system_config_source_default')->toOptionArray();
            $form->setValues($default);
        }

        return parent::_prepareForm();
    }

}