<?php

namespace app\api\validate;

use think\Validate;
use think\Config;

class MerchantValidate extends Validate
{
    // validation rules
    protected $rule = [
        'name'=>'require',
        'province'=> 'require',
        'city'=> 'require',
        'area'=> 'require',
        'phone'=> 'require',
        'address'=> 'require',
        'email'=> 'require|email',
        'zz_image'=> 'require',
        'vaild_time'=> 'require',
        'type'=> 'require',
        'industry_id'=> 'require',
        'company_number'=> 'require',
    ];
    protected $message  =   [
        'name.require' => 'Store type name cannot be empty',
        'email.email' => 'Mailbox format error',
        'province.require' => 'province cannot be empty',
        'city.require' => 'City cannot be empty',
        'area.require' => 'The district/county cannot be empty',
        'phone.require' => 'Contact phone number cannot be empty',
        'address.require' => 'The detailed address cannot be empty',
        'email.require' => 'The mailbox cannot be empty',
        'zz_image.require' => 'The license image cannot be empty',
        'vaild_time.require' => 'valid time cannot be empty',
        'type.require' => 'The store type cannot be empty',
        'industry_id.require' => 'Industry type cannot be empty',
        'company_number.require' => 'The registration number of the business license cannot be empty',
    ];

}