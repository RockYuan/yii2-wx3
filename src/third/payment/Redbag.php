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

use Yii;
use rockyuan\wx3\core\Driver;
use yii\httpclient\Client;
use rockyuan\wx3\core\Exception;
use rockyuan\wx3\helpers\Util;

/**
 * Redbag
 * 现金红包接口
 * 
 * @package rockyuan\wx3\third\payment
 * 
 */
class Redbag extends Driver {

    /**
     * 发送普通红包
     * @var
     */
    const API_SEND_NORMAl_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';

    /**
     * 发送裂变红包
     * @var
     */
    const API_SEND_GROUP_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack';

    /**
     * 查询红包列表
     * @var
     */
    const API_QUERY_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';

    /**
     * 发送一个红包
     * @param $params
     * @param $type string 红包类型
     * @throws Exception
     * @return array
     */
    public function send($params,$type = 'normal'){
        $conf = [
            'nonce_str'=>Yii::$app->security->generateRandomString(32),
            'mch_id'=>$this->extra['mch_id'],
            'wxappid'=>$this->extra['appid'],
        ];

        if($type == 'group'){
            $conf['amt_type'] = 'ALL_RAND';
        }else{
            $conf['client_ip'] = Yii::$app->request->userIP;
        }

        $params = array_merge($params,$conf);
        $params['sign'] = Util::makeSign($params,$this->extra['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->extra['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->extra['key_path'],
        ];

        $response = $this->post($type == 'normal' ? self::API_SEND_NORMAl_URL : self::API_SEND_GROUP_URL,$params,[],$options)
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
     * 获取一个红包信息
     * @param $mchBillno string 商户订单号
     * @throws Exception
     * @return object
     */
    public function query($mchBillno){
        $params = [
            'appid'=>$this->extra['appid'],
            'mch_id'=>$this->extra['mch_id'],
            'mch_billno'=>$mchBillno,
            'bill_type'=>'MCHT',
            'nonce_str'=>Yii::$app->security->generateRandomString(32)
        ];
        $params['sign'] = Util::makeSign($params,$this->extra['key']);

        $options = [
            CURLOPT_SSLCERTTYPE=>'PEM',
            CURLOPT_SSLCERT=>$this->extra['cert_path'],
            CURLOPT_SSLKEYTYPE=>'PEM',
            CURLOPT_SSLKEY=>$this->extra['key_path'],
        ];

        $response = $this->post(self::API_QUERY_URL,$params,[],$options)
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
}