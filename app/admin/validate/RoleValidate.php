<?php

namespace app\admin\validate;
use think\Validate;

class RoleValidate extends Validate
{
    protected $rule = [
        'title|Role'=> 'unique:auth_group'
    ];

}