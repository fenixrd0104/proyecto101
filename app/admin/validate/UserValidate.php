<?php

namespace app\admin\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'username|administrator'=>'unique:admin',
    ];

}