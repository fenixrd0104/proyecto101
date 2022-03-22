<?php

namespace app\admin\validate;

use think\Validate;

class WxinVaildate extends Validate
{
    // validation rules
    protected $rule = [
        'rule_name'=>'require',
        'key_word'=> 'require',
        'content'=> 'require',
    ];


    protected $message  =   [
		'rule_name.require' => 'rule name is required',
        'key_word.require' => 'Keywords cannot be empty',
        'content.require' => 'reply content required',

    ];

}