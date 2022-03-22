<?php
/**
 * shopping cart
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/7
 * Time: 9:26
 */

namespace app\common\model;

use app\admin\model\Goods;
use think\facade\Db;

class Cart
{
    static private $_instance = null;
    private $goods;//Goods model
    private $session_id;//session_id
    private $user_id;//user_id
    private $maxNum = 20;//How many items are stored in the shopping cart at most

    private function __construct($user_id, $session_id)
    {
        $this->session_id = $session_id ? $session_id : session_id();
        $this->user_id    = $user_id ? $user_id : 0;
    }
    private function __clone() {
    }

    static public function getInstance($user_id = 0, $session_id = '') {
        if (self::$_instance == null) {
            self::$_instance = new self ($user_id, $session_id);
        }
        return self::$_instance;
    }

    /**
     * Modify the product model
     */
    public function setGoods(Goods $goods)
    {
        return $this->goods = $goods;
    }

    /**
     * Modify user ID
     */
    public function setUserId($user_id)
    {
        return $this->user_id = $user_id;
    }

    /**
     * Modify the unique value of session_id
     */
    public function setSessionId($session_id)
    {
        return $this->session_id = $session_id;
    }

    /**
     * Add item to cart
     */
    public function add($goods_id, $goods_num, $goods_spec_key = '')
    {
        $Goods = new Goods;
        if ($goodsModel = $Goods->find($goods_id)) {
            $this->setGoods($goodsModel);
        }
        if (!$this->goods) {
            return ['status'=>'0', 'msg'=>'purchased item does not exist', 'result'=>''];
        }
        #Activity product restrictions must be logged in to add to the cart
        if ($this->goods->prom_type > 0 && $this->user_id == 0) {
            return ['status'=>'0', 'msg'=>'You must log in first to purchase event items', 'result'=>''];
        }
        # Determine whether the shopping cart has reached the upper limit
        if ($this->user_id) {
            $cartNum = Db::name('cart')->where('user_id', $this->user_id)->count();
        } else {
            $cartNum = Db::name('cart')->where('session_id', $this->session_id)->count();
        }
        if ($cartNum >= $this->maxNum) {
            return ['status'=>'0', 'msg'=>'The shopping cart can only hold at most '.$this->maxNum.' kinds of products', 'result'=>''];
        }
        #Call different add-to-cart methods according to the type of prom_type to add to the cart
        switch ($this->goods->prom_type) {
            case 1 :
                $res = $this->addFlashSaleCart($goods_num, $goods_spec_key);
                break;
            case 2 :
               #todo group buy
                break;
            case 3 :
                #todo Promotional Offers
                break;
            case 3 :
                #todo pre-sale
                break;
            default :
                $res = $this->addNormalCart($goods_num, $goods_spec_key);
                break;
        }
        $res['result'] = $this->getUserCartGoodsNum();
        //setcookie('cn', $UserCartGoodsNum, null, '/');
        return $res;
    }

