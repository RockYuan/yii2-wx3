<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\mini\payment;

use rockyuan\wx3\core\Exception;
use rockyuan\wx3\helpers\Util;

/**
 * Notify API.
 * @package abei2017\mini\pay
 * @author abei<abei@nai8.me>
 */
class Notify {

    /**
     * @var $notify
     */
    protected $notify;

    /**
     * @var
     */
    protected $merchant;

    /**
     * @var boolean | array
     */
    protected $data = false;

    public function __construct($merchant){
        $this->merchant = $merchant;
    }

    public function getData(){
        if($this->data){
            return $this->data;
        }

        $xml = @$GLOBALS['HTTP_RAW_POST_DATA'];
        if(!$xml){
        	$xml = file_get_contents("php://input");
        }
        $xmlArray = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $this->data = $xmlArray;
    }

    /**
     * 检测签名
     * @author abei<abei@nai8.me>
     */
    public function checkSign(){
        if($this->data == false){
            $this->getData();
        }

        $sign = Util::makeSign($this->data,$this->merchant['key']);
        if($this->GetSign() == $sign){
            return true;
        }
        throw new Exception("签名错误！");
    }

    public function GetSign(){
        return $this->data['sign'];
    }

}