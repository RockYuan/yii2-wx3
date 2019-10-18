<?php

/*
 * This file is part of the rockyuan/yii2-wx3.
 *
 * 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace rockyuan\wx3;

use rockyuan\wx3\core\Exception;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;

/**
 * bootstrap
 * 此类负责模块其他类的驱动以及相关变量的初始化
 *
 * @author RockYuan
 * @package rockyuan\wx3
 */
class Wx3App extends Component {

    /**
     * yii2-wx配置
     * @var
     */
    public $conf = [];

    /**
     * http客户端
     * @var
     */
    public $httpClient;

    public $httpConf = [
        'transport' => 'yii\httpclient\CurlTransport',
    ];

    /**
     * 类映射
     * @var array
     */
    public $classMap = [
        'core'=>[
            'accessToken'=>'rockyuan\wx3\core\AccessToken'
        ],

        'third'=>[
            'accessToken'=>'rockyuan\wx3\third\core\AccessToken',
            'server'=>'rockyuan\wx3\third\server\Server',    // 服务接口
        ],

        'mp'=>[
            'accessToken'=>'rockyuan\wx3\mp\core\AccessToken',
            'base'=>'rockyuan\wx3\mp\core\Base',    // 二维码
            'qrcode'=>'rockyuan\wx3\mp\qrcode\Qrcode',    // 二维码
            'shorturl'=>'rockyuan\wx3\mp\qrcode\Shorturl',    // 短地址
            'server'=>'rockyuan\wx3\mp\server\Server',    // 服务接口
            'remark'=>'rockyuan\wx3\mp\user\Remark',  //  会员备注
            'user'=>'rockyuan\wx3\mp\user\User',  //  会员管理
            'tag'=>'rockyuan\wx3\mp\user\Tag',    //  会员标签
            'menu'=>'rockyuan\wx3\mp\menu\Menu',  // 菜单
            'js'=>'rockyuan\wx3\mp\js\Js',    //  JS
            'template'=>'rockyuan\wx3\mp\template\Template', //   消息模板
            'pay'=>'rockyuan\wx3\mp\payment\Pay',//  支付接口
            'mch'=>'rockyuan\wx3\mp\payment\Mch',//  企业付款
            'redbag'=>'rockyuan\wx3\mp\payment\Redbag',//  红包
            'oauth'=>'rockyuan\wx3\mp\oauth\OAuth',//  web授权
            'resource'=>'rockyuan\wx3\mp\resource\Resource',//  素材
            'kf'=>'rockyuan\wx3\mp\kf\Kf',//  客服
            'customService'=>'rockyuan\wx3\mp\kf\CustomService',//  群发
        ],

        'mini'=>[
            'accessToken'=>'rockyuan\wx3\mini\core\AccessToken',
            'user'=>'rockyuan\wx3\mini\user\User', // 会员
            'pay'=>'rockyuan\wx3\mini\payment\Pay', // 支付
            'qrcode'=>'rockyuan\wx3\mini\qrcode\Qrcode', // 二维码&小程序码
            'template'=>'rockyuan\wx3\mini\template\Template', // 模板消息
            'custom'=>'rockyuan\wx3\mini\custom\Customer',
            'server'=>'rockyuan\wx3\mini\custom\Server',
        ]

    ];

    public function init(){
        parent::init();
        $this->httpClient = new Client($this->httpConf);
    }

    /**
     * 驱动函数
     * 此函数主要负责生成相关类的实例化对象并传递相关参数
     *
     * @param $api string 类的映射名
     * @param array $extra  附加参数
     * @throws Exception
     * @return object
     */
    public function driver($api,$extra = []){

        $api = explode('.',$api);
        if(empty($api) OR isset($this->classMap[$api[0]][$api[1]]) == false){
            throw new Exception('很抱歉，你输入的API不合法。');
        }

        //  初始化conf
        if(empty($this->conf)){
            if(isset(Yii::$app->params['wx']) == false){
                throw new Exception('请在yii2的配置文件中设置配置项wx');
            }

            if(isset(Yii::$app->params['wx'][$api[0]]) == false){
                throw new Exception("请在yii2的配置文件中设置配置项wx[{$api[0]}]");
            }

            $this->conf = Yii::$app->params['wx'][$api[0]];
        }

        $config = [
            'conf'=>$this->conf,
            'httpClient'=>$this->httpClient,
            'extra'=>$extra,
        ];

        $config['class'] = $this->classMap[$api[0]][$api[1]];

        return Yii::createObject($config);
    }
}