    private function addNormalCart($goods_num, $goods_spec_key)
    {
        #check this item sku
        $goodsStore = $this->goods->store_count;
        $goodsPrice = $this->goods->shop_price;
        if ($goods_spec_key) {
            $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('spec_key', $goods_spec_key)->find();
            if ($goodsSpec) {
                $goodsStore = $goodsSpec['spec_num'];
                $goodsPrice = $goodsSpec['shop_price'];
            } else {
                return ['status' => 0, 'msg' => 'Check no product of this specification', 'result' => ''];
            }
        }
       #Check if the item already exists in the shopping cart
        if ($this->user_id) {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'goods_id'=>$this->goods->id,'spec_key'=>$goods_spec_key] )->find();
        } else {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods- >id,'spec_key'=>$goods_spec_key])->find();
        }
        # Perform a logical add operation, if the item already exists in the shopping cart
        if ($userCartGoods) {
            $userWantGoodsNum = $goods_num + $userCartGoods['goods_num'];
            if($userWantGoodsNum > $goodsStore){
                return ['status' => 0, 'msg' => 'Insufficient stock of goods, remaining '.$goodsStore.' pieces, the current shopping cart has '.$userCartGoods['goods_num'].'pieces', 'result' => ''];
            }
            $res = Db::name('cart')->where('id', $userCartGoods['id'])->update(['goods_num' => $userWantGoodsNum, 'goods_price'=>$goodsPrice, ' member_goods_price'=>$goodsPrice]);
        } else {
            if($goods_num > $goodsStore){
                return ['status' => 0, 'msg' => 'Insufficient stock of goods, remaining '.$goodsStore.'piece', 'result' => ''];
            }
            $cartAddData = [
                'user_id' => $this->user_id, // user id
                'session_id' => $this->session_id, // sessionid
                'shop_id' => $this->goods->shop_id, // merchant id
                'goods_id' => $this->goods->id, // item id
                'goods_sn' => $this->goods->goods_sn, // item number
                'goods_name' => $this->goods->goods_name, // commodity name
                'market_price' => $this->goods->market_price, // market price
                'goods_price' => $goodsPrice, // purchase price
                'member_goods_price' => $goodsPrice, // member discount price defaults to purchase price
                'goods_num' => $goods_num, // buy quantity
                'add_time' => time(), // Add time to cart
                'prom_type' => 0, // 0 normal order, 1 flash sale, 2 group purchase, 3 promotion
                'prom_id' => 0, // event id
            ];
            //Default product original image
            $cartAddData['goods_img'] = $this->goods->original_img;
            if($goods_spec_key){
                $cartAddData['spec_key'] = $goods_spec_key;
                $cartAddData['spec_key_name'] = $goodsSpec['spec_name']; //Spec spec_name
                $cartAddData['sku'] = $goodsSpec['spec_sku']; //sku
                $img = Db::name('spec_image')->where([['goods_id','=',$this->goods->id], ['spec_item_id','in', explode('_' , $goods_spec_key)]])->value('src');
                if ($img) {
                    //Use the image of this specification
                    $cartAddData['goods_img'] = $img;
                }
            }
            $res = Db::name('Cart')->insert($cartAddData);
        }
        # Judge the return value of the database operation and return the information
        if($res!== false){
            return ['status' => 1, 'msg' => 'successfully added to the shopping cart', 'result' => ''];
        }else{
            return ['status' => 0, 'msg' => 'Add to cart failed', 'result' => ''];
        }
    }

    /**
     * Add spike products to shopping cart
     * @param $goods_num|Number of items purchased
     * @return array
     */
    private function addFlashSaleCart($goods_num, $goods_spec_key){
        $flashSaleLogic = new FlashSale($this->goods->prom_id);
        $flashSale = $flashSaleLogic->getPromModel();
        $flashSaleIsEnd = $flashSaleLogic->checkFlashSaleIsEnd();
        $goodsStore = $this->goods->store_count;
        if ($goods_spec_key) {
            $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $goods_spec_key)->find();
            if ($goodsSpec) {
                $goodsStore = $goodsSpec['spec_num'];
            } else {
                return ['status' => 0, 'msg' => 'No product of this specification found', 'result' => ''];
            }
        }
        if($flashSaleIsEnd){
            return ['status' => 0, 'msg' => 'Seckill activity has ended', 'result' => ''];
        }
        $flashSaleIsAble = $flashSaleLogic->checkActivityIsAble();
        if(!$flashSaleIsAble){
           //The activity is not in progress, go through the ordering process of ordinary products
            return $this->addNormalCart($goods_num,$goods_spec_key);
        }
        //Get the snapped items in the user's shopping cart
        if (!$this->user_id) {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'session_id'=>$this->session_id,'goods_id'=>$this->goods- >id,'spec_key'=>$goods_spec_key, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->find();
            $userCartGoodsNum = Db::name('cart')->where(['user_id'=>$this->user_id, 'session_id'=>$this->session_id, 'goods_id'=>$this->goods- >id, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->sum('goods_num');
        } else {
            $userCartGoods = Db::name('cart')->where(['user_id'=>$this->user_id,'goods_id'=>$this->goods->id,'spec_key'=>$goods_spec_key, 'prom_type' => 1, 'prom_id' => $this->goods->prom_id])->find();
            $userCartGoodsNum = Db::name('cart')->where(['user_id'=>$this->user_id, 'goods_id'=>$this->goods->id, 'prom_type' => 1, ' prom_id' => $this->goods->prom_id])->sum('goods_num');
        }
        $userCartGoodsNum = empty($userCartGoodsNum) ? 0 : $userCartGoodsNum;///Get the number of snapped goods in the user's shopping cart

        $userFlashOrderGoodsNum = $flashSaleLogic->getUserFlashOrderGoodsNum($this->user_id); //Get the number of items purchased by the user
        $flashSalePurchase = $flashSale['goods_num'] - $flashSale['buy_num'];//Purchase the remaining inventory
        $userBuyGoodsNum = $goods_num + $userFlashOrderGoodsNum + $userCartGoodsNum;
        if($userBuyGoodsNum > $flashSale['buy_limit']){
            return ['status' => 0, 'msg' => 'Purchase limit per person'.$flashSale['buy_limit'].'pieces, you have placed an order'.$userFlashOrderGoodsNum.'pieces'.'Shopping cart already exists' .$userCartGoodsNum.'pieces', 'result' => ''];
        }
        $userWantGoodsNum = $goods_num + $userCartGoodsNum;//The quantity to be purchased this time plus the quantity of the shopping cart itself
        if($userWantGoodsNum > $flashSalePurchase){
            return ['status' => 0, 'msg' => 'Insufficient number of active items to buy, remaining '.$flashSalePurchase.', the current shopping cart has '.$userCartGoodsNum.' pieces', 'result' => '' ];
        }
        if($userWantGoodsNum > $goodsStore){
            return ['status' => 0, 'msg' => 'Insufficient stock of goods, remaining '.$goodsStore.' pieces, the current shopping cart has '.$userCartGoods['goods_num'].'pieces', 'result' => ''];
        }
        if($userCartGoods){
            $cartResult = Db::name('cart')->where('id', $userCartGoods['id'])->setInc('goods_num', $goods_num);
        }else{
            $cartAddData = [
               'user_id' => $this->user_id, // user id
                'session_id' => $this->session_id, // sessionid
                'goods_id' => $this->goods->id, // item id
                'goods_sn' => $this->goods->goods_sn, // item number
                'goods_name' => $this->goods->goods_name, // commodity name
                'market_price' => $this->goods->market_price, // market price
                'goods_price' => $flashSale['price'], // purchase price
                'member_goods_price' => $flashSale['price'], // member discount price defaults to purchase price
                'goods_num' => $goods_num, // buy quantity
                'add_time' => time(), // Add time to cart
                'prom_type' => 1, // 0 normal order, 1 flash sale, 2 group purchase, 3 promotion
                'prom_id' => $this->goods->prom_id, // event id
            ];
            //Default product original image
            $cartAddData['goods_img'] = $this->goods->original_img;
            if($goods_spec_key){
                $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $goods_spec_key)->find();
                $cartAddData['spec_key'] = $goods_spec_key;
                $cartAddData['spec_key_name'] = $goodsSpec['key_name']; //spec key_name
                $img = Db::name('spec_image')->where(['goods_id' =>$this->goods->id, 'spec_item_id' => ['in', explode('_', $goods_spec_key) ]])->value('src');
                if ($img) {
                    //Use the image of this specification
                    $cartAddData['goods_img'] = $img;
                }
            }
            $cartResult = Db::name('Cart')->insert($cartAddData);
        }
        if($cartResult !== false){
            return ['status' => 1, 'msg' => 'successfully added to the shopping cart', 'result' => ''];
        }else{
            return ['status' => 0, 'msg' => 'Add to cart failed', 'result' => ''];
        }
    }

    /**
     * Get the total number of items in the shopping cart
          */
         public function getUserCartGoodsNum()
         {
             if ($this->user_id) {
                 $goods_num = Db::name('cart')->where(['user_id' => $this->user_id])->sum('goods_num');
             } else {
                 $goods_num = Db::name('cart')->where(['session_id' => $this->session_id])->sum('goods_num');
             }
             return $goods_num ? $goods_num : 0;
         }
     
         /**
          * Get how many items the user wants to buy in the shopping cart
          */
         public function getUserCartOrderCount(){
             if (!$this->user_id) {
                 return false;
             }
             $count = Db::name('Cart')->where(['user_id' => $this->user_id , 'selected' => 1])->count();
             return $count;
         }
     
         /**
          * Get the user's shopping cart list
          * 0 is all 1 is selected
     */
    public function cartList($selected = 0)
    {
        #If the user is already logged in, query by user id
        if ($this->user_id) {
            $cartWhere['user_id'] = $this->user_id;
        } else {
            $cartWhere['session_id'] = $this->session_id;
            $user['user_id'] = 0;
        }
        if ($selected == 1) {
            $cartWhere['selected'] = 1;
        }
        $cartList = DB::name('cart')->alias('a')->join('shop_lists b','b.id= a.shop_id')->field('a.*,b. name as shop_name')->where($cartWhere)->select()->toArray(); //Get shopping cart items
        $total_goods_num = $select_goods_num = $total_price = $cut_fee = 0;//Initialize data. Total Quantity of Items/Total Items/Savings
        foreach ($cartList as $k => $val) {
            # Calculate the total price of each item
            $val['goods_fee'] = $val['goods_num'] * $val['member_goods_price'];

            # Calculate the maximum number of purchases of each item
            $val['spec_num'] = $store_count = getGoodsNum($val['goods_id'], $val['spec_key']);
            $cartList[$k] = $val;
            #greater than the inventory force becomes the inventory number
            if ($store_count > 0 && $cartList[$k]['goods_num'] > $store_count) {
                $cartList[$k]['goods_num'] = $store_count;
                DB::name('Cart')->where('id', $val['id'])->update(['goods_num'=>$store_count]);
            }
            if ($store_count <= 0) {
                //No inventory, forced not to select
                DB::name('Cart')->where('id', $val['id'])->update(['selected' => 0]);
                $val['selected'] = 0;
            }
            #Only the shopping carts that are checked, skip the ones that are not checked
            if ($val['selected'] == 0){
                continue;
            }
            $total_goods_num += $val['goods_num'];
            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];
            $total_price += $val['goods_num'] * $val['member_goods_price'];
        }
        $total_price = ['total_fee' => $total_price, 'cut_fee' => $cut_fee, 'num' => $total_goods_num];
        //setcookie('cn', $total_goods_num, null, '/');
        return ['cartList' => $cartList, 'total_price' => $total_price];
    }
    /**
     * Operation after login
          * Change the unlogged user_id of the shopping cart to the user ID and merge the products
          */
         public function doLoginHandle($user_id)
         {
             if (!$user_id) {
                 return;
             }
             //Check if there is a shopping cart that is not logged in and saved
             $noLoginCart = Db::name('cart')->where(['session_id' => $this->session_id, 'user_id' => 0])->select();
             if ($noLoginCart) {
                 foreach($noLoginCart as $k => $v) {
                     // see if the same cart exists
                     if ($existCart = Db::name('cart')->where(['user_id' => $user_id, 'goods_id' => $v['goods_id'], 'spec_key' => $v['spec_key ']])->find()) {
                         //Increase the existing number
                         Db::name('cart')->where('id', $existCart['id'])->update(['goods_num' => $existCart['goods_num'] + $v['goods_num'] , 'selected' => $v['selected']]);
                         // and delete this entry
                         Db::name('cart')->delete($v['id']);
                     } else {
                         //If there is no same, just change user_id to user ID
                    Db::name('cart')->where('id', $v['id'])->update(['user_id' => $user_id]);
                }
            }
        }
    }

    
    /**
    * Directly modify the quantity
     * $id cart ID
     */
    public function editData($id, $num = 1)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => 'Shopping cart does not have this record'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => 'No permission to modify'];
        }
        if ($cart['prom_type'] > 0) {
            return ['status' => 0, 'msg' => 'The quantity of active items cannot be modified'];
        }
        $store_count = getGoodsNum($cart['goods_id'], $cart['spec_key']) ?: 0;
        if ($cart['goods_num'] + $num > $store_count) {
            return ['status' => 0, 'msg' => 'stock has reached the upper limit'];
        } else {
            Db::name('cart')->where('id', $id)->update(['goods_num'=>$num]);
            return ['status' => 1, 'msg' => 'Modify the product successfully'];
        }
    }

    /**
    * Increase the number of
     * $id cart ID
     */
    public function increase($id, $num = 1)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => 'Shopping cart does not have this record'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => 'No permission to modify'];
        }
        if ($cart['prom_type'] > 0) {
            return ['status' => 0, 'msg' => 'The quantity of active items cannot be modified'];
        }
        $store_count = getGoodsNum($cart['goods_id'], $cart['spec_key']) ?: 0;
        if ($cart['goods_num'] + $num > $store_count) {
            return ['status' => 0, 'msg' => 'stock has reached the upper limit'];
        } else {
            Db::name('cart')->where('id', $id)->inc('goods_num', $num)->update();
            return ['status' => 1, 'msg' => 'Add item successfully'];
        }
    }

    /**
     * reduce in quantity
     * $id cart ID
     */
    public function reduce($id, $num = 1)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => 'Shopping cart does not have this record'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => 'No permission to modify'];
        }
        if ($cart['prom_type'] > 0) {
            return ['status' => 0, 'msg' => 'The quantity of active items cannot be modified'];
        }
        if ($cart['goods_num'] - $num <= 0) {
            return ['status' => 0, 'msg' => 'The minimum number of shopping carts is one'];
        } else {
            Db::name('cart')->where('id', $id)->dec('goods_num', $num)->update();
            return ['status' => 1, 'msg' => 'Reduce commodity success'];
        }
    }
    /**
     * Toggle between checked and unchecked
     */
    public function changeSelected($id)
    {
        $cart = Db::name('cart')->where('id', $id)->find();
        if (!$cart) {
            return ['status' => 0, 'msg' => 'Shopping cart does not have this record'];
        }
        if ($cart['session_id'] != $this->session_id && $cart['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => 'No permission to modify'];
        }
        $selected = $cart['selected'] ? 0 : 1;
        Db::name('cart')->where('id', $id)->update(['selected' => $selected]);
        return ['status' => 1, 'msg' => 'Modified successfully'];
    }
    /**
     * select all
     */
    public function changeSelectedAll($selected, $shop_id)
    {
        $selected = $selected == 1 ? 1 : 0;
        $map = [];
        if($shop_id){$map = ['shop_id'=>$shop_id];}
        if ($this->user_id) {
            
            Db::name('cart')->where($map)->where(['user_id' => $this->user_id])->update(['selected' => $selected]);
        } else {
            Db::name('cart')->where($map)->where(['session_id' => $this->session_id])->update(['selected' => $selected]);
        }
        return ['status' => 1, 'msg' => 'Modified successfully'];
    }
    /**
     * Remove from cart
     */
    public function remove($id)
    {
        $info = Db::name('cart')->where('id', $id)->find();
        if (!$info) {
           return ['status' => 0, 'msg' => 'The shopping cart does not have this item'];
        }
        if ($info['session_id'] != $this->session_id && $info['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => 'No permission to modify'];
        }
        Db::name('cart')->delete($id);
        return ['status' => 1, 'msg' => 'Successfully removed the item'];
    }

    /**
     * Modify shopping cart product specifications
     */
    public function change_cart_spec($id, $spec_key, $num)
    {
        $info = Db::name('cart')->where('id', $id)->find();
        if (!$info) {
            return ['status' => 0, 'msg' => 'Shopping cart does not have this item'];
        }
        if ($info['session_id'] != $this->session_id && $info['user_id'] != $this->user_id) {
            return ['status' => 0, 'msg' => 'No permission to modify'];
        }
        $this->setGoods(Goods::get($info['goods_id']));
        $goods_image = $this->goods->original_img;
        $goodsSpec = Db::name('spec_goods')->where('goods_id', $this->goods->id)->where('key', $spec_key)->find();
        if (!$goodsSpec) {
            return ['status' => 0, 'msg' => 'No such item found'];
        }
        $goodsStore = $goodsSpec['store_count'] ? $goodsSpec['store_count'] : $this->goods->store_count;//If there is no specification stock, go according to the stock of the item table
        $goodsPrice = $goodsSpec['price'] ? $goodsSpec['price'] : $this->goods->shop_price;//If there is no specification price, follow the price of the product list
        $img = Db::name('spec_image')->where(['goods_id' =>$this->goods->id, 'spec_item_id' => ['in', explode('_', $spec_key) ]])->value('src');
        if ($img) {
            //Use the image of this specification
            $goods_image = $img;
        }
        if($num > $goodsStore){
            return ['status' => 0, 'msg' => 'Insufficient goods in stock, remaining'.$goodsStore];
        }
        $update = [
            'goods_price' => $goodsPrice,
            'member_goods_price' => $goodsPrice,
            'goods_num' => $num,
            'spec_key' => $spec_key,
            'spec_key_name' => $goodsSpec['key_name'],
            'goods_img' => $goods_image,

        ];
        $res = Db::name('cart')->where('id', $id)->update($update);
        if ($res !== false) {
            return ['status' => 1, 'msg' => 'Modified successfully'];
        }
        return ['status' => 0, 'msg' => 'modification failed'];
    }
}