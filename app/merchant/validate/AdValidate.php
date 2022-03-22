<?php

namespace app\merchant\validate;

use think\Validate;

class AdValidate extends Validate
{
    protected $rule = [
       'title|title' => 'require',
       'orderby|sort' => 'require',

    ];

}