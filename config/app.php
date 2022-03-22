<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | App settings
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // application address
    'app_host' => Env::get('app.host', ''),
    // application namespace
    'app_namespace' => '',
    // whether to enable routing
    'with_route' => true,
    // whether to enable events
    'with_event' => true,
    // Application mapping (automatic multi-application mode is valid)
    'app_map' => [],
    // Domain name binding (valid for automatic multi-application mode)
    'domain_bind' => [
       // 'admin' => 'admin', // blog subdomain is bound to blog application
       // 'shop.tp.com' => 'shop', // full domain name binding
      // '*' => 'home', // The second-level generic domain name is bound to the home application
    ],
    // List of applications that prohibit URL access (automatic multi-application mode is valid)
    'deny_app_list' => ['common'],
    // default application
    'default_app' => 'wap',
    // default timezone
    'default_timezone' => 'Asia/Shanghai',

    // Template file for exception page
    'exception_tmpl' => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // Error display information, valid in non-debug mode
    'error_message' => 'Operation or page error! Please try again later~',
    // show error message
    'show_error_msg' => false,


    // +------------------------------------------------------------ -----------------------
    // | Payment callback settings
    // +------------------------------------------------------------ -----------------------
    'alipay'=>[
        'use_sandbox' => false,// Whether to use sandbox mode
        'sign_type' => 'RSA2', // RSA RSA2
        'limit_pay' => [
            //'balance',// balance
            //'moneyFund',// Yu'e Bao
            //'debitCardExpress',// Debit Card Express
            //'creditCard',//credit card
            //'creditCardExpress',// Credit Card Express
            //'creditCardCartoon',//credit card cartoon
            //'credit_group',// Credit payment type (including credit card cartoon, credit card shortcut, Huabei, Huabei installment)
        ],// The user cannot pay with the specified channel. When there are multiple channels, separate them with ","
        'notify_url'=>['api/snotify/index',['type'=>'ali_charge']],
        'return_url'=>['api/snotify/return_url',['type'=>'ali_charge']],
        'return_raw'=>true,
    ],





    'auth_key'   => 'JUD6FCtZsqrmVXc2apev4TRn3O8gAhxbSlH9wfPN',
    'redis_host'=>'127.0.0.1',
    'redis_port'=>'6379',
    'swoole_server_ip'=>'127.0.0.1',
    'swoole_server_port'=>'9502',
    'TRADE_MATCH_FAKE'=>[],


];
