<?php

/*
 * This file is part of the rockyuan/yii2-wx3.
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\helpers;

/**
 * 自定义的json数据formatter
 * 该formatter主要是在数据进行json_encode时对其中的汉字内容不进行编码
 *
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/lang-7.html
 * @package rockyuan\wx3\helpers
 */
class JsonFormatter extends \yii\httpclient\JsonFormatter {

    public $encodeOptions = 256;
}