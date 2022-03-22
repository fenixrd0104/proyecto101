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

/**
 * A definition example of the directory structure of the automatically generated application by php think build
 */
return [
    // files that need to be automatically created
    '__file__' => [],
    // need to automatically create the directory
    '__dir__' => ['controller', 'model', 'view'],
    // The controller that needs to be automatically created
    'controller' => ['Index'],
    // Models that need to be automatically created
    'model' => ['User'],
    // Templates that need to be automatically created
    'view'       => ['index/index'],
];
