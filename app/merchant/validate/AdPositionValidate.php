<?php

namespace app\merchant\validate;

use think\Validate;

class AdPositionValidate extends Validate
{
    protected $rule = [
       'name|Ad slot name' => 'require',
       'orderby|sort' => 'require',
    ];

}