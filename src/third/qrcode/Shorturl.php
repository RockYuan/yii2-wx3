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
 * 长链接转短地址助手
 * 
 * @package rockyuan\wx3\third\qrcode
 */
class Shorturl extends Driver {

    private $accessToken;

    const API_SHORT_URL = 'https://api.weixin.qq.com/cgi-bin/shorturl';

    public function init(){
        parent::init();
        $this->accessToken = (new Authorization(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getAuthToken($this->extra['appid']);
    }

    /**
     * 将一个微信支付的长连接转化为短链接
     * @param string $longUrl 长连接
     * @return string
     */
    public function toShort($longUrl = ''){
        $response = $this->post(self::API_SHORT_URL."?access_token=".$this->accessToken,['action'=>'long2short','long_url'=>$longUrl])->setFormat(Client::FORMAT_JSON)->send();

        $data = $response->getData();
        return $data['short_url'];
    }

}