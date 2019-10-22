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
use rockyuan\wx3\third\core\ComponentAccessToken;
use rockyuan\wx3\core\Driver;
use rockyuan\wx3\core\Exception;

class PreAuthCode extends Driver {

    //  获取api_create_preauthcode的接口地址
    const API_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode';

    private $redisPrefix = 'wx3.';

    /**
     * 获得PreAuthCode
     *
     * @param $refresh boolean 是否刷新缓存
     * @return string
     */
    public function getPreAuthCode($refresh = false){

        $key = $this->redisPrefix . $this->conf['app_id'];

        $field = 'pre_auth_code';

        $redis = Yii::$app->redis;

        if($refresh == true){
            $redis->hdel($key, $field);
        }
        else{
            $pre_auth_code = $redis->hget($key, $field);
            $pre_auth_code_expire = $redis->hget($key, $field . "_expire");
        }

        if( time() > $pre_auth_code_expire || empty( $pre_auth_code ) ){
            $token = $this->getFromServer();

            if ( empty($token['pre_auth_code']) ){
                Yii::info("接口没返回pre_auth_code: " . json_encode( $token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ));

                throw new Exception("接口没返回pre_auth_code");
            }

            Yii::info("接口返回pre_auth_code: " . json_encode( $token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ));

            $pre_auth_code = $token['pre_auth_code'];
            $pre_auth_code_expire = time() + $token['expires_in'] - 120; // 过期前120开始更新

            $data = [
                $field => $pre_auth_code,
                ($field . "_expire") => (string)$pre_auth_code_expire,
            ];

            foreach ($data as $field => $value) {
                $redis->hset($key, $field, $value);
            }

            $redis->expire($key, $token['expires_in']);

            Yii::info("$key 新pre_auth_code(".date('H:i:s', $pre_auth_code_expire).") $pre_auth_code" );
        }
        else{
            Yii::info("$key 旧pre_auth_code(".date('H:i:s', $pre_auth_code_expire).") $pre_auth_code" );
        }

        return $pre_auth_code;
    }

    /**
     * 从服务器上获得pre_auth_code
     *
     * @return mixed
     * 
     * @throws \rockyuan\wx3\core\Exception
     */
    public function getFromServer(){

        try{
            $postData = [
                "component_appid" => $this->conf['app_id'],
            ];

            $component_access_token = (new ComponentAccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();
    
            $response = $this->post(self::API_TOKEN_GET . "?component_access_token=$component_access_token", $postData)->setFormat(Client::FORMAT_JSON)->send();
    
            $data = $response->getData();
    
            if( empty($data['pre_auth_code']) ){
                throw new Exception($data);
            }
    
            return $data;
        }
        catch(Exception $e){
            Yii::info("getFromServer错误: " . json_encode( $e, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
        }
    }
}