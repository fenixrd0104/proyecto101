<?php

namespace app\merchant\validate;

use think\Validate;

class OrderValidate extends Validate
{
    // validation rules
    protected $rule = [
        ['consignee','require','required by consignee'],
        ['mobile','require|number','Mobile phone number required|Mobile phone number format is incorrect'],
        ['province', 'require|gt:0', 'Address must be selected|Address must be selected'],
        ['address', 'require', 'Address must be filled in'],
        ['goods_list', 'require', 'goods must be selected']
    ];
}