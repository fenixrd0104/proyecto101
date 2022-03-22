<?php

namespace app\merchant\validate;

use think\Validate;

class SupplierValidate extends Validate
{
    protected $rule = [
        'supplier_name|supplier name'=> 'require',
        'supplier_contacts|contacts'=> 'require',
        'supplier_phone|phone'=> 'require|length:11',
    ];

}