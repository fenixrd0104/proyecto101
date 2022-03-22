<?php
/**
 * Order class
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/12
 * Time: 13:11
 */

namespace app\common\model;

use app\admin\model\GoodsStock;
use app\admin\model\GoodsStockLog;
use app\admin\model\MemberModel;
use app\common\model\Price;
use app\common\service\Msg;
use app\common\service\Settlement;

use app\common\model\OrderAction;
use think\Model;
use think\facade\Db;
use think\Config;
use app\common\service\Payment;
use app\common\service\Users;
use app\common\model\MemberWalletLogModel;
class Order extends Model
{
    protected $pk = 'order_id';
    protected $autoWriteTimestamp = false;
    //Get order items
    public function OrderGoods()
    {
        return $this->hasMany('OrderGoods','order_id','order_id');
    }
    /**
     * @notice promotions are not currently available
     * @param $user_id User ID
     * @param $address_id mailing address id
     * @param $invoice_title Invoice Header Default None
     * @param int $coupon_id coupon ID default no TODO
     * @param $car_price commodity price information['postFee'=>'logistics fee','couponFee'=>'coupon','balance'=>'use balance','pointsFee'=>'points offset amount', 'payables'=>'payable amount','goodsFee'=>'goods total','order_prom_id'=>'promotion ID','order_prom_amount'=>'discount amount']
     * @param string $user_note User's order note
     * @param string $pay_name Payment method Full payment, balance, points
     * @return array
     */
    public function addOrder($take_order=1,$user_id, $cartList, $address,$pay_points,$user_money,$content,$order_type = 1, $user_note = '', $invoice_title = '', $coupon_id = 0, $pay_name = '',$daifu=0)
    {
                // group by store
                $cartListGroup=[];
                foreach ($cartList as $k => $val) { //Data merchant grouping
                    if($one_shop = Db::name('shop_lists')->where(['id'=>$val['shop_id'],'status'=>0])->value('name')){
                        return ['status' => 0, 'data' => [], 'msg' => $one_shop .' The shop is closed, the order cannot be submitted'];
                    }
                    $cartListGroup[$val['shop_id']][] = $val;
                }
                $priceModel = new Price();
                $payables = 0;
                $order_user_money = 0;
                foreach ($cartListGroup as $k => $v) {
                    //Calculate item price
                    $goodsPrice = $priceModel->calculate_price($user_id, $v, 0, $address['province'], $address['city'], $address['district'], $pay_points, $user_money);
                    if ($goodsPrice['status'] == 0) {
                        return json(['status' => 0, 'msg' => $goodsPrice['msg']]);
                    }
                    $goodsPrice = $goodsPrice['result'];
                    $car_price = [ 'postFee' => $goodsPrice['shipping_price'],//Logistics fee
                        'couponFee' => 0,//Coupon
                        'pointsFee' => $goodsPrice['integral_money'],//Points offset amount
                        'payables' => $goodsPrice['order_amount'],//payable amount
                        'goodsFee' => $goodsPrice['goods_price'],//The total amount of goods
                        'balance' => $goodsPrice['user_money'],
                        'order_prom_id' => 0,//Promotion ID
                        'order_prom_amount'=> 0//Promo amount
                    ];
                    $order_sn = $this->get_order_sn();
                    //Point amount conversion ratio
                    $point_rate = config('config.point_rate');
                    $point_rate = $point_rate ? $point_rate : 100;
                    empty($user_note)?$user_note=$content:$user_note=$user_note;
                    $data = [
                        'order_sn' => $order_sn, // order number
                        'user_id' => $user_id, // user id
                        'shop_id' => $k, //shop id
                        'top_cate' => $v[0]['top_cate'],//The top category can only be a single product
                        'take_id' => $v[0]['take_id'], //payer
                        'caidan_id' => $v[0]['caidan_id'], //menu id
                        'consignee' => $address['consignee'], // consignee
                        'province' => $address['province'],//'province id',
                        'city' => $address['city'],//'city id',
                        'district' => $address['district'],//'county',
                        'twon' => $address['twon'],// 'street',
                        'address' => $address['address'],//'detailed address',
                        'mobile' => $address['mobile'],//'mobile phone',
                        'zipcode' => $address['zipcode'],//'zipcode',
                        'email'            => $address['email'],//'邮箱',
                        'invoice_title' => $invoice_title, //'Invoice header',
                        'goods_price' => $car_price['goodsFee'],//'goods price',
                        'shipping_price' => $car_price['postFee'],//'shipping price',
                        'user_money' => $car_price['balance'],//Use the balance
                        'coupon_price' => $car_price['couponFee'],//'Use deduction gold',
                        'integral' => ($car_price['pointsFee'] * $point_rate), //'Use points',
                        'integral_money' => $car_price['pointsFee'],//'How much money is earned by using points',
                        'total_amount' => ($car_price['goodsFee'] + $car_price['postFee']),// total order amount
                        'order_amount' => $car_price['payables'],//'payable amount',
						'add_time' => time(), // order time
                        'order_prom_id' => $car_price['order_prom_id'],//'order promotion id',
                        'order_prom_amount'=> $car_price['order_prom_amount'],//'How much is discounted by the order promotion activity',
                        'user_note' => $user_note, // User's order note
                        'pay_name' => $pay_name,//payment method, it may be balance payment or point exchange, other payment methods will be replaced later
                        'create_time' => time(),//Payment method, it may be balance payment or point exchange, other payment methods will be replaced later
                        'update_time' => time(),//payment method, it may be balance payment or point exchange, other payment methods will be replaced later
                    ];
                    $orderList[$k] = $data;
                    $payables += $car_price['payables'];
                    $order_user_money += $payables + $car_price['balance'];
                }

                if($user_money < $order_user_money && $take_order){
                   return ['status' => 0, 'data' => [], 'msg' => 'Insufficient balance, please recharge'];
                }
                // start things
            Db::startTrans();
            try {
                $fee = ['integral'=>0,'user_money'=>0,'order_amount'=>0];
                // save the order
                foreach ($orderList as $k => $v) {
                    $order = $this;
                    $order_res = $order->save($v);
                    $action_info = [
                        'order_id' => $order['order_id'],
                        'shop_id' => $order['shop_id'], // shop id
                        'action_user' => $user_id,
                        'action_note' => 'You have submitted the order, please wait for the system to confirm',
                        'status_desc' => 'Submit order',
                        'log_time'        => time(),
                    ];
                    // save the record
                    Db::name('order_action')->insert($action_info);

                    // save the corresponding item
                    $cartList = $cartListGroup[$order['shop_id']];
                    foreach ($cartList as $kk => $val) {
                        $goods = Db::name('goods')->where("id", $val['goods_id'])->find();
                        $data2['order_id'] = $order['order_id']; // order id
                        $data2['shop_id'] = $order['shop_id']; // shop id
                        $data2['goods_id'] = $val['goods_id']; // item id
                        $data2['goods_name'] = $val['goods_name']; // product name
                        $data2['goods_sn'] = $val['goods_sn']; // item number
                        $data2['goods_num'] = $val['goods_num']; // Purchase quantity
                        $data2['market_price'] = $val['market_price']; // market price
                        $data2['goods_price'] = $val['goods_price']; // item price
                        $data2['spec_key'] = $val['spec_key']; // product specification
                        $data2['spec_key_name'] = $val['spec_key_name']; // Product specification name
                        $data2['member_goods_price'] = $val['member_goods_price']; // member discount price
                        $data2['cost_price'] = $goods['cost_price']; // cost price
                        $data2['give_integral'] = $goods['give_integral'] * $val['goods_num']; // Bonus points for purchases
                        $data2['prom_type'] = $val['prom_type']; // 0 normal order, 1 flash sale, 2 group purchase, 3 promotion
                        $data2['prom_id'] = $val['prom_id']; // event id
                        $data2['sku'] = $val['sku'];
                        $data2['img'] = $val['goods_img']; // image
                        $data2['create_time']        = time();
                        $data2['update_time']        = time();
                        Db::name('order_goods')->insert($data2);
                        if ($val['prom_type'] == 1) {
                            Db::name('flash_sale')->where('id', $val['prom_id'])->inc('order_num', $val['goods_num'])->update();
                        }
                    }

                    if(($order['user_money']+$order['order_amount']) > 0 && $take_order){
                        $this->orderLongevityLire(1,$order['order_id'],$order['shop_id'],$order['user_id'],$order['user_money']+$order['order_amount']);
                    }
                    if($order['integral'] > 0 && $take_order){
                        $this->orderLongevityLire(5,$order['order_id'],$order['shop_id'],$order['user_id'],$order['integral']);
                    }
                    $users = new Users();

                    $orderId[$k] = $order['order_id'];

                    // if ($order['integral'] > 0) {
                    //     $users->userDecIntegral($user_id, $order['integral']);
                    // //IntegralLog::operate($user_id, -$order['integral'], 1, 1,'Deduct points for placing an order',0);
                    // }

                    // deduct balance
                    if ($order['user_money'] > 0 && $take_order) {
                        $users->userDecMoney($user_id, $order['user_money']);
                        //MoneyLog::operate($user_id, -$order['user_money'], 3, 1, 'Order balance deduction');
                    }

                    //Non-paid goods will be rewarded first
                    if($daifu==0){
                        $this->order_give($user_id,$order);// Call the gift method to give the corresponding gift to the person who placed the order
                    }

                    // If the payable amount is 0, it may be balance payment + points + deduction. Here the order payment status directly becomes paid
                    if ($order['order_amount'] <= 0 && $take_order) {
                        $this->update_pay_status($v['order_sn'],['pay_code' => 1]);
                    }
                    if($v['caidan_id'] == 1 || $v['caidan_id']==2){
                        if($v['top_cate']==1) {
                            $users->vip_goods($user_id, $v['caidan_id']);
                        }
                    }
                    // $fee['integral'] += $order['integral'];
                    // $fee['user_money'] += $order['user_money'];
                    // $fee['order_amount'] += $order['order_amount'];
                }
                //delete the order items submitted in the shopping cart
                if($order_type){
                    Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->delete();
                }
                //The red envelope is open and it is a direct purchase product
                if(config('config.open_redbag')==1&&$data['top_cate']==4){
                    // Db::name('member')->where([['id','=',$user_id],['status','=',1]])->inc('zg_rednum',1)->update();
                    Db::name('get_record')->save(['uid'=>$user_id,'type'=>3,'act'=>$user_id,'create_time'=>time(),'update_time'=>time()]);
                }
                // $users = new Users();

                // if ($fee['integral'] > 0) {
                //     $users->userDecIntegral($user_id, $fee['integral']);
                // IntegralLog::operate($user_id, -$fee['integral'], 1, 1,'Deduct points for placing an order',0);
                // }

                // //Deduct balance
                // if ($fee['user_money'] > 0) {
                // $users->userDecMoney($user_id, $fee['user_money']);
                // MoneyLog::operate($user_id, -$fee['user_money'], 3, 1, 'Order balance deduction');
                // }

                // // If the payable amount is 0, it may be balance payment + points + deduction. Here the payment status of the order directly becomes paid
                // if ($fee['order_amount'] <= 0) {
                // $this->update_pay_status($order_sn,['pay_code' => 1]);
                // }
                // //Delete the order items that have been submitted in the shopping cart
                // if($order_type){
                // Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->delete();
                // }

                // transaction submission
                Db::commit();
               return ['status' => 1,'msg' => 'Order submitted successfully','result' => $orderId,'payables'=>$payables]; // Return the new order id
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return ['status' => 0, 'data' => [], 'msg' => $e->getMessage()];
            }

        // }
        // $order_id = Db::name('order')->insertGetId($data);
        // if (!$order_id) {
        //     return ['status' => 0, 'msg' => 'Failed to place order'];
        // }
        // //Record order operation log
        // $action_info = [
        // 'order_id' => $order_id,
        // 'action_user' => $user_id,
        // 'action_note' => 'You have submitted the order, please wait for the system to confirm',
        // 'status_desc' => 'Submit order',
        // 'log_time' => time(),
        // ];
        // Db::name('order_action')->insert($action_info);
        // //Start adding order item information at the same time
        // $cartList = Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->select();

        // foreach ($cartList as $key => $val) {
        //     $goods = Db::name('goods')->where("id", $val['goods_id'])->find();
        //     $data2['order_id']           = $order_id; // 订单id
        // $data2['goods_id'] = $val['goods_id']; // item id
        // $data2['goods_name'] = $val['goods_name']; // item name
        // $data2['goods_sn'] = $val['goods_sn']; // item number
        // $data2['goods_num'] = $val['goods_num']; // Purchase quantity
        // $data2['market_price'] = $val['market_price']; // market price
        // $data2['goods_price'] = $val['goods_price']; // item price
        // $data2['spec_key'] = $val['spec_key']; // product specification
        // $data2['spec_key_name'] = $val['spec_key_name']; // Product specification name
        // $data2['member_goods_price'] = $val['member_goods_price']; // member discount price
        // $data2['cost_price'] = $goods['cost_price']; // cost price
        // $data2['give_integral'] = $goods['give_integral'] * $val['goods_num']; // Bonus points for purchases
        // $data2['prom_type'] = $val['prom_type']; // 0 normal order, 1 flash sale, 2 group purchase, 3 promotion
        // $data2['prom_id'] = $val['prom_id']; // activity id
        // $data2['img'] = $val['goods_img']; // image
        //     Db::name('order_goods')->insert($data2);
        //     if ($val['prom_type'] == 1) {
        //         Db::name('flash_sale')->where('id', $val['prom_id'])->setInc('order_num', $val['goods_num']);
        //     }
        // }

        // $users = new Users();
        // //Modify coupon TODO
        // //Deduct points

        // if ($data['integral'] > 0) {
        // $users->userDecIntegral($user_id, $data['integral']);
        // IntegralLog::operate($user_id, -$data['integral'], 1, 1,'Deduct points for placing an order',0);
        // }

        // //Deduct balance
        // if ($data['user_money'] > 0) {
        // $users->userDecMoney($user_id, $data['user_money']);
        // MoneyLog::operate($user_id, -$data['user_money'], 3, 1, 'Order balance deduction');
        // }

        // // If the payable amount is 0, it may be balance payment + points + deduction. Here the payment status of the order directly becomes paid
        // if ($data['order_amount'] <= 0) {
        // $this->update_pay_status($order_sn,['pay_code' => 1]);
        // }
        // //Delete the order items that have been submitted in the shopping cart
        // Db::name('cart')->where(['user_id' => $user_id, 'selected' => 1])->delete();
        // return ['status' => 1,'msg' => 'Order submitted successfully','result' => $order_id]; // Return the new order id
    }
    /**
     * The order added here is confirmed and paid by default! ! !
     * @param $user_id defaults to anonymous user
     * @param $address receipt information array [consignee,province,city,district,address,mobile]
     * @param $pay_code payment method
     * @param $goods_list list of goods [id:spec=>num]
     * @param string $invoice_title Invoice title
     * @param string $admin_note admin note
     * @return array
     */
    public function adminAddOrder($address, $pay_code, $goods_list, $invoice_title = '', $admin_note = '', $user_id = 0)
    {
        $order_sn = $this->get_order_sn();
        $pay = Db::name('pay_config')->where("id", $pay_code)->find();
        // Calculate the price according to $goods_list
        $price = new Price();
        $price = $price->calculate_admin_price($goods_list, $address);
        $price = $price['result'];
        $data = [
            'order_sn' => $order_sn, // order number
            'user_id' => $user_id, // user id
            'consignee' => $address['consignee'], // consignee
            'province' => $address['province'],//'province id',
            'city' => $address['city'],//'city id',
            'district' => $address['district'],//'county',
            'address' => $address['address'],//'detailed address',
            'mobile' => $address['mobile'],//'mobile phone',
            'invoice_title' => $invoice_title, //'Invoice header',
            'goods_price' => $price['goods_price'],//'goods price',
            'shipping_price' => $price['shipping_price'],//'Logistics price',
            'total_amount' => $price['total_amount'],// total order amount
            'order_amount' => $price['order_amount'],//'payable amount',
            'add_time' => time(), // order time
            'admin_note' => $admin_note ? $admin_note : 'Administrator placed an order', //Administrator placed an order note
            'pay_name' => $pay['name'],//payment method
            'pay_code'         => $pay_code
        ];
        $order_id = Db::name('order')->insertGetId($data);
        if (!$order_id) {
            return ['status' => 0, 'msg' => 'Order failed'];
        }
        //Record order operation log
        $action_info = [
            'order_id' => $order_id,
            'action_user' => 0,
            'action_note' => 'You have submitted the order, please wait for the system to confirm',
            'status_desc' => 'Submit order',
            'log_time' => time(),
        ];
        Db::name('order_action')->insert($action_info);
        foreach ($goods_list as $k => $v) {
            //Determine whether it is a standard product or an ordinary product
            if (strstr($k, ':') === false) {
                $info = Db::name('goods')->where('id', $k)->find();
                $specInfo = [];
                $data2['goods_id'] = $k; // item id
            } else {
                $k = explode(':', $k);
                $info = Db::name('goods')->where('id', $k[0])->find();
                $specInfo = Db::name('spec_goods')->where('goods_id', $k[0])->where('key', $k[1])->find();
                //var_dump($specInfo);die;
                $data2['goods_id'] = $k[0]; // item id
                $img = Db::name('spec_image')->where(['goods_id' => $k[0], 'spec_item_id' => ['in', explode('_', $specInfo['key' ])]])->value('src');
                if ($img) {
                    $specInfo['img'] = $img;
                }
            }
            $data2['order_id'] = $order_id; // order id
            $data2['goods_name'] = $info['goods_name']; // product name
            $data2['goods_sn'] = $info['goods_sn']; // item number
            $data2['goods_num'] = $v; // Purchase quantity
            $data2['market_price'] = $info['market_price']; // market price
           $data2['goods_price'] = isset($specInfo['price']) ? $specInfo['price'] : $info['shop_price']; // commodity price
            $data2['spec_key'] = isset($specInfo['key']) ? $specInfo['key'] : ''; // product specification
            $data2['spec_key_name'] = isset($specInfo['key_name']) ? $specInfo['key_name'] : ''; // product specification name
            $data2['member_goods_price'] = $data2['goods_price']; // member discount price
            $data2['cost_price'] = $info['cost_price']; // cost price
            $data2['give_integral'] = $info['give_integral'] * $v; // Bonus points for purchases
            $data2['prom_type'] = 0;
            $data2['img'] = isset($specInfo['img']) ? $specInfo['img'] : $info['original_img']; //Use pictures to judge pictures
            $data2['create_time']        = time();
            $data2['update_time']        = time();
            Db::name('order_goods')->insert($data2);
        }
        //After adding the order, the default call has been confirmed and the payment method has been
        Db::name('order')->where('order_id', $order_id)->update(['order_status' => 1]);
        $this->logOrder($order_id, 'Administrators background order automatically confirms', 'Confirm', 0);
        $this->update_pay_status($order_sn, ['is_admin'=>1, 'note'=>'automatic payment for administrator background orders']);
        return ['status' => 1,'msg' => 'Order submitted successfully','result' => $order_id]; // Return the new order id
    }
    /**
     * Modify the order without changing the status of the order!
     * @param $user_id defaults to anonymous user
     * @param $address receipt information array [consignee,province,city,district,address,mobile]
     * @param $pay_code payment method
     * @param $goods_list list of goods [id:spec=>num]
     * @param string $invoice_title Invoice title
     * @param string $admin_note admin note
     * @return array
     */
    public function adminUpdateOrder($order_id, $address, $pay_code, $goods_list, $invoice_title = '', $admin_note = '')
    {
        $orderInfo = Db::name('order')->where('order_id', $order_id)->find();
        if ($orderInfo['order_status'] >= 2) {
            return ['status' => 0, 'msg' => 'The order cannot be modified']; // Modify the order
        }
        //$shipping = Db::name('shipping')->where("id", $shipping_code)->find();
        $pay = Db::name('pay_config')->where("id", $pay_code)->find();
        // Calculate the price according to $goods_list
        $price = new Price();
        $price = $price->calculate_admin_price($goods_list, $address);
        $price = $price['result'];
        $update = [
            'consignee' => $address['consignee'], // consignee
            'province' => $address['province'],//'province id',
            'city' => $address['city'],//'city id',
            'district' => $address['district'],//'county',
            'address' => $address['address'],//'detailed address',
            'mobile' => $address['mobile'],//'mobile phone',
            'invoice_title' => $invoice_title, //'Invoice header',
            'goods_price' => $price['goods_price'],//'goods price',
            'shipping_price' => $price['shipping_price'],//'Logistics price',
            'total_amount' => $price['total_amount'],// total order amount
            'order_amount' => $price['order_amount'],//'payable amount',
            'pay_name' => $pay['name'],//payment method
            'pay_code'         => $pay_code
        ];
        Db::name('order')->where('order_id', $order_id)->update($update); //Modify the order
        //Record order operation log
        $action_info = [
            'order_id' => $order_id,
            'action_user' => 0,
            'order_status' => $orderInfo['order_status'],
            'pay_status' => $orderInfo['pay_status'],
            'shipping_status' => $orderInfo['shipping_status'],
            'action_note' => $admin_note ? $admin_note : 'Order modified successfully',
            'status_desc' => 'modify order',
            'log_time'        => time(),
        ];
        Db::name('order_action')->insert($action_info);
        //Delete the associated product and add it again
        Db::name('order_goods')->where('order_id', $order_id)->delete();
        foreach ($goods_list as $k => $v) {
            //Determine whether it is a standard product or an ordinary product
            if (strstr($k, ':') === false) {
                $info = Db::name('goods')->where('id', $k)->find();
                $specInfo = [];
                $data2['goods_id'] = $k; // item id
            } else {
                $k = explode(':', $k);
                $info = Db::name('goods')->where('id', $k[0])->find();
                $specInfo = Db::name('spec_goods')->where('goods_id', $k[0])->where('key', $k[1])->find();
                //var_dump($specInfo);die;
                $data2['goods_id'] = $k[0]; // item id
                $img = Db::name('spec_image')->where(['goods_id' => $k[0], 'spec_item_id' => $specInfo['id']])->value('src');
                if ($img) {
                    $specInfo['img'] = $img;
                }
            }
            $data2['order_id'] = $order_id; // order id
            $data2['goods_name'] = $info['goods_name']; // product name
            $data2['goods_sn'] = $info['goods_sn']; // item number
            $data2['goods_num'] = $v; // Purchase quantity
            $data2['market_price'] = $info['market_price']; // market price
            $data2['goods_price'] = isset($specInfo['price']) ? $specInfo['price'] : $info['shop_price']; // commodity price
            $data2['spec_key'] = isset($specInfo['key']) ? $specInfo['key'] : ''; // product specification
            $data2['spec_key_name'] = isset($specInfo['key_name']) ? $specInfo['key_name'] : ''; // product specification name
            $data2['member_goods_price'] = $data2['goods_price']; // member discount price
            $data2['cost_price'] = $info['cost_price']; // cost price
            $data2['give_integral'] = $info['give_integral'] * $v; // Bonus points for purchases
            $data2['prom_type'] = 0;
            $data2['img'] = isset($specInfo['img']) ? $specInfo['img'] : $info['original_img']; //Use pictures to judge pictures
            Db::name('order_goods')->insert($data2);
        }
        return ['status' => 1,'msg' => 'Modify order']; // Modify order
    }

