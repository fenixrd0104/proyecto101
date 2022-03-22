<?php

namespace app\merchant\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
       'account|account' => 'require',
        'password|password' => 'require',
        'code|Verification code' => 'require',

    ];
}