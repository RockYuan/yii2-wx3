<?php

/*
 * This file is part of the rockyuan/yii2-wx3.
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\core;

use rockyuan\wx3\helpers\Util;
use yii\base\Component;

/**
 * 接口类
 * 该类主要抽象出每个接口类必须的几个属性
 * @package rockyuan\wx3\core
 */
class Driver extends Component {

    /**
     * ERRORS
     */
    const ERROR_NO_RESPONSE = '本次请求并没有得到响应，请检查通讯是否畅通。';

    public $conf;
    public $extra;
    public $httpClient;

    /**
     * 生成一个request请求
     * @return mixed
     */
    protected function request(){
        return $this->httpClient->createRequest();
    }

    /**
     * 封装httpClient的get函数。
     *
     * @param $url string 请求地址
     * @param $params array 请求参数配置
     * @param $headers array 请求头部参数配置
     * @param $options array 操作项
     * @return mixed
     */
    protected function get($url,$params = [], $headers = [], $options = []){
        return $this->httpClient->get($url,$params,$headers,$options);
    }

    protected function post($url,$params = [], $headers = [], $options = []){
        return $this->httpClient->post($url,$params,$headers,$options);
    }


}