    /**
     * User cancels order
     * @param $user_id User ID
     * @param $order_id order ID
     * @param string $action_note Action note
     * @return array
     */
    public function cancel_order($user_id, $order_id, $action_note='You cancelled the order')
    {
        $order = Db::name('order')->where(['order_id' => $order_id])->find();
        if (empty($order))
            return ['status' => 0, 'msg' => 'order does not exist', 'result' => ''];
        if ($order['order_status'] == 3) {
            return ['status' => 0, 'msg' => 'The order has been cancelled', 'result'=>''];
        }
        // Confirm that the order cannot be cancelled
        if($order['order_status'] > 0)
            return ['status' => 0, 'msg' => 'Payment status or order status not allowed', 'result' => ''];

        if($order['pay_status'] >0){
            return ['status' => 0, 'msg' => 'paid orders cannot be cancelled', 'result' => ''];
        }
        //Refund the points if there are points
        if ($order['integral']>0) {
            // refund points
            //IntegralLog::setInc($user_id,$order['integral']);
           //IntegralLog::operate($user_id, $order['integral'], 1, 1, 'Order cancelled and returned points', 0);
            //$this->orderLongevityLire(6,$order['order_id'],$order['shop_id'],$user_id,$order['integral']);
            //IntegralLog::operate($user_id, $order['integral'], 2, 1,'Order refund and return points',0);
        }
        if ($order['user_money'] > 0) {
            //refund balance
            //MoneyLog::setInc($user_id,$order['user_money']);
            //MoneyLog::operate($user_id, $order['user_money'], 4, 1, 'Order cancelled and returned balance', 0);
            //$this->orderLongevityLire(3,$order['order_id'],$order['shop_id'],$user_id,$order['user_money']);
            // MoneyLog::operate($user_id, $order['user_money'], 4, 1, 'Order refund return balance');
        }

        //TODO refund coupon etc.
        $row = Db::name('order')->where(['order_id' => $order_id, 'user_id|take_id' => $user_id])->update(['order_status' => 3]);
        $data['order_id']        = $order_id;
        $data['action_user']     = $user_id;
        $data['action_note']     = $action_note;
        $data['order_status']    = 3;
        $data['pay_status']      = $order['pay_status'];
        $data['shipping_status'] = $order['shipping_status'];
        $data['log_time']        = time();
        $data['status_desc'] = 'User canceled order';
        Db::name('order_action')->insert($data);//Order operation record
        if (!$row){
            return ['status' => 0, 'msg' => 'operation failed', 'result' => ''];
        }

        // subtract the sales of the item
        $order_goods = Db::name('order_goods')->where('order_id',$data['order_id'])->select();
        foreach ($order_goods as $k => $v) {
            Db::name('goods')->where('id',$v['goods_id'])->dec('sales_num', $v['goods_num'])->update();
            //decrease stock
            Db::name('goods')->where('id',$v['goods_id'])->inc('spec_num', $v['goods_num'])->update();
            Db::name('spec_goods')->where(['goods_id'=>$v['goods_id'],'spec_key'=>$v['spec_key']])->inc('spec_num')- >update();
        }
        return ['status' => 1, 'msg' => 'operation successful', 'result' => ''];
    }


