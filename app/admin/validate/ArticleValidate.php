<?php
namespace app\admin\validate;
use think\Validate;

class ArticleValidate extends Validate
{
    protected $rule = [
       'title|title' => 'require',
       'cate_id|category' => 'require|number',

    ];

}