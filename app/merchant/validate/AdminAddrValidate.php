<?php

namespace app\merchant\validate;

use think\Validate;

class AdminAddrValidate extends Validate
{
    protected $rule = [
        'username|admin'=> 'unique:admin',
        'password|password'=> 'require',
       // 'groupid|分组'=> 'require',
    ];

}