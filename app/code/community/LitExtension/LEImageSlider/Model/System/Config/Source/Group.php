<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_System_Config_Source_Group {

    public function toOptionArray() {

        $model = Mage::getModel('leimageslider/group');
        $collection = $model->getCollection();

        $data = array();
        foreach ($collection as $group) {
            $data[$group['leimageslider_group_id']] = $group['title'];
        }

        return $data;
    }

}
