<?php

namespace app\api\validate;

use think\Validate;

class RechargeValidate extends Validate
{
    protected $rule = [
        'money|balance limit'=>'require|>:0',
        'verify'=>'require',
        'password'=>'require|checkdeng',
        'cz_money|Recharge amount'=>'require|>:0|checkeds|>=:100|checkbs',
    ];
    protected function checkbs($value, $rule ,$data)
    {
        return (floor($value/100)!= $value/100) ? false : true;
    }
    protected function checkeds($value, $rule ,$data)
    {
        return ($value > $data['money']) ? false : true;
    }
    protected function checkdeng($value, $rule ,$data)
    {
        return ($value != $data['pay_password']) ? false : true;
    }

    protected $message = [
        'cz_money.checkService' => 'The balance limit (including the handling fee) is insufficient',
        'money.require' => 'The balance amount cannot be empty',
        'money.>:0' => 'The balance is greater than 0',
        'cz_money.>:0' => 'The balance is greater than 0',
        'verify.require' => 'Verification code cannot be empty',
        'password.require' => 'Payment password cannot be empty',
        'cz_money.require' => 'The recharge amount cannot be empty',
        'cz_money.checkbs' => 'The recharge amount must be a multiple of 100',
        'cz_money.checkeds' => 'The recharge amount cannot be higher than the balance amount',
        'password.checkdeng' => 'payment password error',
    ];

}