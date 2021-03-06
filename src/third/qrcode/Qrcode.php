<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\qrcode;

use rockyuan\wx3\core\Driver;
use rockyuan\wx3\third\core\Authorization;
use yii\httpclient\Client;

/**
 * Qrcode
 * 二维码生成接口
 * @package rockyuan\wx3\third\qrcode
 * 
 */
class Qrcode extends Driver {

    private $accessToken;

    //  生成临时二维码
    const API_QRCODE_URL = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';

    public function init()
    {
        parent::init();
        $this->accessToken = (new Authorization(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getAuthToken($this->extra['appid']);
    }

    /**
     * 生成临时二维码
     *
     * @param $val integer 二维码的值
     * @param $seconds integer 过期秒数
     * 
     * @return array
     */
    public function intTemp($val, $seconds = 2592000){
        return $this->temp($val, $seconds);
    }

    public function strTemp($val, $seconds = 2592000){
        return $this->temp($val, $seconds);
    }

    /**
     * 生成一个临时二维码
     * 此方法会根据$val的类型来决定是字符串还是整数。
     *
     * @param $val 值
     * @param int $seconds  过期时间
     * 
     */
    public function temp($val, $seconds = 2592000){
        if(is_int($val) && $val > 0){
            return $this->tempQrcode('QR_SCENE',$seconds,['scene_id'=>$val]);
        }else{
            return $this->tempQrcode('QR_STR_SCENE',$seconds,['scene_str'=>$val]);
        }
    }

    /**
     * 生成临时二维码
     * @param string $action    数字还是字符串类型
     * @param int $seconds  二维码过期时间
     * @param array $scene  值
     * @return mixed
     */
    private function tempQrcode($action = 'QR_SCENE', $seconds = 2592000, $scene = ['scene_id'=>0]){
        $params = array_merge(['expire_seconds'=>$seconds,'action_name'=>$action,'action_info'=>['scene'=>$scene]]);
        $response = $this->post(Qrcode::API_QRCODE_URL."?access_token={$this->accessToken}",$params)
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);
        return $response->getData();
    }

    /**
     * 生成永久的数字型二维码
     * @param $val integer
     * @return mixed
     */
    public function intForver($val){
        return $this->foreverQrcode('QR_LIMIT_SCENE',['scene_id'=>$val]);
    }

    /**
     * 生成永久的字符型二维码
     * @param $val
     * @return mixed
     */
    public function strForver($val){
        return $this->foreverQrcode('QR_LIMIT_STR_SCENE',['scene_str'=>$val]);
    }

    /**
     * 生成一个永久二维码
     * 此方法会根据$val的类型来决定是字符串还是整数。
     *
     * @param $val 值
     * @since 1.2
     */
    public function forever($val){
        if(is_int($val) && $val > 0){
            return $this->foreverQrcode('QR_LIMIT_SCENE',['scene_id'=>$val]);
        }else{
            return $this->foreverQrcode('QR_LIMIT_STR_SCENE',['scene_str'=>$val]);
        }
    }

    private function foreverQrcode($action = 'QR_LIMIT_SCENE', $scene = ['scene_id'=>0]){
        $params = array_merge(['action_name'=>$action,'action_info'=>['scene'=>$scene]]);
        $response = $this->post(Qrcode::API_QRCODE_URL."?access_token={$this->accessToken}",$params)
            ->setFormat(Client::FORMAT_JSON)->send();

        $response->setFormat(Client::FORMAT_JSON);
        return $response->getData();
    }
}