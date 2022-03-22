<?php

namespace app\merchant\validate;

use think\Validate;

class GoodsAttributeValidate extends Validate
{
    // validation rules
    protected $rule = [
        'name|Attribute name'=>'require',
       'type_id|type'=> 'require',
        'input_type|input method'=> 'require',
        'input_type'=>'checkValues:thinkphp'
    ];
    protected function checkValues($input_type,$rule)
    {
        if((trim(input('values') == '')) && ($input_type == '1'))
            return false;
        else
            return true;
    }
}