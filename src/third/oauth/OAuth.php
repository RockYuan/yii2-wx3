<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\oauth;

use rockyuan\wx3\core\Driver;
use Yii;
use yii\httpclient\Client;
use rockyuan\wx3\core\Exception;
use rockyuan\wx3\third\core\Authorization;
use rockyuan\wx3\third\core\ComponentAccessToken;

/**
 * web网页授权
 *
 * @package rockyuan\wx3\third\oauth
 * 
 */
class OAuth extends Driver {

    const API_AUTHORIZE_URL = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=*appid*&redirect_uri=*redirect*&response_type=code&scope=*scope*&state=*state*&component_appid=*component_appid*#wechat_redirect";

    const API_ACCESS_TOKEN_URL = "https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=*appid*&code=*code*&grant_type=authorization_code&component_appid=*component_appid*&component_access_token=*component_access_token*";

    const API_USER_INFO_URL = "https://api.weixin.qq.com/sns/userinfo?access_token=*access_token*&openid=*openid*&lang=zh_CN";

    public $code = false;
    protected $accessToken = false;
    protected $openId = false;
    protected $refreshToken = false;
    protected $expire = false;
    protected $scope = false;

    /**
     * 跳转到授权页面
     */
    public function send(){
        header( "location: " . $this->getUrl() );
    }

    /**
     * getUrl
     * 
     * 获得认证code的url, 用于送回前端, 由前端重定向
     *
     * @return void
     *
     * @author Rock <RockYuan@gmail.com>
     * @since 20190921
     */
    public function getUrl(){

        $search = ['*appid*', '*redirect*', '*scope*', '*state*', '*component_appid*'];

        $replace = [ $this->extra['appid'], $this->extra['redirect'], $this->extra['scope'], $this->extra['state'], $this->conf['app_id'] ];

        $authUrl = str_replace($search, $replace, self::API_AUTHORIZE_URL);

        return $authUrl;
    }

    /**
     * 获得web授权的access token
     * 该方法需要从get参数中获取code来换取。
     * @return bool
     * @throws Exception
     */
    protected function initAccessToken(){
        try{
            if($this->accessToken && time() < $this->expire){
                return $this->accessToken;
            }
    
            $code = $this->getCode();
    
            $search = ['*appid*', '*code*', '*component_appid*', '*component_access_token*'];
    
            $replace = [ $this->extra['appid'], $code, $this->conf['app_id'], (new ComponentAccessToken(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getToken() ];
    
            $url = str_replace($search, $replace, self::API_ACCESS_TOKEN_URL);
    
            $response = $this->get($url)->send();

            if($response->isOk == false){
                throw new Exception(self::ERROR_NO_RESPONSE);
            }
    
            $response->setFormat(Client::FORMAT_JSON);

            $data = $response->getData();

            if(isset($data['errcode']) && $data['errcode'] != 0){
                throw new Exception($data['errmsg'], $data['errcode']);
            }
    
            $data = $response->getData();
    
            $this->accessToken = $data['access_token'];
            $this->openId = $data['openid'];
            $this->refreshToken = $data['refresh_token'];
            $this->expire = (time() + $data['expires_in']);
            $this->scope = $data['scope'];

            Yii::info("授权公众号网页授权令牌(".date('H:i:s', $this->expire).") " . json_encode($data) );

            return true;
        }
        catch(Exception $e){
            Yii::error("授权公众号网页授权异常:" . $e->getMessage());
            throw new Exception("授权公众号网页授权异常:" . $e->getMessage());
        }
    }

    /**
     * 获得web授权的access token和openId
     * @return bool
     */
    public function getOpenId(){
        if($this->openId){
            return $this->openId;
        }

        $this->initAccessToken();

        return $this->openId;
    }

    protected function getCode(){
        if($this->code == false){
            $this->code = Yii::$app->request->get('code');
        }

        return $this->code;
    }

    /**
     * 通过web授权的access_token获得用户信息
     *
     * @return mixed
     * @throws Exception
     */
    public function user(){
        
        $this->initAccessToken();

        $search = ['*access_token*', '*openid*'];
    
        $replace = [ $this->accessToken, $this->openId ];

        $url = str_replace($search, $replace, self::API_USER_INFO_URL);

        $response = $this->get($url)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);

        $data = $response->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg'], $data['errcode']);
        }

        return $data;
    }

}