   //Payment completed Modify the order --> Change the order to a paid order and record the operation record
    public function update_pay_status($order_sn, $ext = [])
    {
        // Convert the order to a paid order
        $order = Db::name('order')->where("order_sn", $order_sn)->find();

        if ($order['pay_status'] == 1) {
            return ['code' => 0, 'msg' => 'The order has been paid'];
        }
        // distinguish pre-sale orders
        if ($order['order_prom_type'] == 4) {
            //TODO
        } else {
            $updata = ['pay_status' => 1,'pay_time' => time()];
            if (isset($ext['pay_code']) && !empty($ext['pay_code'])) {
                $updata['pay_code'] = $ext['pay_code'];
                $updata['pay_name'] = Db::name('pay_config')->where('id', $ext['pay_code'])->value('name');
            }
            $res = Db::name('order')->where("order_sn", $order_sn)->update($updata);
        }

        if ($res !== false) {
            //Determine whether there are snapped up items, and if so, increase the quantity
            $order_goods = Db::name('order_goods')->where('order_id', $order['order_id'])->select();
            foreach ($order_goods as $k => $v) {
                if ($v['prom_type'] == 1) {
                    Db::name('flash_sale')->where('id', $v['prom_id'])->inc('buy_num', $v['goods_num'])->update();
                }
            }
            //TODO:: reduce inventory

            //Record order operation log
            if (isset($ext['is_admin'])) {
                $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : 'Order payment successful';
                $this->logOrder($order['order_id'], $note, 'Successful payment',$ext['is_admin'],1);
            } else {
                $this->logOrder($order['order_id'], 'Order payment successful', 'Payment successful', $order['user_id']);
            }
            //forward news
            if ($order['user_id']) {
                $member = Db::name('member')->where('id', $order['user_id'])->find();
                // //forward news
                // $param = [
                // 'code' => '',
                // 'content'=>$member['nickname'].'Your order:'.$order_sn.'Payment completed'
                // ];
                // if($member['mobile']) {
                //     $info = \app\common\service\Msg::send_sms(1,$member['country_code'].$member['mobile'],$param);
                // }
            }
           return ['code' => 1, 'msg' => 'Order payment successful'];
        }
        return ['code' => 0, 'msg' => 'Order payment failed'];
    }

