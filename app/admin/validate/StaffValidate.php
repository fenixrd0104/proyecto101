<?php

namespace app\admin\validate;

use think\Validate;

class StaffValidate extends Validate
{
    protected $rule = [
        'account|account' => 'require|unique:member',
        'shop_id|The store' => 'require',
        'reusername|real name' => 'require',
    ];
}