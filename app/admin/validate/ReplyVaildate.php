<?php
namespace app\admin\validate;
use think\Validate;
class ReplyVaildate extends Validate
{
    protected $rule = [
	    ['description', 'require', 'description cannot be empty']
    ];
}