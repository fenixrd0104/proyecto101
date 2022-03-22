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
// | session settings
// +------------------------------------------------------------ -----------------------

return [
    // session name
    'name' => '',
    //Submit variable of SESSION_ID, solve flash upload cross-domain
    'var_session_id' => '',
    // Drive mode supports file redis memcache memcached
    'type' => 'file',
    // Expiration
    'expire' => 7200,
    // prefix
    'prefix' => 'merchant',
];
