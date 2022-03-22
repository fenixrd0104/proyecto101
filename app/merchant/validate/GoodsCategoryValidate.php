<?php

namespace app\merchant\validate;

use think\Validate;

class GoodsCategoryValidate extends Validate
{
    // validation rules
    protected $rule = [
       'name|Classification name'=>'require',
       'mobile_name|wap category name'=>'require'
    ];
}