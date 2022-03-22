<?php
/**
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/11/3
 * Time: 13:04
 */

namespace app\common\model;

use think\Model;
use think\facade\Db;

class FlashSale extends Model
{
    protected $flashSale;

    public function __construct($falseSaleId)
    {
        parent::__construct();
        $this->flashSale = Db::name('flash_sale')->find($falseSaleId);
        if ($this->flashSale) {
           / / Each initialization detects whether the activity is over, if it fails, update the activity and restore the commodity to normal commodity
            if ($this->checkFlashSaleIsEnd()) {
                Db::name('goods')->where("id", $this->flashSale['goods_id'])->update(['prom_type' => 0, 'prom_id' => 0]);
                Db::name('flash_sale')->where('id', $falseSaleId)->update(['is_end' => 1]);
            }
        }
    }
    /**
     * Whether the event is running
     * @return bool
     */
    public function checkActivityIsAble(){
        if(empty($this->flashSale)){
            return false;
        }
        if(time() > $this->flashSale['start_time'] && time() < $this->flashSale['end_time']){
            return true;
        }
        return false;
    }
    /**
     * Is the event over?
     * @return bool
     */
    public function checkFlashSaleIsEnd(){
        if(empty($this->flashSale)){
            return false;
        }
        if($this->flashSale['buy_num'] >= $this->flashSale['goods_num']){
            return true;
        }
        if(time() > $this->flashSale['end_time']){
            return true;
        }
        return false;
    }

    /**
     * Get information on snap-up deals
     * @param int $user_id | User ID
     * @param int $goods_id |goods id
     * @return mixed
     */
    public function getPromotionInfo($user_id = 0, $goods_id = 0)
    {
        if (empty($this->flashSale)) {
            $promotionInfo['is_end'] = 1;//Ended
        } else {
            $promotionInfo['is_end'] = 0;
            $promotionInfo['prom_type'] = 1;
            $promotionInfo['prom_id'] = $this->flashSale['id'];
            $promotionInfo['start_time'] = $this->flashSale['start_time'];
            $promotionInfo['start_time'] = $this->flashSale['start_time'];
            $promotionInfo['end_time'] = $this->flashSale['end_time'];
            $promotionInfo['store_count'] = $this->flashSale['goods_num'] - $this->flashSale['buy_num'];
            if ($promotionInfo['store_count'] <= 0) {
                $promotionInfo['is_end'] = 2;//Sold out
            } else {
                $promotionInfo['price'] = $this->flashSale['price'];
            }
        }
        return $promotionInfo;
    }

    /**
     *Get the number of items that the user snapped up
     * @param $user_id
     * @return float|int
     */
    public function getUserFlashOrderGoodsNum($user_id){
        $orderWhere = [
            'user_id'=>$user_id,
            'order_status' => ['<>', 3],
            'add_time' => ['between', [$this->flashSale['start_time'], $this->flashSale['end_time']]]
        ];
        $order_id_arr = Db::name('order')->where($orderWhere)->column('order_id');
        if ($order_id_arr) {
            $orderGoodsWhere = ['prom_id' => $this->flashSale['id'], 'prom_type' => 1, 'order_id' => ['in', implode(',', $order_id_arr)]];
            $goods_num = DB::name('order_goods')->where($orderGoodsWhere)->sum('goods_num');
            return $goods_num;
        } else {
            return 0;
        }
    }

    /**
     * Get the number of items left by the user
     * @param $user_id User ID
     * @param $goods_num purchase quantity
     * @return mixed
     */
    public function getUserFlashResidueGoodsNum($user_id,$goods_num){
        $purchase_num = $this->getUserFlashOrderGoodsNum($user_id); //Users snapped up the number of purchased items
        $residue_num = $this->flashSale['goods_num'] - $this->flashSale['buy_num']; //Remaining stock
        $cart_num = $goods_num + $purchase_num; //Total purchase quantity in the shopping cart now, including purchased, shopping cart
        //The total purchased quantity is greater than the limit purchase quantity
        if($cart_num > $this->flashSale['buy_limit']){
            if($goods_num >= $this->flashSale['buy_limit']){
                $goods_num = $this->flashSale['buy_limit'] - $purchase_num;
            }elseif($goods_num > $residue_num){ //The quantity to buy is greater than the stock
                $goods_num = $residue_num;
            }
        }else{
            //The quantity to buy is greater than the stock
            if($goods_num > $residue_num){
                $goods_num  = $residue_num;
            }

        }
        return $goods_num;
    }

    /**
     * Get a single snap event
     * @return static
     */
    public function getPromModel(){
        return $this->flashSale;
    }
}