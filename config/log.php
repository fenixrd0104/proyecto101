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
// | log settings
// +------------------------------------------------------------ -----------------------
return [
    // default logging channel
    'default' => 'file',
    // logging level
    'level' => [],
    // Channel logged by log type ['error'=>'email',...]
    'type_channel' => [],

    // log channel list
    'channels' => [
        'file' => [
            // logging method
            'type' => 'File',
            // log save directory
            'path' => '',
            // single file log write
            'single' => false,
            // independent log level
            'apart_level' => [],
            // maximum number of log files
            'max_files' => 0,
        ],
        // Other log channel configuration
    ],
];
