<?php
/**
 * @project: SimpleConfigurableProducts
 * @author : LitExtension
 * @url    : http://litextension.com
 * @email  : litextension@gmail.com
 */

class LitExtension_SimpleConfigurableProducts_Block_Catalog_Product_View_Media extends Mage_Catalog_Block_Product_View_Media {

    public function getGalleryUrl($image=null)
    {
        $params = array(
            'id'=>$this->getProduct()->getId(),
            'pid'=>$this->getProduct()->getCpid()
        );
        if ($image) {
            $params['image'] = $image->getValueId();
            return $this->getUrl('*/*/gallery', $params);
        }
        return $this->getUrl('*/*/gallery', $params);
    }


}


