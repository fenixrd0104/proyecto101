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
// | Multilingual settings
// +------------------------------------------------------------ -----------------------

use think\facade\Env;

return [
    // default language
    'default_lang' => Env::get('lang.default_lang', 'zh-cn'),
    // list of allowed languages
    'allow_lang_list' => [],
    // Multilingual automatic detection of variable names
    'detect_var' => 'lang',
    // Whether to use cookie record
    'use_cookie' => true,
    // Multilingual cookie variable
    'cookie_var' => 'think_lang',
    // Extended language pack
    'extend_list' => [],
    // Accept-Language is escaped to the corresponding language package name
    'accept_language' => [
        'zh-hans-cn' => 'zh-cn',
    ],
];
