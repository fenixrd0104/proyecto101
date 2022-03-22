<?php

namespace app\merchant\validate;

use think\Validate;
use think\Config;

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