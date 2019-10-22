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

class Authorization extends Driver {

    const AUTH_SCAN_URL = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=*appid*&pre_auth_code=*pre_auth_code*&auth_type=*auth_type*&redirect_uri=*redirect*';

    const AUTH_NOSCAN_URL = 'https://mp.weixin.qq.com/safe/bindcomponent?action=bindcomponent&no_scan=1&component_appid=*appid*&pre_auth_code=*pre_auth_code*&auth_type=*auth_type*&redirect_uri=*redirect*#wechat_redirect';

    //  获取api_create_preauthcode的接口地址
    const API_PRE_AUTH_CODE = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=';

    const API_QUERY_AUTH = "https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=";

    const API_AUTH_TOKEN = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=";

    const API_AUTHORIZER_INFO = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=";

    const API_AUTHORIZER_LIST = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_list?component_access_token=";

    private $redisPrefix = 'wx3.';

    /**
     * 生成预授权码和返回授权网址
     *
     * @param [string] $redirect
     * @param boolean $no_scan
     * @param integer $auth_type
     * @return string authUrl
     *
     * @author Rock <RockYuan@gmail.com>
     * @since 20191021
     */
    public function authUrl($redirect, $qrcode = false, $auth_type = 3){
        $search = ['*appid*', '*pre_auth_code*', '*auth_type*', '*redirect*'];
        $replace = [$this->conf['app_id'], $this->getPreAuthCode(), $auth_type, $redirect];

        if ($qrcode){
            $authUrl = self::AUTH_SCAN_URL;
        }
        else{
            $authUrl = self::AUTH_NOSCAN_URL;
        }

        $authUrl = str_replace($search, $replace, $authUrl);

        return $authUrl;
    }

