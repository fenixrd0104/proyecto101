<?php

namespace app\admin\validate;

use think\Validate;

class AdminAddrValidate extends Validate
{
    protected $rule = [
        'username|admin'=> 'unique:admin',
        'password|password'=> 'require',
       // 'groupid|group'=> 'require',
    ];

}