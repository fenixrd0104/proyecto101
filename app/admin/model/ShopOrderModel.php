<?php

namespace app\admin\model;
use app\common\service\Settlement;
use think\Model;
use think\facade\Db;

class ShopOrderModel extends Model
{
    protected $name = 'shop_order';
    protected $pk = 'order_id';
    protected $autoWriteTimestamp = true;
    public $orderStatus=[0=>'未提交',1=>'已提交',2=>'已撤销'];
    public $payStatus=[0=>'未付款',1=>'已付款',2=>'已退款'];
    public function getOrderStatusAttr($value)
    {
        return $this->orderStatus[$value];
    }
    public function getPayStatusAttr($value)
    {
        return $this->payStatus[$value];
    }
    public static function onBeforeInsert($user)
    {
        if ($user->sale_uid) {
            $user->sale_account = Db::name('admin')->where(['id'=>$user->sale_uid])->value('username');
        }
        if ($user->user_id) {
            $user->user_account = Db::name('member')->where(['id'=>$user->user_id])->value('account');
        }

    }
    public static function onBeforeUpdate($user)
    {
        if ($user->sale_uid) {
            $user->sale_account = Db::name('admin')->where(['id'=>$user->sale_uid])->value('username');
        }
        if ($user->user_id) {
            $user->user_account = Db::name('member')->where(['id'=>$user->user_id])->value('account');
        }
    }
    public function createOrder($shop_id,$cashier_uid,$cashier_account,$sale_uid,$user_id,$order_amount,$total_amount,$goods_price,$num,$discount,$exchange_integral,$admin_note){
        $this->save([
            'shop_id'=>$shop_id,
            'cashier_uid'=>$cashier_uid,
            'cashier_account'=>$cashier_account,
            'sale_uid'=>$sale_uid,
            'user_id'=>$user_id,
            'order_amount'=>$order_amount,
            'total_amount'=>$total_amount,
            'goods_price'=>$goods_price,
            'num'=>$num,
            'discount'=>$discount,
            'exchange_integral'=>$exchange_integral,
            'admin_note'=>$admin_note,
        ]);

        return $this->order_id;
    }
    public function getPaginate($map,$limit){
        return  $this->field('think_shop_order.*,think_member.account,think_shop_lists.name shop_name')->join('think_member','think_shop_order.user_id=think_member.id','left')->join('think_shop_lists','think_shop_order.shop_id=think_shop_lists.id')->order('think_shop_order.order_id desc')->where($map)->paginate($limit);
    }
    public function getSumArr($map){
        return  $this->field('sum(total_amount) as total_amount,sum(order_amount) as order_amount,sum(num) as num')->join('think_member','think_shop_order.user_id=think_member.id','left')->join('think_shop_lists','think_shop_order.shop_id=think_shop_lists.id')->where($map)->find();
    }
    //付款成功的操作
    public function payOk($order_id,$pay_id,$pay_name){
        //先查看 这个订单 是不是已经支付了
        $order_info =$this->where(['order_id'=>$order_id])->find();
        if($order_info->pay_status !=0){
            return false;
        }
        if($order_info->pay_status == 0){
            $this->where(['order_id'=>$order_id])->update([
                'pay_time'=>time(),
                'pay_status'=>1,
                'pay_id'=>$pay_id,
                'pay_name'=>$pay_name,
            ]);
            //TODO::更新订单日志
            ShopOrderLogModel::operate($order_info['sale_uid'],$order_info['user_id'],$order_id,'收银台付款');
            if($order_info['user_id']){
                //TODO::结算佣金
                $Settlement =  new Settlement(config('config'));
                $Settlement->xiaofei($order_info['user_id'],$order_id,true);
            }
        }

        return true;
    }

}

