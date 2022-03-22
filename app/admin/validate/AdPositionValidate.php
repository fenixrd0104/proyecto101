<?php

namespace app\admin\validate;

use think\Validate;

class AdPositionValidate extends Validate
{
    protected $rule = [
	   'name|Ad slot name' => 'require',
       'orderby|sort' => 'require',
    ];

}