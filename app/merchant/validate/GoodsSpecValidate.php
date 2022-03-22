<?php

namespace app\merchant\validate;

use think\Validate;

class GoodsSpecValidate extends Validate
{
    // validation rules
    protected $rule = [
        'name|Attribute name'=>'require',
        'type_id|Product type'=>'require',
        'items|Specification item'=>'require',
    ];
}