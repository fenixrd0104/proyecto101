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
use think\facade\Env;

// +----------------------------------------------------------------------
// | cache settings
// +------------------------------------------------------------ -----------------------

return [
    'default' => 'file',
    'stores' => [
        // file cache
        'file' => [
            // drive method
            'type' => 'file',
            // Set a different cache save directory
            'path' => '../runtime/file/',
        ],
        // redis cache
        'redis' => [
            // drive method
            'type' => 'redis',
            // server address
            'host'       => '127.0.0.1',
        ],
    ],
];
