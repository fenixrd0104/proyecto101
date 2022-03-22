<?php

namespace app\admin\validate;

use think\Validate;
use think\Config;

class WmenuVaildate extends Validate
{
// validation rules
    protected $rule = [
        'name'=>'require',
        'key_value'=> 'require',

    ];


    protected $message = [
        'name.require' => 'Menu name is required',
        'key_value.require' => 'value is required',

    ];

}