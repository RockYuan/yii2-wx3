<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\mini\template;

use rockyuan\wx3\core\Driver;
use Yii;
use yii\httpclient\Client;

/**
 * Template
 * 小程序模板消息
 * @author abei<abei@nai8.me>
 * @link https://nai8.me/yii2wx
 * @package rockyuan\wx3\mini\template
 */
class Template extends Driver {

    const API_SEND_TMPL = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=';

    /**
     * 发送模板消息
     *
     * @param $toUser
     * @param $templateId
     * @param $formId
     * @param $data
     * @param array $extra
     */
    public function send($toUser,$templateId,$formId,$data,$extra = []){
        $params = array_merge([
            'touser'=>$toUser,
            'template_id'=>$templateId,
            'form_id'=>$formId,
            'data'=>$data,
        ],$extra);
        $response = $this->post(self::API_SEND_TMPL.$this->accessToken->getToken(),$params)->setFormat(Client::FORMAT_JSON)->send();

        return $response->getContent();
    }

    
}