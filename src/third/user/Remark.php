<?php
/*
 * This file is part of the rockyuan/yii2-wx3
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3\third\user;

use rockyuan\wx3\core\Driver;
use rockyuan\wx3\core\Exception;
use rockyuan\wx3\third\core\Authorization;

/**
 * 备注助手
 * 
 * @package rockyuan\wx3\third\user
 */
class Remark extends Driver {

    const API_UPDATE_REMARK_URL = "https://api.weixin.qq.com/cgi-bin/user/info/updateremark";

    /**
     * @var bool 接口令牌
     */
    private $accessToken = false;

    public function init(){
        parent::init();

        $this->accessToken = (new Authorization(['conf'=>$this->conf,'httpClient'=>$this->httpClient]))->getAuthToken($this->extra['appid']);
    }

    /**
     * 给一个用户打备注
     *
     * @param $openId
     * @param $remark
     * @return bool
     * @throws Exception
     */
    public function update($openId,$remark){
        $this->httpClient->formatters = ['uncodeJson'=>'rockyuan\wx3\helpers\JsonFormatter'];
        $response = $this->post(self::API_UPDATE_REMARK_URL."?access_token={$this->accessToken}",['openid'=>$openId,'remark'=>$remark])
            ->setFormat('uncodeJson')->send();

        $data = $response->getData();
        if(isset($data['errcode']) && $data['errcode'] == 0){
            return true;
        }else{
            throw new Exception($data['errmsg'],$data['errcode']);
        }
    }

}