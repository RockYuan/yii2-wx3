<?php

/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\js;

use Yii;
use rockyuan\wx3\core\Driver;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\httpclient\Client;
use rockyuan\wx3\core\Exception;
use rockyuan\wx3\third\core\Authorization;

/**
 * Js
 * 该助手类主要负责微信公众号JSSDK功能
 *
 * @package rockyuan\wx3\third\js
 */
class Js extends Driver {

    const API_TICKET = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';

    private $accessToken;

    private $redisPrefix = 'wx3.';

    public function init(){
        parent::init();
        $this->accessToken = (new Authorization(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getAuthToken($this->extra['appid']);
    }

    /**
     * 构造JSSDK配置参数
     *
     * @param array $apis api接口地址
     * @param boolean $debug 是否启动调试模式
     * @return mixed
     */
    public function buildConfig($url = '', $apis = [],$debug = false){

        if ( empty($apis) ) {
            $apis = $this->conf['jssdk'];
        }
        $signPackage = $this->signature($url);
        $config = array_merge(['debug'=>$debug],$signPackage,['jsApiList'=>$apis]);

        return Json::encode($config);
    }

    /**
     * 获得jssdk需要的配置参数
     * 这里包含appId、nonceStr、timestamp、url和signature。
     *
     * @return array
     */
    public function signature($url = ''){

        if (empty($url)){
            $url = Url::current([],true);
        }
        
        $nonce = Yii::$app->security->generateRandomString(32);
        $timestamp = time();
        $ticket = $this->ticket();

        $sign = [
            'appId' => $this->extra['appid'],
            'nonceStr' => $nonce,
            'timestamp' => $timestamp,
            'signature' => $this->getSignature($ticket, $nonce, $timestamp, $url),
        ];

        return $sign;
    }

    /**
     * 获得签名
     *
     * @param $ticket string jsapi_ticket
     * @param $nonce string 随机字符串
     * @param $timestamp integer 当前的时间戳
     * @param $url string 使用jssdk接口的url地址
     * @return string 签名
     */
    public function getSignature($ticket,$nonce,$timestamp,$url){

        return sha1("jsapi_ticket={$ticket}&noncestr={$nonce}&timestamp={$timestamp}&url={$url}");
    }

    /**
     * 获得jsapi_ticket
     * jsapi_ticket有访问次数的限制，同时每个jsapi_ticket有效期为7200秒，因此我们进行了存储。
     *
     * @return mixed
     */
    public function ticket(){

        $key = $this->redisPrefix . $this->extra['appid'];

        $field = 'js_ticket';

        $redis = Yii::$app->redis;

        $js_ticket = $redis->hget($key, $field);
        $js_ticket_expire = $redis->hget($key, $field . "_expire");

        if( time() > $js_ticket_expire || empty( $js_ticket ) ){

            try{
                // 从服务器获取
                $response = $this->get(self::API_TICKET."?access_token={$this->accessToken}&type=jsapi")->send();

                $data = $response->setFormat(Client::FORMAT_JSON)->getData();

                $js_ticket = $data['ticket'];
                $js_ticket_expire = time() + $data['expires_in'] - 60; // 过期前1分钟开始更新js票据

                $hash = [
                    $field => $js_ticket,
                    ($field . "_expire") => (string)$js_ticket_expire,
                ];
    
                foreach ($hash as $field => $value) {
                    $redis->hset($key, $field, $value);
                }

                Yii::info("$key 授权公众号新js票据(".date('H:i:s', $js_ticket_expire).") $js_ticket" );

                return $js_ticket;
            }
            catch(Exception $e){
                throw new Exception("获取授权公众号新js票据异常: " . $e->getMessage());
            }
        }
        else{
            Yii::info("$key 授权公众号现有js票据(".date('H:i:s', $js_ticket_expire).") $js_ticket" );
        }

        return $js_ticket;
    }
}