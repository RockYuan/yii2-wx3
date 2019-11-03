<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\payment;

use rockyuan\wx3\core\Exception;
use Yii;
use rockyuan\wx3\core\Driver;
use yii\httpclient\Client;
use rockyuan\wx3\helpers\Util;

/**
 * Mch
 * 企业付款接口
 *
 * @package rockyuan\wx3\third\payment
 */
class Mch extends Driver {

    const API_SEND_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';

    /**
     * 发送企业付款到零钱包
     *
     * @param $params array 付款参数（必填参数为partner_trade_no、openid、amount、desc、check_name）
     * @throws Exception
     * @return array
     */
    public function send($params = []){
        $conf = [
            'mch_appid'=>$this->extra['appid'],
            'mchid'=>$this->extra['mch_id'],
            'spbill_create_ip'=>Yii::$app->request->userIP,
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params = array_merge($params,$conf);
        $params['sign'] = Util::makeSign($params,$this->extra['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->extra['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->extra['key_path'],
        ];

        $response = $this->post(self::API_SEND_URL,$params,[],$options)
            ->setFormat(Client::FORMAT_XML)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_XML);
        $result = $response->getData();

        if($result['return_code'] == 'FAIL'){
            throw new Exception($result['return_msg']);
        }

        if($result['result_code'] == 'FAIL'){
            throw new Exception($result['err_code']."#".$result['err_code_des']);
        }

        return $result;
    }

    /**
     * 查询企业付款
     * 只支持查询30天内的订单，30天之前的订单请登录商户平台查询。
     *
     * @param $partnerTradeNo string 商户订单号
     * @return array
     * @throws Exception
     */
    public function query($partnerTradeNo){
        $params = [
            'appid'=>$this->extra['appid'],
            'mch_id'=>$this->extra['mch_id'],
            'partner_trade_no'=>$partnerTradeNo,
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params['sign'] = Util::makeSign($params,$this->extra['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->extra['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->extra['key_path'],
        ];

        $response = $this->post(self::API_QUERY_URL,$params,[],$options)->setFormat(Client::FORMAT_XML)->send();

        if($response->isOk == false){
            throw new Exception(self::ERROR_NO_RESPONSE);
        }

        $response->setFormat(Client::FORMAT_XML);
        $result = $response->getData();

        if($result['return_code'] == 'FAIL'){
            throw new Exception($result['return_msg']);
        }

        if($result['result_code'] == 'FAIL'){
            throw new Exception($result['err_code']."#".$result['err_code_des']);
        }

        return $result;
    }
}