    //Payment cancellation Modify the order --> Change the order to an unpaid order and record the operation record
    public function update_paycancel_status($order_sn, $ext = [])
    {
        // Convert the order to a paid order
        $order = Db::name('order')->where("order_sn", $order_sn)->find();
        if ($order['pay_status'] == 0) {
            return ['code' => 0, 'msg' => 'The order has not been paid'];
        }
        // distinguish pre-sale orders
        if ($order['order_prom_type'] == 4) {
            //TODO
        } else {
            $updata = ['pay_status' => 0];
            $res = Db::name('order')->where("order_sn", $order_sn)->update($updata);
        }
        if ($res !== false) {
            //TODO::Restore inventory

            //Record order operation log
            if (isset($ext['is_admin'])) {
                $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : 'Order cancelled and payment successful';
                $this->logOrder($order['order_id'], $note, 'Payment Cancelled', $ext['is_admin'],1);
            } else {
                $this->logOrder($order['order_id'], 'Order cancelled payment successfully', 'Payment cancelled', $order['user_id']);
            }
            return ['code' => 1, 'msg' => 'Order payment cancelled successfully'];
        }
        return ['code' => 0, 'msg' => 'Order payment cancellation failed'];
    }

    // allocate store
    public function confirm_order_shop($order_sn,$ext=[]){
        $shop_id=$ext['shop_id'];
        $is_admin=$ext['is_admin'];
        $order = Db::name('order')->where("order_sn", $order_sn)->find();
        if($order['shop_id']){
            return ['code'=>0,'data'=>[],'msg'=>'已确认订单'];
        }
        Db::startTrans();
        try {
            //Update order status and assign to store
            Db::name('order')->where('order_id', $order['order_id'])->update(['order_status' => 1,'shop_id'=>$shop_id]);
            Db::name('order_goods')->where('order_id', $order['order_id'])->update(['shop_id'=>$shop_id]);
            //store minus inventory
            $this->minus_stock($order['order_id'],$is_admin);
            // commit the transaction
            Db::commit();
            //Record order operation log
            if (isset($ext['is_admin'])) {
                $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : 'Order assigned successfully';
                $this->logOrder($order['order_id'], $note, 'allocation successful',$ext['is_admin'],1);
            } else {
                $this->logOrder($order['order_id'], 'Order assigned successfully', 'Assigned successfully', $order['user_id']);
            }
            return ['code'=>1,'data'=>[],'msg'=>'order allocation successful'];
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return ['code'=>0,'data'=>[],'msg'=>'Order allocation failed:'.$e->getMessage()];
        }
    }
    // cancel store allocation
    public function confirm_order_shop_cancel($order_sn,$ext=[]){
        $order = Db::name('order')->where("order_sn", $order_sn)->find();
        if(!$order['shop_id']){
            return ['code'=>0,'data'=>[],'msg'=>'unassigned store'];
        }
        Db::startTrans();
        try {
            //Store add inventory
            $this->rever_minus_stock($order['order_id'],$ext['is_admin']);
            //Update order status and assign to store
            Db::name('order')->where('order_id', $order['order_id'])->update(['order_status' => 0,'shop_id'=>0]);
            Db::name('order_goods')->where('order_id', $order['order_id'])->update(['shop_id'=>0]);
            // commit the transaction
            Db::commit();
            //Record order operation log
            if (isset($ext['is_admin'])) {
                $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : 'Cancel order assignment successfully';
                $this->logOrder($order['order_id'], $note, 'Unassign',$ext['is_admin'],1);
            } else {
                $this->logOrder($order['order_id'], 'Cancel order allocation success', 'Cancel allocation', $order['user_id']);
            }
            return ['code'=>1,'data'=>[],'msg'=>'Cancel order assignment successfully'];
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return ['code'=>0,'data'=>[],'msg'=>'Cancel order allocation failed:'.$e->getMessage()];
        }
    }

