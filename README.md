# Yii2-wx3
## 支持微信公众平台-第三方平台
[微信官方说明](https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/Third_party_platform_appid.html)

## 当前状态
✓ 授权服务号操作类

? 授权小程序操作类 @todo

## 配置和使用

### 安装
`composer require "rockyuan/yii2-wx3"`

### 配置数组 

```php
<?php
return [
    'params' =>[
        'wx'=>[
            // 第三方平台
            'third' => [
                // 现网第三方
                'app_id'  => 'wx...', // AppID
                'secret'  => '...', // AppSecret
                'token'   => '...', // Token
                'encodingAESKey'=>'...', // 消息加解密密钥
                'safeMode'=>2, //2-安全, 第三方平台只可以设为安全

                'jssdk' => [
                    'chooseImage', 'previewImage',
                ],
            ],
            //  默认公众号(保留用于处理无授权公众号操作)
            'mp'=>[
                'app_id'  => 'wx...',         // AppID
                'secret'  => '',     // AppSecret
                'token'   => '',          // Token
                'encodingAESKey'=>'',// 消息加解密密钥,该选项需要和公众号后台设置保持一直
                'safeMode'=>2,//0-明文 1-兼容 2-安全，该选项需要和公众号后台设置保持一直
        
                'payment'=>[
                    'mch_id'        =>  '',
                    'key'           =>  '',
                    'notify_url'    =>  'https://',
                    'cert_path'     => '/cert/apiclient_cert.pem', // XXX: 绝对路径！！！！
                    'key_path'      => '/cert/apiclient_key.pem',      // XXX: 绝对路径！！！！
                ],
        
                'oauth' => [
                    'scopes'   => 'snsapi_base',
                    'callback' => 'https://',
                ],

                'jssdk' => [
                    'chooseImage', 'previewImage'
                ],
            ],
            //  小程序配置 (保留用于处理无授权小程序操作)
            'mini'=>[
                //  基本配置
                'app_id'  => '', 
                'secret'  => '',
                'token' => '',
                'safeMode'=>0,
                'encodingAESKey'=>'',
                //  微信支付
                'payment' => [
                    'mch_id'        => '',
                    'key'           => '',
                ],
            ],
        ],
    ]
];
```

## 使用

### 默认公众号
基本沿用abei2017/yii2-wx的使用方法, 小部分作修改

```php
use rockyuan\wx3\Wx3App;

//  方法一
$qrcode = (new Wx3App())->driver('mp.qrcode');

//  方法二 (指定自定义配置参数)
$conf = Yii::$app->params['wechat']; // 自定义配置数组key（最后一层数组key不可以更改）
$this->wx3 = new Wx3App(['conf'=>$conf]);

$qrcode = $this->wx3->driver('mp.qrcode');

// 对象操作方法
$data = $qrcode->intTemp(9527); // 生成一个数字类临时关注二维码，省缺第二参数时默认有效期为2592000秒
```

### 第三方平台授权公众号
#### 接收第三方平台的定时票据 (第三方平台的启动基础)
```php
$server = $this->wx3->driver("third.server");

$server->setMessageHandler(function($message) {
    ... // 参考普通公众号开发的消息处理
}
```
__其他流程参考官方第三方平台文档要求__

#### 授权公众号的操作类(对比普通公众号)
```php
if ( !empty($appid) ){
    // 授权公众号创建关注二维码(传送授权公众号的appid))
    $qrcode = $this->wx3->driver("third.qrcode", ['appid' => $appid]);
}
else{
    // 普通公众号创建关注二维码
    $qrcode = $this->wx3->driver("mp.qrcode");
}

$qr = $qrcode->temp($code);
```

#### 更多... @todo

## 感谢
[abei2017/yii2-wx](https://github.com/abei2017/yii2-wx)

## License
[MIT](./LICENSE)