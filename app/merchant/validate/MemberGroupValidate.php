<?php

namespace app\merchant\validate;
use think\Validate;

class MemberGroupValidate extends Validate
{
    protected $rule = [
        'group_name|member group'=> 'require|unique:member_group'
    ];

}