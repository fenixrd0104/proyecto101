<?php

namespace app\admin\validate;
use think\Validate;

class UserGradeValidate extends Validate
{
    protected $rule = [
		['name','require','level name must be filled in'],
        ['portrait','require','Grade pictures must be uploaded'],
        ['max','require','Maximum value must be filled in'],
        ['min','require','minimum must be filled in'],
    ];

}