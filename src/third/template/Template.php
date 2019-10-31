<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\template;

use rockyuan\wx3\core\Driver;
use rockyuan\wx3\core\Exception;
use yii\httpclient\Client;
use rockyuan\wx3\third\core\Authorization;

/**
 * 模板消息助手
 * @package rockyuan\wx3\third\template
 */
class Template extends Driver {

    private $accessToken;

    const API_SEND_TEMPLATE_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';

    public function init(){
        parent::init();
        $this->accessToken = (new Authorization(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getAuthToken($this->extra['appid']);
    }

    /**
     * 发送一个模板消息
     */
    public function send($openId,$templateId,$url,$data){
        $formatData = [];
        foreach($data as $key=>$val){
            if(is_string($val)){
                $formatData[$key] = ['value'=>$val,'color'=>'#4D4D4D'];
            }elseif (is_array($val)){
                if(isset($val['value'])){
                    $formatData[$key] = $val;
                }else{
                    $formatData[$key] = ['value'=>$val[0],'color'=>$val[1]];
                }
            }
        }

        $params = [
            'touser'=>$openId,
            'template_id'=>$templateId,
            'url'=>$url,
            'data'=>$formatData
        ];

        $response = $this->post(self::API_SEND_TEMPLATE_URL."?access_token=".$this->accessToken,$params)
            ->setFormat(Client::FORMAT_JSON)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $data = $response->setFormat(Client::FORMAT_JSON)->getData();

        if(isset($data['errcode']) && $data['errcode'] != 0){
            throw new Exception($data['errmsg'],$data['errcode']);
        }

        return $data['msgid'];
    }

}