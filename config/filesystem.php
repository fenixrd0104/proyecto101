<?php

use think\facade\Env;

return [
    'default' => Env::get('filesystem.driver', 'local'),
    'disks'   => [
        'local'  => [
            'driver' => 'local',
            'root'   => app()->getRuntimePath() . 'storage',
        ],
        'public' => [
            'driver'     => 'local',
            'root'       => app()->getRootPath() . 'public_html',
            'url'        => '/',
            'visibility' => 'public', 
        ],
        // More disk configuration information
    ],
];
