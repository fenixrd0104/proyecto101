<?php
/**
 * General class for price calculation
  * Created by PhpStorm.
  * User: tianfeiwen
  * Date: 2017/9/12
  * Time: 15:21
  */
 namespace app\common\model;
 
 use think\Model;
 use think\facade\Db;
 use think\facade\Config;
 class Price extends Model
 {
     /**
      * Calculate the order amount
      * @param type $user_id user id
      * @param type $order_goods The purchased item is checked from the selected item in the cart
      * @param type $shipping_price Shipping cost, if the shipping cost is passed, the shipping cost will not be calculated
      * @param type $province province
      * @param type $city city
      * @param type $district county
      * @param type $pay_points points
      * @param type $user_money balance
      * @param type $coupon_id coupon
      * @param type $couponCode coupon code
     * @return array
     */
    public function calculate_price($user_id = 0, $order_goods, $shipping_price = 0, $province = 0, $city = 0, $district = 0, $pay_points = 0, $user_money = 0, $coupon_id = 0, $couponCode = '')
    {
        $member = new Member();
        $user = $member->where('id',$user_id)->find();
        if (empty($order_goods)){
          return ['status' => 0, 'msg' => 'The item list cannot be empty', 'result' => ''];
        }
        $goods_ids = [];
        foreach ($order_goods as $k => $v) {
            $goods_ids[] = $v['goods_id'];
        }
        $goods_arr = Db::name('goods')->where('id', 'in', $goods_ids)->column('top_cate,weight,market_price,is_free_shipping,exchange_integral,shop_price,fid', 'id' );
        // view product
        $goods_weight = 0;//Goods weight
        $order_integral = 0;//Commodity available points
        $goods_price = 0;//Total price of goods
        $cut_fee = 0; // save amount
        $goods_num = 0;//Number of purchases
        $arr = [];//Array used to calculate logistics cost
        $typegoods = 0;// Product type 1 is a preferential product
        foreach ($order_goods as $key => $val) {
            //Determine whether it is a privilege
            if($goods_arr[$val['goods_id']]['top_cate']==2){
                $typegoods = 1;
            }
            //If the product is not free shipping, calculate the weight of the product
            if ($goods_arr[$val['goods_id']]['is_free_shipping'] == 0) {
                $goods_weight += $goods_arr[$val['goods_id']]['weight'] * $val['goods_num']; //accumulate product weight
                $arr[$key]['id'] = $val['goods_id'];//Goods id, temporarily useless, as a reserved field
                $arr[$key]['fid'] = $goods_arr[$val['goods_id']]['fid'];//Item shipping template id
                $arr[$key]['num'] = $val['goods_num'];//Number of goods
                $arr[$key]['weight'] = $goods_arr[$val['goods_id']]['weight'];//Item weight
                $arr[$key]['volume'] = 0;//Product volume
                $arr[$key]['price'] = $val['member_goods_price'];//Goods price
            }
            // Calculate the available points for the order
            if ($goods_arr[$val['goods_id']]['exchange_integral'] > 0) {
                //If the product is set to redeem points, the points of the product itself will be used.
                $order_integral +=  $goods_arr[$val['goods_id']]['exchange_integral'] * $val['goods_num'];
            }
            //Subtotal
            $order_goods[$key]['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];
            $order_goods[$key]['spec_num'] = getGoodsNum($val['goods_id'], $val['spec_key']);
            //Maximum amount of inventory that can be purchased
            if ($order_goods[$key]['spec_num'] <= 0 || $order_goods[$key]['spec_num'] < $order_goods[$key]['goods_num']) {
                return ['status' => 0, 'msg' => $order_goods[$key]['goods_name'] .','.$val['spec_key_name']. "Insufficient stock, please re-order", 'result ' => ''];
            }
            $goods_price += $order_goods[$key]['goods_fee']; // total price of goods
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];//saving amount
            $goods_num += $val['goods_num']; // Purchase quantity

            // if ($goods_arr[$val['goods_id']]['exchange_integral'] > 0) {
            // //Commodity set points redemption, use the points of the commodity itself.
            // $order_integral += $goods_price;
            // }
        }
        //Process the logistics price If the logistics price is not passed, the logistics needs to be calculated

        if ($shipping_price == 0) {
            //Calculate logistics cost
            if ($arr) {
                $shipping_price = \app\common\service\Freight::order_freight($city, $arr);
            }
        }
        //TODO:: Commodity price is equal to the point price added
        //$order_integral = $goods_price;

        // available points processing
        if ($pay_points) {
            if ($pay_points > $order_integral) {
               $pay_points = $order_integral;//If the used points exceed the limit, use the most points
            }
            //If the user does not have so many points, force the user's existing points to be used
            if ($user['integral'] < $pay_points) {
                $pay_points = $user['integral'];
            }
        }
        //The amount payable for the item
        $order_amount = $goods_price + $shipping_price;//Total price of goods + logistics price - preferential price
        //Subtract the proportional amount corresponding to the points
        $point_rate = config('config.point_rate');
        $point_rate = $point_rate ? $point_rate : 100;

        $pay_points = $pay_points/$point_rate;//Points offset payable amount 0.05
        $order_amount -= $pay_points;//Subtract points to offset the amount due -0.05
        //Available Balance
        if ($user_money) {
            if ($user_money > $order_amount) {
                $user_money = $order_amount;//If the balance used exceeds the limit, use the maximum balance
            }
            //If the user does not have this much balance, force the user's existing balance to be used
            if ($user['money'] < $user_money) {
                $user_money = $user['money'];
            }
        }

        if($typegoods==1){
            $user_money = 0;
        }
        $order_amount -= $user_money;//Subtract the fee paid by the balance
        $total_amount = $goods_price + $shipping_price;//Total price of goods
        //Total order price Amount payable Logistics fee Total product price Savings Amount of products in total Points Balance Coupon
        $result = [
            'total_amount' => $total_amount, // total price
            'order_amount' => $order_amount, // Amount due
            'shipping_price' => $shipping_price, // Shipping fee
            'goods_price' => $goods_price, // total price of goods
            'cut_fee' => $cut_fee, // How much money was saved
            'anum' => $goods_num, // total quantity of goods
            'integral_money' => $pay_points, // Points offset amount
            'user_money' => round($user_money,2),// use balance
            'coupon_price' => 0, // Coupon offset amount
        ];
        //var_dump($result);exit;
        return ['status' => 1, 'msg' => "计算价钱成功", 'result' => $result];
    }

    /**
     * Used to calculate the price of the product placed by the back-end administrator
     * @param $goods_list list of goods [id:spec=>num]
     * @param $address address information
     */
    public function calculate_admin_price($goods_list, $address)
    {
        $goods_weight = 0;//Goods weight
        $goods_price = 0;//Total price of goods
        $cut_fee = 0; // save amount
        $goods_num = 0;//Number of purchases
        foreach ($goods_list as $k => $v) {
            //Determine whether it is a standard product or an ordinary product
            if (strstr($k, ':') === false) {
                $info = Db::name('goods')->where('id', $k)->find();
                $goods_weight += $info['weight'] * $v;
                $goods_price  += $info['shop_price'] * $v;
                $cut_fee      += ($info['market_price'] - $info['shop_price']) * $v;
                $goods_num    += $v;
            } else {
                $k = explode(':', $k);
                $specInfo  = Db::name('spec_goods')->where('goods_id', $k[0])->where('key', $k[1])->find();
                $goodsInfo = Db::name('goods')->where('id', $k[0])->find();
                $goods_weight += $goodsInfo['weight'] * $v;
                $goods_price  += $specInfo['price'] * $v;
                $cut_fee      += ($goodsInfo['market_price'] - $specInfo['price']) * $v;
                $goods_num    += $v;
            }
        }
        //TODO process the logistics price If the logistics price is not passed, the logistics needs to be calculated
        $shipping_price = 0;
        //Total amount of goods payable
        $order_amount = $goods_price + $shipping_price;
        $result = [
            'total_amount' => $order_amount, // total price
            'order_amount' => $order_amount, // Amount due
            'shipping_price' => $shipping_price, // Shipping fee
            'goods_price' => $goods_price, // total price of goods
            'cut_fee' => $cut_fee, // How much money was saved
            'anum' => $goods_num // total number of items
        ];
        return ['status' => 1, 'msg' => "Successful price calculation", 'result' => $result];
    }
}