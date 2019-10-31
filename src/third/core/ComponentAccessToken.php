<?php

/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\core;

use Yii;
use yii\httpclient\Client;
use rockyuan\wx3\core\Driver;
use rockyuan\wx3\core\Exception;

class ComponentAccessToken extends Driver {

    //  获取component_access_token的接口地址
    const API_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';

    private $redisPrefix = 'wx3.';

    /**
     * 保存定时送来的第三方ticket 
     * */
    public function setComponentVerifyTicket($ticket){
        
        $key = $this->redisPrefix . $this->conf['app_id'];
        $field = 'ComponentVerifyTicket';

        $redis = Yii::$app->redis;

        $ticketOld = $redis->hget($key, $field);

        if ( empty($ticketOld) || $ticketOld != $ticket ) {
            // 旧ticket不存在 或 ticket已更新
            $redis->hset($key, $field, $ticket);
            $redis->expire($key, 7200);

            return true;
        }
        else{
            return false;
        }
        
    }

    /**
     * 读出的第三方ticket 
     * */
    public function getComponentVerifyTicket(){
        
        $key = $this->redisPrefix . $this->conf['app_id'];
        $field = 'ComponentVerifyTicket';

        $redis = Yii::$app->redis;

        $ticket = $redis->hget($key, $field);

        if ( empty($ticket) ) {
            // ticket不存在 或 未送来
            throw new Exception("ComponentVerifyTicket 不存在");
        }

        return $ticket;
    }

    /**
     * 获得access_token
     *
     * @param $cacheRefresh boolean 是否刷新缓存
     * @return string
     */
    public function getToken($refresh = false){

        $key = $this->redisPrefix . $this->conf['app_id'];

        $field = 'access_token';

        $redis = Yii::$app->redis;

        if($refresh == true){
            $redis->hdel($key, $field);
        }
        else{
            $access_token = $redis->hget($key, $field);
            $access_token_expire = $redis->hget($key, $field . "_expire");
        }

        if( time() > $access_token_expire || empty( $access_token ) ){
            $token = $this->getTokenFromServer();

            if ( empty($token['component_access_token']) ){
                Yii::error("接口没返回component_access_token: " . json_encode( $token, JSON_UNESCAPED_SLASHES ));

                throw new Exception("接口没返回component_access_token");
            }

            $access_token = $token['component_access_token'];
            $access_token_expire = time() + $token['expires_in'] - 1800; // 过期前半小时开始更新at

            $data = [
                $field => $access_token,
                ($field . "_expire") => (string)$access_token_expire,
            ];

            // $redisRes = $redis->hmset($key, $data); // yii2-redis 2.0.9不可设置数组

            foreach ($data as $field => $value) {
                $redis->hset($key, $field, $value);
            }

            $redis->expire($key, $token['expires_in']);

            Yii::info("$key 新令牌(".date('H:i:s', $access_token_expire).") $access_token" );
        }
        else{
            Yii::info("$key 旧令牌(".date('H:i:s', $access_token_expire).") $access_token" );
        }

        return $access_token;
    }

    /**
     * 从服务器上获得accessToken。
     *
     * @return mixed
     * 
     * @throws \rockyuan\wx3\core\Exception
     */
    private function getTokenFromServer(){

        try{
            $postData = [
                "component_appid" => $this->conf['app_id'],
                "component_appsecret" => $this->conf['secret'],
                "component_verify_ticket" => $this->getComponentVerifyTicket(),
            ];
    
            $response = $this->post(self::API_TOKEN_GET, $postData)->setFormat(Client::FORMAT_JSON)->send();
    
            $data = $response->getData();
    
            if( empty($data['component_access_token']) ){
                Yii::error("请求微信接口失败: " . $response->content );
                throw new Exception("请求微信接口失败");
            }
    
            return $data;
        }
        catch(Exception $e){
            Yii::error("请求微信接口异常: " . $e->getMessage() );
        }
    }
}