    //Ship
    public function updata_shoping_delivery($order_sn,$invoice_no,$shipping_id,$userId){
        $orderInfo = Db::name('order')->where("order_sn", $order_sn)->find();

        if(!$orderInfo){
            return ['code' => 0, 'msg' => 'Order does not exist'];
        }
        if (!$invoice_no) {
            return ['code' => 0, 'msg' => 'The invoice number cannot be empty'];
        }
        if (!$shipping_id) {
            return ['code' => 0, 'msg' => 'Logistics mode cannot be empty'];
        }
        if ($orderInfo['pay_status'] != 1) {
            return ['code' => 0, 'msg' => 'Unpaid orders cannot be shipped'];
        }
        if ($orderInfo['order_status'] != 1) {
            return ['code' => 0, 'msg' => 'Unconfirmed orders cannot be shipped'];
        }
        // $pool_water = Db::name('member')->where('shop_id',$orderInfo['shop_id'])->value('pool_water');
        // if(($orderInfo['order_amount']+$orderInfo['user_money']) > $pool_water){
        // return ['code' => 0, 'msg' => 'Insufficient flow pool, unable to deliver'];
        // }
        // carry out delivery processing
        $updata['shipping_status'] = 1;
        $updata['invoice_no'] = $invoice_no;
        $updata['shipping_code'] = $shipping_id;
        $updata['shipping_name'] = Db::name('shipping')->where('id', $shipping_id)->value('shipping_name');
        $updata['shipping_time'] = time();
        if(Db::name('order')->where("order_id", $orderInfo['order_id'])->update($updata)){
            // //flow pool reduction
            // $this->orderLongevityLire(2,$orderInfo['order_id'],$orderInfo['shop_id'],$orderInfo['user_id'],$orderInfo['user_money']+$orderInfo['order_amount']) ;
            // increase item sales
            $order_goods = Db::name('order_goods')->where('order_id',$orderInfo['order_id'])->select();
            /*foreach ($order_goods as $k => $v) {
                Db::name('goods')->where('id',$v['goods_id'])->inc('sales_num', $v['goods_num'])->update();
                //decrease stock
                Db::name('goods')->where('id',$v['goods_id'])->dec('spec_num', $v['goods_num'])->update();
                Db::name('spec_goods')->where(['goods_id'=>$v['goods_id'],'spec_key'=>$v['spec_key']])->dec('spec_num')- >update();
            }*/
            //Administrator operation log record
            $this->logOrder($orderInfo['order_id'], 'Successful delivery operation', 'Successful delivery', $userId,1);
            //forward news
            if ($orderInfo['user_id']) {
                $member = new MemberModel();
                $member_res = $member->where('id', $orderInfo['user_id'])->find();
                $params = [
                    'code' => '',
                    'content'=>'Hello, your order number '.$orderInfo['order_sn'].' has been shipped, please log in to the mobile APP or website to check! '
                    //Hello, your order number is ***** has been shipped, please log in to the mobile APP or website to check!
                ];
                if ($member_res['mobile']) {
                    $haoma = substr($orderInfo['order_sn'],-4);
                    if($member_res['country_code']==86){
                        $params['content'] = 'Hello, your order ending in '.$haoma.' has been shipped, please log in to the APP to check! ';
                    }else{
                        $params['content'] = 'Hello, your order number ending in '.$haoma.' has been shipped.';
                    }
                    $info = \app\common\service\Msg::send_sms(1,$member_res['country_code'].$member_res['mobile'],$params);
                }
		// Msg::send(4, $orderInfo['user_id'], ['name' => $name, 'order_id' => $orderInfo['order_sn']]);
            }
            return ['code' => 1, 'msg' => 'Successful delivery'];
        }
        return ['code'=>0,'data'=>[],'msg'=>'shipping failed'];

    }

