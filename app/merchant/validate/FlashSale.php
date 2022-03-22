<?php
namespace app\merchant\validate;
use think\Validate;
use think\Db;
class FlashSale extends Validate
{
    // validation rules
    protected $rule = [
        ['title', 'require'],
        ['goods_id', 'require'],
        ['price','require|number'],
        ['goods_num','require|number|checkGoodsNum'],
        ['buy_limit','require|number|checkLimit'],
        ['start_time','require'],
        ['end_time','require|checkEndTime'],
    ];
    //error message
    protected $message = [
        'title.require' => 'The title must be snapped up',
        'goods_id.require' => 'Please select the goods to participate in the snap-up',
        'price.require' => 'Please fill in the price of the limited time purchase',
        'price.number' => 'The price of the flash sale must be a number',
        'goods_num.require' => 'Please fill in the number of purchases',
        'goods_num.number' => 'The number of purchases must be a number',
        'goods_num.checkGoodsNum' => 'The number of purchases cannot exceed the inventory of goods',
        'buy_limit.require' => 'Please fill in the purchase limit',
        'buy_limit.number' => 'The purchase limit must be a number',
        'buy_limit.checkLimit' => 'The purchase limit cannot exceed the snap purchase quantity',
        'start_time.require' => 'Please select the start time',
        'end_time.require' => 'Please select the end time',
        'end_time.checkEndTime' => 'The end time cannot be earlier than the start time',
    ];

    /**
     * Check limit quantity
     * @param $value|Validation data
     * @param $rule|Validation rule
     * @param $data|All data
     * @return bool|string
     */
    protected function checkLimit($value, $rule ,$data)
    {
        return ($value > $data['goods_num']) ? false : true;
    }
    /**
     * Check end time
     * @param $value|Validation data
     * @param $rule|Validation rule
     * @param $data|All data
     * @return bool|string
     */
    protected function checkEndTime($value, $rule ,$data)
    {
        return ($value < $data['start_time']) ? false : true;
    }

    /**
     * Check rush quantity
     */
    protected function checkGoodsNum($value, $rule ,$data)
    {
        $store_count = Db::name('goods')->where('id', $data['goods_id'])->value('store_count');
        return ($value <= $store_count) ? true : false;
    }
}