    /**
     * 从接口获得微信返回
     *
     * @return mixed
     * 
     * @throws \rockyuan\wx3\core\Exception
     */
    private function getFromWx($url, $postData){

        try{
            $component_access_token = (new ComponentAccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken();

            if (empty($component_access_token)){
                throw new Exception("无第三方AT");
            }
    
            $response = $this->post($url . $component_access_token, $postData)->setFormat(Client::FORMAT_JSON)->send();
    
            $data = $response->getData();

            if( empty($data) ){
                Yii::error("请求微信接口失败: " . $response->content );
                throw new Exception("无返回");
            }
    
            return $data;
        }
        catch(Exception $e){
            Yii::error( "请求微信接口异常: " . $e->getMessage() );
        }
    }

    /**
     * 获得预授权码
     *
     * @return string
     */
    private function getPreAuthCode(){

        $postData = [
            "component_appid" => $this->conf['app_id'],
        ];

        $wxJson = $this->getFromWx(self::API_PRE_AUTH_CODE, $postData);

        if ( empty($wxJson['pre_auth_code']) ){
            Yii::error("接口没返回预授权码: " . json_encode( $wxJson, JSON_UNESCAPED_SLASHES ));

            throw new Exception("接口没返回预授权码");
        }

        $pre_auth_code = $wxJson['pre_auth_code'];
        $pre_auth_code_expire = time() + $wxJson['expires_in'] - 120; // 过期前120开始更新

        Yii::info( "预授权码: " . json_encode( $wxJson, JSON_UNESCAPED_SLASHES ) );

        return $pre_auth_code;
    }

    /**
     * 使用授权码获取授权信息
     *
     * @param [string] $authorizationCode
     * @return mixed
     *
     * @author Rock <RockYuan@gmail.com>
     * @since 20191021
     */
    public function queryAuth($authorizationCode){
        try{
            $postData = [
                "component_appid" => $this->conf['app_id'],
                "authorization_code" => $authorizationCode,
            ];

            $wxJson = (array) $this->getFromWx(self::API_QUERY_AUTH, $postData);

            if ( empty($wxJson['authorization_info']) ){
                Yii::error("接口没返回authorization_info: " . json_encode( $wxJson, JSON_UNESCAPED_SLASHES ));

                throw new Exception("接口没返回authorization_info");
            }
            else{
                if ( !empty($wxJson['authorization_info']) ){
                    $auth_info = $wxJson['authorization_info'];

                    $key = $this->redisPrefix . $auth_info['authorizer_appid'];

                    $field = 'authorizer_refresh_token';

                    $redis = Yii::$app->redis;

                    $redis->hset($key, $field, $auth_info['authorizer_refresh_token']);
                }
                return $wxJson;
            }
        }
        catch(Exception $e){
            Yii::error("获取授权信息异常: " . $e->getMessage() );
        }

        return null;
    }

    /**
     * 获取/刷新接口调用令牌
     *
     * @param [string] $appid
     * @param boolean $refresh
     * @return string access_token
     *
     * @author Rock <RockYuan@gmail.com>
     * @since 20191021
     */
    public function getAuthToken($appid, $refresh = false){
        $key = $this->redisPrefix . $appid;

        $field = 'access_token';

        $redis = Yii::$app->redis;

        if($refresh == true){
            $redis->hdel($key, $field);
        }
        else{
            $access_token = $redis->hget($key, $field);
            $access_token_expire = $redis->hget($key, $field . "_expire");

            $authorizer_refresh_token = $redis->hget($key, "authorizer_refresh_token");

            $hash = [
                $field => $access_token,
                ($field . "_expire") => (string)$access_token_expire,
                'authorizer_refresh_token' => $authorizer_refresh_token,
            ];
        }

        if ( empty($authorizer_refresh_token) ){
            throw new Exception("获取/刷新接口调用令牌错误: 无刷新令牌");
        }

        if( time() > $access_token_expire || empty( $access_token ) ){

            try{
                $postData = [
                    "component_appid" => $this->conf['app_id'],
                    "authorizer_appid" => $appid,
                    "authorizer_refresh_token" => $authorizer_refresh_token,
                ];

                $wxJson = $this->getFromWx(self::API_AUTH_TOKEN, $postData);

                if( empty($wxJson['authorizer_access_token']) ){
                    Yii::error("获取/刷新接口调用令牌错误: " . json_encode( $wxJson, JSON_UNESCAPED_SLASHES ) );
                    throw new Exception("无调用令牌返回");
                }

                $access_token = $wxJson['authorizer_access_token'];
                $access_token_expire = time() + $wxJson['expires_in'] - 600; // 过期前10分钟开始更新授权公众号AT
                $authorizer_refresh_token = $wxJson['authorizer_refresh_token'];

                $hash = [
                    $field => $access_token,
                    ($field . "_expire") => (string)$access_token_expire,
                    'authorizer_refresh_token' => $authorizer_refresh_token, // 为保持刷新令牌长期保存, 主key不要过期
                ];
    
                foreach ($hash as $field => $value) {
                    $redis->hset($key, $field, $value);
                }
    
                Yii::info("$key 授权公众号新令牌(".date('H:i:s', $access_token_expire).") $access_token" );

                return $access_token;
                
            }
            catch(Exception $e){
                throw new Exception("获取/刷新接口调用令牌异常: " . $e->getMessage());
            }
        }
        else{
            Yii::info("$key 授权公众号旧令牌(".date('H:i:s', $access_token_expire).") $access_token" );
        }

        return $access_token;
    }

    /**
     * 获取授权方的帐号基本信息
     *
     * @param [string] $authorizer_appid
     * @return mixed
     *
     * @author Rock <RockYuan@gmail.com>
     * @since 20191022
     */
    public function authorizerInfo($authorizer_appid){
        try{
            $postData = [
                "component_appid" => $this->conf['app_id'],
                "authorizer_appid" => $authorizer_appid,
            ];

            $wxJson = (array) $this->getFromWx(self::API_AUTHORIZER_INFO, $postData);

            if ( empty($wxJson['authorizer_info']) || empty($wxJson['authorization_info']) ){
                Yii::error("接口没返回authorizer_info, authorization_info: " . json_encode( $wxJson, JSON_UNESCAPED_SLASHES ));

                throw new Exception("接口没返回authorizer_info, authorization_info");
            }
            else{
                return $wxJson;
            }
        }
        catch(Exception $e){
            throw new Exception("获取授权方信息异常:" . $e->getMessage());
        }

        return null;
    }

    /**
     * 拉取所有已授权的帐号信息
     *
     * @param integer $offset
     * @param integer $count
     * @return array
     *
     * @author Rock <RockYuan@gmail.com>
     * @since 20191022
     */
    public function authorizerList($offset = 0, $count = 100){
        try{
            $postData = [
                "component_appid" => $this->conf['app_id'],
                "offset" => $offset,
                "count" => $count,
            ];

            $wxJson = (array) $this->getFromWx(self::API_AUTHORIZER_LIST, $postData);

            if ( ! empty($wxJson['list']) ){
                $redis = Yii::$app->redis;
                // 更新到redis
                foreach( $wxJson['list'] as $authorizer ){
                    $key = $this->redisPrefix . $authorizer['authorizer_appid'];

                    $redis->hset($key, 'authorizer_refresh_token', $authorizer['refresh_token']);
                }
            }

            return $wxJson;
        }
        catch(Exception $e){
            throw new Exception("拉取所有已授权的帐号信息异常:" . $e->getMessage());
        }

        return null;
    }
}