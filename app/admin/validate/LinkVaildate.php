<?php

namespace app\admin\validate;

use think\facade\Validate;
use think\facade\Config;

class LinkVaildate extends Validate
{
// validation rules
    protected $rule = [
        'title'=>'require',
        'link'=>'require',
    ];
    protected $message = [
        'title.require' => 'The link name cannot be empty',
        'link.require' => 'The link address cannot be empty',
    ];

}