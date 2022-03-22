<?php

namespace app\api\validate;

use think\Validate;

class WithdrawalValidate extends Validate
{
    protected $rule = [
        'money|balance limit'=>'require|>:0',
        'verify'=>'require',
        'password'=>'require',
        'tx_money|Withdrawal amount'=>'require|checkeds',
    ];
    protected function checkeds($value, $rule ,$data)
    {
        return ($value > $data['money']) ? false : true;
    }
   /* protected function checkServies($value, $rule ,$data)
    {
        return ($value <= $data['service_charge']) ? false : true;
    }*/

    protected $message = [
        'money.require' => 'The balance amount cannot be empty',
        'money>:0' => 'The balance is greater than 0',
        'verify.require' => 'Verification code cannot be empty',
        'password.require' => 'Payment password cannot be empty',
        'tx_money.require' => 'The withdrawal amount cannot be empty',
        'tx_money.checkeds' => 'Insufficient balance',
        'password.checkdeng' => 'payment password error',
    ];

}