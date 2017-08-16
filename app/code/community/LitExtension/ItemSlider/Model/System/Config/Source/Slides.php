<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_ItemSlider_Model_System_Config_Source_Slides {

    public function toOptionArray() {

        $model = Mage::getModel('itemslider/slides');
        $collection = $model->getCollection()
            ->addEnableFilter(LitExtension_ItemSlider_Model_Status::STATUS_ENABLED);

        $data = array();
        foreach ($collection as $slides) {
            $data[$slides['slide_id']] = $slides['slide_name'];
        }

        return $data;
    }

}