   //confirm the receipt of goods
    public function confirm_order($order_sn, $ext = [])
    {
        $order = Db::name('order')->where("order_sn", $order_sn)->find();
        if ($order['order_status'] != 1)
            return ['code' => 0,'msg' => 'The order cannot be confirmed for receipt'];
        if (empty($order['pay_time']) || $order['pay_status'] == 0) {
            return ['code' => 0, 'msg' => 'The merchant has not confirmed the payment, and the order cannot be confirmed for the time being received'];
        }
        if ($order['pay_status'] == 3 || $order['pay_status'] == 2) {
            return ['code' => 0,'msg' => 'The order has been applied for a refund or is in the process of applying for a refund, and the receipt cannot be determined for the time being'];
        }
        $updata['order_status'] = 2; // Received
        //$updata['pay_status'] = 1; // paid
        $updata['confirm_time'] = time(); // receipt confirmation time

        Db::startTrans();
        try {

            Db::name('order')->where("order_sn", $order_sn)->update($updata);

            //$this->orderDetailed($order['shop_id'],$order['order_id'],$order['user_money']+$order['order_amount'],1,'order collection',$order ['user_id']);
            $this->orderLongevityLire(4,$order['order_id'],$order['shop_id'],$order['user_id'],$order['user_money']+$order['order_amount']);
            // $Users = new Users;
            // $Users->order_jiangli($order['user_id'],$order['user_money']+$order['order_amount']);

            Db::name('shop_order_log')->insert(['order_id'=>$order['order_id'],'user_id'=>$order['user_id'],'shop_id'=>$order['shop_id '],'remarks'=>'order collection','create_time'=>time()]);

            // commit the transaction
            Db::commit();
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return ['code'=>0,'data'=>[],'msg'=>'operation failed:'.$e->getMessage()];
        }
        //$this->order_give($order);// Call the gift method to give the corresponding gift to the person who placed the order
        //Record
        //$Settlement = new Settlement(config('config'));
        //$Settlement->xiaofei($order['user_id'],$order['order_id']);
        if (isset($ext['is_admin'])) {
            $note = isset($ext['note']) && !empty($ext['note']) ? $ext['note'] : 'Order confirmed receipt successfully';
            $this->logOrder($order['order_id'], $note, 'Confirm receipt', $ext['is_admin'],1);
        } else {
            $this->logOrder($order['order_id'], 'Order confirmed receipt successfully', 'Confirmed receipt', $order['user_id']);
        }
        return ['code' => 1,'msg' => 'operation successful'];
    }

   // void the order
    public function orderInvalid($order_id,$note,$admin){
        if(Db::name('order')->where('order_id', $order_id)->update(['order_status' => 5])){
            $this->logOrder($order_id, $note, 'Order void', $admin,1);
            return ['code' => 1,'msg' => 'operation successful'];
        }
        return ['code' => 0,'msg' => 'operation failed'];
    }
    // delete the order
    public function delOrder($order_id,$note,$admin)
    {
        $data = Db::name('order')->where('order_id', $order_id)->find();
        if (!$data) {
            return ['code' => 0, 'msg' => 'Order does not exist'];
        } elseif ($data['deleted']) {
            return ['code' => 0, 'msg' => 'The order has been deleted'];
        } elseif (in_array($data['order_status'], [0, 1])) { //To be confirmed, confirmed
            return ['code' => 0, 'msg' => 'The order has not been completed and cannot be deleted'];
        }
        $row = Db::name('order')->where('order_id' ,$order_id)->update(['deleted'=>1]);
        if (!$row) {
            return ['code' => 0,'msg' => 'deletion failed'];
        }
        $this->logOrder($order_id, $note, 'order deletion', $admin,1);
        return ['code' => 1, 'msg' => 'deletion successful'];
    }



    /**
     * Return management
     *Single item of order
     * @param $rec_id order_goods self-incrementing primary key used to identify which product to return
     * @param $refund_type Refund type 0 refund only 1 return refund
     * @param $imgs image
     * @param $reason reason for refund
     * @param $reason problem description
     * @return array
     */
    public function add_return_goods($order_id, $is_work, $refund_type = 0, $refund_kddh='', $refund_kdlx='', $imgs = [], $reason = '', $describe = '')
    {
        //① First find out order information and product order information based on order_id
        $order = Db::name('order')->where('order_id', $order_id)->find();
        if (!$order) {
           return ['code' => 0, 'msg' => 'The order was not found', 'result' =>''];
        }
        if (!$order['pay_status']) {
            return ['code' => 0, 'msg' => 'The order has not been paid and cannot apply for a refund', 'result' =>''];
        }
        if ($order['pay_status'] == 2) {
            return ['code' => 0, 'msg' => 'The order is applying for a refund, please do not apply again', 'result' =>''];
        }
        if ($order['pay_status'] == 3) {
            return ['code' => 0, 'msg' => 'The order has been successfully applied for a refund, please do not apply again', 'result' =>''];
        }
        // if($order['shipping_status'] == 1 && $refund_type == 0){
        // return ['code' => 0, 'msg' => 'shipped, type cannot only be refunded', 'result' =>''];
        // }
        // if($order['shipping_status'] == 0 && $refund_type == 1){
        // return ['code' => 0, 'msg' => 'Not shipped, type can only be refunded', 'result' =>''];
        // }
        //return the goods
        $return_money = $return_deposit = 0;
        if ($order['shipping_status'] > 0 && $order['shipping_price']) {
            //Non-refundable logistics fee, priority cash reduction

            if ($order['order_amount']+$order['user_money']) {

                $return_money = ($order['order_amount']+$order['user_money']) - $order['shipping_price'];
                if ($return_money < 0) {
                    //Reuse the balance
                    $return_deposit = $order['user_money'] + $return_money;
                    $return_money   = 0;
                }
            }
        } else {
            $return_deposit = $order['user_money'];
            $return_money   = $order['order_amount'];
        }

        if ($return_money < 0 || $return_deposit < 0) {
            return ['code' => 0, 'msg' =>'Error requesting refund', 'result' =>''];
        }
        $goods_num=Db::name('order_goods')->where('order_id',$order_id)->sum('goods_num');

        $data = [
            'order_id'        => $order_id,
            'shop_id'         => $order['shop_id'],
            'order_sn'        => $order['order_sn'],
            'reason'          => $reason,
            'describe'        => $describe,
            'imgs'            => $imgs ? json_encode($imgs) : '',
            'addtime'         => time(),
            'status'          => 0,
            'goods_num'       => $goods_num,
            'user_id'         => $order['user_id'],
            'refund_money'    => $return_money,
            'refund_deposit'  => $return_deposit,
            'refund_integral' => $order['integral'],
            'refund_type'     => $refund_type,
            'refund_kddh'     => $refund_kddh,
            'refund_kdlx'     => $refund_kdlx,
            'is_work'         => $is_work
        ];
        $res = Db::name('return_goods')->insert($data);
        if ($res) {
            $id = Db::name('return_goods')->getLastInsID();
           //modify order status
            Db::name('order')->where('order_id', $order_id)->update(['pay_status' => 2]);
            return ['code' => 1, 'msg' => 'application successful', 'result' => $id];
        } else {
            return ['code' => 0, 'msg' => 'application failed', 'result' => ''];
        }
    }

