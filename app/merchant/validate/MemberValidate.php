<?php

namespace app\merchant\validate;
use think\Validate;

class MemberValidate extends Validate
{
    protected $rule = [
        'account|member account'=> 'unique:member'
    ];

}