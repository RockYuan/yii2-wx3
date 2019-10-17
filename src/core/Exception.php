<?php
/*
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\core;

/**
 * Exception
 * yii2-wx3专属异常类
 * 
 */
class Exception extends \yii\base\Exception {

    public function getName(){
        return 'Yii2-wx3';
    }

}