    /**
     *@param submit a ticket
     */
    public function add_return_work($id, $refund_type = 0, $imgs = [], $reason = '', $describe = ''){
        $return_goods = Db::name('return_goods')->where('id', $id)->find();
        if (!$return_goods) {
           return ['code' => 0, 'msg' => 'The return request was not found', 'result' =>''];
        }
    }
    /**
     * Return and exchange review processing
     * -2 User canceled -1 Review failed 0 Pending review 1 Passed
     */
    public function returnGoodsStatus($id, $status)
    {
        $return_goods = Db::name('return_goods')->where('id', $id)->find();
        if ($status == -2 || $status == -1) {
            Db::name('return_goods')->where('id', $id)->update(['status' => $status, 'refund_time' => time()]);
            if ($status == -2) {
                Db::name('order')->where('order_id', $return_goods['order_id'])->update(['pay_status' => 1]);
            } else {
                Db::name('order')->where('order_id', $return_goods['order_id'])->update(['pay_status' => 4]);
                //TODO reject refund for notification
                //forward news
                $member = new MemberModel();
				// $name = $member->where('id', $return_goods['user_id'])->value('nickname');
                $member_res = $member->where('id', $return_goods['user_id'])->find();
                $params['code'] = '';
                if($return_goods['refund_type'] == 1){
                    $params['content'] = 'Hello, your '.$return_goods['order_sn'].' order merchant refused to refund, please log in to check the reason in my order or contact the merchant. ';
                }else{
                    $params['content'] = 'Hello, your '.$return_goods['order_sn'].' order merchant rejected your return request, please log in to check the reason for rejection on the order details page or contact the merchant. ';
                }
                if ($member_res['mobile']) {
                    $info = \app\common\service\Msg::send_sms(1,$member_res['country_code'].$member_res['mobile'],$params);
                }
//                Msg::send(6, $return_goods['user_id'], ['name' => $member_res['nickname'], 'order_id' => $return_goods['order_sn']]);
            }
        } elseif ($status == 1) {
           //examination passed
			// if ($return_goods['refund_money'] > 0) {
            //Refund the payment, directly transfer the payment for a refund
//                $payment = new Payment();
//                echo "<pre>";
//                print_r(1);
//                $res = $payment->refunds($return_goods['order_sn'], $return_goods['refund_money']);
////                return $res;
//                if ($res['code'] != 1) {
//                    return ['code' => 0, 'msg' =>$res['msg']];
//                }
//            }
            // start transaction
			// Db::startTrans();
			// try {
            if ($return_goods['refund_integral'] > 0) {
                // refund points
                $this->orderLongevityLire(6,$return_goods['order_id'],$return_goods['shop_id'],$return_goods['user_id'],$return_goods['refund_integral']);
                //IntegralLog::setInc($return_goods['user_id'],$return_goods['refund_integral']);
                IntegralLog::operate($return_goods['user_id'], $return_goods['refund_integral'], 2, 1,'Order refund return points',0);
            }
            $money = $return_goods['refund_deposit'] + $return_goods['refund_money'];
            if ($return_goods['refund_deposit'] > 0 || $return_goods['refund_money'] > 0) {
                //refund balance
                //MoneyLog::setInc($return_goods['user_id'],$return_goods['refund_deposit']);

                $this->orderLongevityLire(3,$return_goods['order_id'],$return_goods['shop_id'],$return_goods['user_id'],$money);
                MoneyLog::operate($return_goods['user_id'], $money, 4, 1, 'Order refund return balance');
            }
            $shipping_status =  Db::name('order')->where('order_id', $return_goods['order_id'])->value('shipping_status');
            //return the goods
            if($shipping_status == 1){
                $this->orderLongevityLire(7,$return_goods['order_id'],$return_goods['shop_id'],$return_goods['user_id'],$money);
            }

            //$this->orderDetailed($return_goods['shop_id'],$return_goods['order_id'],$return_goods['refund_money'],2,'order refund',$return_goods['user_id']);
			// return ['code' => 1, 'msg' => $return_goods];
            Db::name('shop_order_log')->insert(['order_id'=>$return_goods['order_id'],'user_id'=>$return_goods['user_id'],'shop_id'=>$return_goods['shop_id '],'remarks'=>'order refund','create_time'=>time()]);
            //TODO There is no transaction judgment here. It needs to be optimized.

            Db::name('return_goods')->where('id', $id)->update(['status' => $status, 'refund_time' => time()]);
            //add stock
            $order_goods = Db::name('order_goods')->where('order_id', $return_goods['order_id'])->select();
            foreach ($order_goods as $v){
//                $user_id=Db::name('order')->where('order_id',$v['order_id'])->value('user_id');
//                    return $user_id;
                //Db::name('member')->where('id',$user_id)->inc('money',$v['member_goods_price'])->update();

                Db::name('goods')->where('id',$v['goods_id'])->dec('sales_num', $v['goods_num'])->update();
                Db::name('goods')->where('id',$v['goods_id'])->inc('spec_num',$v['goods_num'])->update();
                Db::name('spec_goods')->where(['goods_id'=>$v['goods_id'],'spec_key'=>$v['spec_key']])->inc('spec_num')->update();
            }
            Db::name('order')->where('order_id', $return_goods['order_id'])->update(['order_status' => 3, 'pay_status' => 3]);
            //TODO agree to refund for notification
// $name = Db::name('member')->where('id', $return_goods['user_id'])->value('nickname');
            $member_res = Db::name('member')->where('id', $return_goods['user_id'])->find();
            $params['code'] = '';
            if($return_goods['refund_type'] == 1){
                $params['content'] = 'Hello, your '.$return_goods['order_sn'].'The order merchant has confirmed the refund, and the amount has been refunded to your account, please log in to check. ';
            }else{
                $params['content'] = 'Hello, your '.$return_goods['order_sn'].' order merchant has agreed to return it, please log in and check the return address in the order details to return it. ';
            }
            if ($member_res['mobile']) {
                $info = \app\common\service\Msg::send_sms(1,$member_res['country_code'].$member_res['mobile'],$params);
            }

////                 commit transaction
// 					Db::commit();
// 					return ['code' => 1, 'msg' => 'Agreed'];
// 					} catch (\Exception $e) {
// 					// rollback the transaction
//					 Db::rollback();
// 					return ['code' => 1, 'msg' => 'Audit failed'];
//            }

//            return $order_goods;
//            $GoodsStock = new GoodsStock();

//            foreach ($order_goods as $v) {
//                $GoodsStock->incStock(0,$v['goods_id'],$v['spec_id'],$v['shop_id'],$v['goods_num'],$v['member_goods_price'],$id ,$type=4,$rem = 'Mall return');
// }
            //Check whether the order has been received and the points sent will be taken back.
            //TODO::Newfman's no points
            /* $order = Db::name('order')->where('order_id', $return_goods['order_id'])->find();
              if ($order['order_status'] == 2 || $order['order_status'] == 4) {
                  if ($give_integral = Db::name('order_goods')->where('order_id', $return_goods['order_id'])->sum('give_integral')) {
                      //recover points
                      IntegralLog::setDec($return_goods['user_id'], $give_integral);
                      IntegralLog::operate($return_goods['user_id'], -$give_integral, 1, 1, 'Order refund to recover bonus points', 0);
                  }
              }*/
            // Judge to modify the order status

//            Msg::send(5, $return_goods['user_id'], ['name' => $name, 'order_id' => $return_goods['order_sn']]);
        }
        return ['code' => 1, 'msg' =>'Processed successfully'];
    }


    /*******************************************************************************************************************
    Related tool code
     ******************************************************************************************************************/
    /**
     * Order operation log
     * parameter example
     * @param type $order_id order id
     * @param type $action_note Action note
     * @param type $status_desc Operation status Submit order, successful payment, cancel, wait for delivery, complete
     * @param type $user_id user id defaults to administrator
     * @return boolean
     */
    public function logOrder($order_id, $action_note, $status_desc, $user_id = 0,$action_user_type=0)
    {
        $order = Db::name('order')->where([["order_id",'in', (string)$order_id]])->select();
//        $order = Db::name('order')->where("order_id", $order_id)->select();
        $action_info = [];
        foreach ($order as $k => $v) {
            $action_info[$k] = [
                'order_id'        => $order_id,
                'shop_id'         => $v['shop_id'],
                'action_user'     => $user_id,
                'action_user_type'=> $action_user_type,
                'order_status'    => $v['order_status'],
                'shipping_status' => $v['shipping_status'],
                'pay_status'      => $v['pay_status'],
                'action_note'     => $action_note,
                'status_desc'     => $status_desc,
                'log_time'        => time(),
            ];
        }
        Db::name('order_action')->insertAll($action_info);
    }

