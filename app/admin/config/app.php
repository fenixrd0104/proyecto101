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

    // +----------------------------------------------------------------------
    // | authconfigure
    // +----------------------------------------------------------------------
        'auth_config' => [
        'auth_on' => 1, // permission switch
        'auth_type' => 1, // Authentication method, 1 is real-time authentication; 2 is login authentication.
        'auth_group' => 'think_auth_group', // User group data without prefix table name
        'auth_group_access' => 'think_auth_group_access', // user-user group relationship without prefix table
        'auth_rule' => 'think_auth_rule', // permission rules without prefix table
        'auth_user' => 'think_admin', // user information without prefix table
    ],
];
