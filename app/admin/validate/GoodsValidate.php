<?php

namespace app\admin\validate;

use think\Validate;
use think\facade\Config;

class GoodsValidate extends Validate
{
    // validation rules
    protected $rule = [
        'goods_name'=>'require',
        'cat_id'=> 'number|gt:0',
        'top_cate'=>'require',
        'goods_sn'=> 'unique:goods',
        'shop_price'=>'require|regex:\d{1,10}(\.\d{1,2})?$',
        'market_price'=>'require|regex:\d{1,10}(\.\d{1,2})?$|checkMarketPrice:thinkphp',
        'weight'=>'regex:\d{1,10}(\.\d{1,2})?$',
        //'supplier_id'=>'require',
        'shop_id'=>'number|gt:0',
        'exchange_integral'=>'checkExchangeIntegral:thinkphp',
        'original_img'=>'require',
    ];


    protected $message  =   [
        'goods_name.require' => 'goods name is required',
        'goods_name.unique' => 'goods name is repeated',
        'cat_id.number' => 'Commodity classification must be filled in',
        'cat_id.gt' => 'Commodity classification must be selected',
        'top_cate' => 'module classification must be selected',
        'goods_sn.unique' => 'goods item number is repeated',
        'shop_price.require' => 'The price format of this shop is wrong',
        'shop_price.regex' => 'The price format of this shop is wrong',
        'market_price.require' => 'market price is required',
        'market_price.regex' => 'The market price is wrong',
        'market_price.checkMarketPrice' => 'The market price must not be less than our store price',
        'weight.regex' => 'weight format is incorrect',
        //'supplier_id.require' => 'supplier name is required',
        'shop_id.gt' => 'The store cannot be empty',
        'exchange_integral.checkExchangeIntegral' => 'The amount of points deducted cannot exceed the total amount of goods',
        'original_img.require' => 'image must be uploaded',
    ];

    /**
     * Check point redemption
     * @author dyr
     * @return bool
     */
    protected function checkExchangeIntegral($value, $rule)
    {
        $exchange_integral = $value;
        $shop_price = input('shop_price', 0);
        //Point conversion ratio
        $point_rate_value = Config::get('config.point_rate');
        $point_rate_value = $point_rate_value ? $point_rate_value : 100;
        if ($exchange_integral > ($shop_price * $point_rate_value)) {
            return 'The deductible amount of points cannot exceed the total amount of goods';
        } else {
            return true;
        }
    }

    /**
     * Check market price
     * @param $value
     * @param $rule
     * @param $data
     * @return bool
     */
    protected function  checkMarketPrice($value,$rule,$data){
        if($value < $data['shop_price']){
            return false;
        }else{
            return true;
        }
    }
}