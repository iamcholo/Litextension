<?php

/**
 * @project     LEImageSlider
 * @package     LitExtension_LEImageSlider
 * @author      LitExtension
 * @email       litextension@gmail.com
 */
class LitExtension_LEImageSlider_Model_System_Config_Source_Default {

    public function getDefaultConfig() {
        $data['autoplay'] = 1;
        $data['width'] = 675;
        $data['height'] = 350;
        $data['dirnav'] = 1;
        $data['controlnav'] = 1;
        $data['pausehorver'] = 1;
        $data['pretext'] = "Prev";
        $data['nexttext'] = "Next";
        $data['textsize'] = 12;
        $data['textcolor'] = "FFFFFF";
        $data['textmargin'] = 5;
        $data['bgcolor'] = "000000";
        $data['bgtransparency'] = 0.8;
        $data['theme'] = "default";
        $data['effect'] = "random";
        $data['animspeed'] = 500;
        $data['pausetime'] = 3000;
        $data['startslide'] = 0;

        return $data;
    }

}

