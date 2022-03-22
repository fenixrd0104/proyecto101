<?php

namespace app\admin\validate;

use think\Validate;

class AdminValidate extends Validate
{
    protected $rule = [
		'username|username' => 'require',
        'password|password' => 'require',
        'code|Verification code' => 'require',

    ];
}