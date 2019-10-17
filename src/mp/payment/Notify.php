<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\mp\payment;

use yii\base\Component;
use rockyuan\wx3\helpers\Xml;
use rockyuan\wx3\helpers\Util;
use rockyuan\wx3\core\Exception;

/**
 * Notify
 * 微信支付通知类
 *
 * @author abei<abei@nai8.me>
 * @link http://nai8.me/yii2wx
 * @package rockyuan\wx3\mp\payment
 */
class Notify extends Component {

    /**
     * 收到的通知（数组形式）
     * @var
     */
    protected $notify;

    public $merchant;

    protected $data = false;

    public function getData(){
        if($this->data){
            return $this->data;
        }

        return $this->data = Xml::parse(file_get_contents('php://input'));
    }

    public function checkSign(){
        if($this->data == false){
            $this->getData();
        }

        $sign = Util::makeSign($this->data,$this->merchant['key']);
        if($sign != $this->data['sign']){
            throw new Exception("签名错误！");
        }

        return true;
    }
}