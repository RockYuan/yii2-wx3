<?php

/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\menu;

use rockyuan\wx3\core\Driver;
use rockyuan\wx3\third\core\Authorization;
use yii\httpclient\Client;
use rockyuan\wx3\core\Exception;

/**
 * Menu
 * 微信公众号菜单助手
 *
 * @package rockyuan\wx3\third\menu
 */
class Menu extends Driver {

    private $accessToken;

    const API_MENU_GET_URL = 'https://api.weixin.qq.com/cgi-bin/menu/get';
    const API_MENU_CREATE_URL = 'https://api.weixin.qq.com/cgi-bin/menu/create';

    public function init(){
        parent::init();
        $this->accessToken = (new Authorization(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getAuthToken($this->extra['appid']);
    }

    /**
     * 获得当前菜单列表
     *
     * @throws Exception
     * @return mixed
     */
    public function ls(){
        $response = $this->get(self::API_MENU_GET_URL."?access_token=".$this->accessToken)->send();

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

    /**
     * 生成菜单
     *
     * @throws Exception
     * @param $buttons array 菜单数据
     * @return boolean
     */
    public function create($buttons = []){
        $this->httpClient->formatters = ['uncodeJson'=>'rockyuan\wx3\helpers\JsonFormatter'];
        $request = $this->post(self::API_MENU_CREATE_URL."?access_token=".$this->accessToken,$buttons)
            ->setFormat('uncodeJson');

        $response = $request->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_JSON);
        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg'], $data['errcode']);
        }

        return true;
    }
}