    /**
     * order bill
     * parameter example
     * @param type $order_id order id
     * @param type $pay_id payment type
     * @param type $pay_name payment name
     * @param type $user_id user id defaults to administrator
     * @return boolean
     */
    public function  orderDetailed($shop_id,$order_id, $money,$pay_id,$pay_name, $user_id = 0){
        $order = Db::name('order')->where([["order_id",'in', (string)$order_id]])->select();
//        $order = Db::name('order')->where("order_id", $order_id)->select();
        $action_info = [];
        foreach ($order as $k => $v) {
            $action_info[$k] = [
                'shop_id'         => $shop_id,
                'infoId'          => $order_id,
                'pay_id'          => $pay_id,
                'pay_name'        => $pay_name,
                'money'           => $money,
                'pay_date'        => date('Y-m-d',time()),
                'user_id'         => $user_id,
                'addtime'        => time(),
            ];
        }
        Db::name('shop_detailed')->insertAll($action_info);

    }
    //Refresh the commodity inventory, if the commodity has a set specification inventory, the total commodity inventory is equal to the sum of all specifications inventory
    public function refresh_stock($goods_id)
    {
        $count = Db::name('spec_goods')->where("goods_id", $goods_id)->count();
        if($count == 0) return false; // no specification method used, no need to change total inventory
        $store_count = Db::name('spec_goods')->where("goods_id", $goods_id)->sum('store_count');
        Db::name('goods')->where("id", $goods_id)->update(array('store_count'=>$store_count)); // Update the total inventory of items
    }
    //decrease stock
    public function minus_stock($order_id,$uid=0)
    {
        $orderGoodsArr = Db::name('order_goods')->where("order_id", $order_id)->select();
        $GoodsStock = new GoodsStock();
        foreach ($orderGoodsArr as $key => $val) {
            if($val['is_stock'] != 1){
                //Remove inventory group ($uid,$goods_id,$spec_id,$shop_id,$num,$price,$act,$type=2,$rem = 'Mall order distribution store'){
                $GoodsStock->decStock($uid,$val['goods_id'],$val['spec_id'],$val['shop_id'],$val['goods_num'],$val['member_goods_price'],$ order_id,2,'Mall order allocation');
                // increase item sales
                Db::name('goods')->where("id", $val['goods_id'])->inc('sales_num', $val['goods_num'])->update();
            }
        }
        //Update the inventory deduction field of the item
        Db::name('order_goods')->where(['order_id'=>$order_id])->update(['is_stock'=>1]);
    }
    //The reverse operation of reducing inventory is used for order cancellation and inventory increase
    public function rever_minus_stock($order_id,$uid=0)
    {
        $orderGoodsArr = Db::name('order_goods')->where("order_id", $order_id)->select();
        $GoodsStock = new GoodsStock();
        foreach ($orderGoodsArr as $key => $val) {
            if($val['is_stock'] == 1){
                //Remove inventory group $uid,$goods_id,$spec_id,$shop_id,$num,$act
                $GoodsStock->incStock($uid,$val['goods_id'],$val['spec_id'],$val['shop_id'],$val['goods_num'],$val['member_goods_price'],$ order_id,$type=3,$rem = 'Unassign order');
                // increase item sales
                Db::name('goods')->where("id", $val['goods_id'])->dec('sales_num', $val['goods_num'])->update();
            }
        }
        Db::name('order_goods')->where(['order_id'=>$order_id])->update(['is_stock'=>0]);
    }

    /**
     * Get the order order_sn
          * @return string
          */
         public function get_order_sn()
         {
             $order_sn = null;
             // Ensure that no duplicate order numbers exist
             while(true){
                 $order_sn = date('YmdHis').rand(1000,9999); // order number
            $order_sn_count = Db::name('order')->where("order_sn = ".$order_sn)->count();
            if($order_sn_count == 0)
                break;
        }

        return $order_sn;
    }
    /**
     * How to give gifts
     */
    public function order_give($uid,$order)
    {
        $integral = Db::name('order_goods')->where(['order_id' => $order['order_id']])->sum('give_integral');
        if ($integral) {
            $users = new Users();
            $user = Db::name('member')->where('id',$uid)->find();
            Db::name('member')->where('id',$uid)->inc('integral',$integral)->update();
            //IntegralLog::operate($user['id'], $integral, 2, 1,'Order completed with free credits',0);
            $MemberWalletLogModel = new MemberWalletLogModel;
            $MemberWalletLogModel->log($user['id'],$integral,$user['integral'],$user['integral'] + $integral,82,'Purchase credits',$order[' order_id']);
        }
    }

    /**
     *@param type 1 payment 2 delivery 3 refund 4 receipt 5 points decrease 6 points increase
     */
    public function orderLongevityLire($type,$order_id,$shop_id,$user_id,$money){
        $user = Db::name('member')->where('id',$user_id)->find();
        $uid  = Db::name('shop_lists')->where('id',$shop_id)->value('uid');
        $shop = Db::name('member')->where('id',$uid)->find();
        $MemberWalletLogModel = new MemberWalletLogModel;
        switch ($type) {
            case 1:
                // balance decrease
                if($money > 0){
                    $MemberWalletLogModel->log($user['id'],$money,$user['money'],$user['money'] - $money,16,'Order payment KRC reduction',$order_id);
                }
                break;
            case 2:
                // reduce the flow pool
                if($money > 0){
                    Db::name('member')->where('id',$uid)->dec('pool_water',$money)->update();
                    $MemberWalletLogModel->log($shop['id'],$money,$shop['pool_water'],$shop['pool_water'] - $money,0,'Order collection inventory pool reduction',$order_id) ;
                }
                break;
            case 3:
                // increase the balance
                if($money > 0){
                    Db::name('member')->where('id',$user['id'])->inc('money',$money)->update();
                    $MemberWalletLogModel->log($user['id'],$money,$user['money'],$user['money'] + $money,0,'order refund KRC increase',$order_id);
                }
                break;
            case 4:
                // Merchant balance increases
                if($money >0){
                    Db::name('member')->where('id',$uid)->inc('money',$money)->update();
                    $MemberWalletLogModel->log($shop['id'],$money,$shop['money'],$shop['money']+$money,17,'order receipt KRC increase',$order_id);
                }

                // increase the user consumption pool
                // if($money >0){
                // Db::name('member')->where('id',$user_id)->inc('pool_consumption',$money)->update();
                // $MemberWalletLogModel->log($user['id'],$money,$user['pool_consumption'],$user['pool_consumption']+$money,0,'Confirm the increase in the receiving resonance hashrate', $order_id);
                // }
                break;
            case 5:
                //Reduce the point pool
                if($money >0){
                    Db::name('member')->where('id',$user_id)->dec('integral',$money)->update();
                    $MemberWalletLogModel->log($user['id'],$money,$user['integral'],$user['integral']+$money,81,'Order payment deduction reduced',$order_id) ;
                }
                break;
            case 6:
                //Increase the credit pool
                if($money >0){
                    Db::name('member')->where('id',$user_id)->inc('integral',$money)->update();
                    $MemberWalletLogModel->log($user['id'],$money,$user['integral'],$user['integral']+$money,0,'Order refund deduction increased',$order_id );
                }
                break;
            case 7:
                // add flow pool
                if($money >0){
                    Db::name('member')->where('id',$uid)->inc('pool_water',$money)->update();
                    $MemberWalletLogModel->log($shop['id'],$money,$shop['pool_water'],$shop['pool_water'] + $money,0,'Order Returns Inventory Pool Increase',$order_id);
                }
                break;
            default:

                break;
        }

    }

}