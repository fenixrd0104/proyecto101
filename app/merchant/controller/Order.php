<?php

namespace app\merchant\controller;
use app\merchant\model\GoodsCategory;
use app\merchant\model\MemberModel;
use app\common\model\PayConfig;
use think\facade\Db;
use app\merchant\model\RegionModel;
use app\merchant\model\GoodsBrand;
use app\common\model\Order as OrderModel;
use app\common\model\Member;
use think\Loader;
class Order extends Base
{
    //The order can be generated with an action button
        private function ordermanagerbtn($order_status, $pay_status, $shipping_status) {
            //summary of action buttons
            $btnConfig = config('shoporder.MANAGE_ORDER_BTN');
            $btn = [];
            if ($order_status == 0 && $pay_status == 0) {
                //Unconfirmed + Unpaid ---> Payment
                $btn['pay'] = $btnConfig['pay'];
            } elseif ($order_status == 0 && ($pay_status == 1 || $pay_status == 4)) {
                //Unconfirmed + paid ---> set as unpaid Confirmed
                //$btn['pay_cancel'] = $btnConfig['pay_cancel'];
                $btn['confirm'] = $btnConfig['confirm'];
            } elseif ($order_status == 1 && $pay_status == 1 && $shipping_status == 0) {
            // } elseif ($order_status == 1 && ($pay_status == 1 || $pay_status == 4) && $shipping_status == 0) {
                // not shipped
                $btn['cancel'] = $btnConfig['cancel'];
                $btn['delivery'] = $btnConfig['delivery'];
            }
            if ($shipping_status == 1 && $order_status == 1 && ($pay_status == 1 || $pay_status == 4)) {
                // Shipped + Paid + Paid ---> Confirm receipt Apply for return
                //$btn['delivery_confirm'] = $btnConfig['delivery_confirm'];
                //$btn['refund'] = $btnConfig['refund'];
            } elseif ($order_status == 2 || $order_status == 4) {
                //Received or completed ---> apply for return
                //$btn['refund'] = $btnConfig['refund'];
            } elseif ($order_status == 3 || $order_status == 5) {
                //Cancelled Cancelled ---> Removed
            $btn['remove'] = $btnConfig['remove'];
        }
        if ($order_status != 5) {
           //All orders that are not void are added an invalid
                       $btn['invalid'] = $btnConfig['invalid'];
                   }
                   return $btn;
               }
               //Find sn according to the order
               private function getOrderSnForOrderId($id){
                   $map['order_id']=$id;
                   if($this->shopId){
                       $map['shop_id']=$this->shopId;
                   }
                  return Db::name('order')->where($map)->value('order_sn');
               }
           
               //Order List
               public function index()
               {
                   $data = input('get.');
                   //Assemble filter conditions
        $map = [];
        $map[]=['shop_id','=',$this->shopId];
        $map[] =['deleted','=',0];
        //Sort condition
        $order = 'order_id-0';//default

        !empty($data['start_time']) && $map[] = ['add_time','>=',strtotime($data['start_time'])];
        !empty($data['end_time']) && $map[] = ['add_time','<=',strtotime($data['end_time'])];
        /* if(!empty($data['end_time'])){
             if(!empty($where2)){
                 $where2 .= ' and add_time <='.strtotime($data['end_time']);
             }else{
                 $where2 = 'add_time <='.strtotime($data['end_time']);
             }
         }*/
        isset($data['pay_status']) && $data['pay_status'] !== '' && $map[] = ['pay_status','=',$data['pay_status']];

        !empty($data['pay_name'])          && $map[] = ['pay_name','=',$data['pay_name']];

        isset($data['shipping_status']) && $data['shipping_status'] !== ''  && $map[] = ['shipping_status','=',$data['shipping_status']];

        isset($data['order_status'])  && $data['order_status'] !== ''    && $map[] = ['order_status','=',$data['order_status']];

        !empty($data['keywords'])          && $map[] = ['consignee|order_sn','like', '%' . $data['keywords'] . '%'];
        //sort condition
        if (isset($data['order']) && !empty($data['order'])) {
            $order = $data['order'];
        }

        //sort condition
        $order = explode('-', $order);
        $orderStr = $order[1] == 1 ? "$order[0] asc" : "$order[0] desc";
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);

        $count = OrderModel::where($map)->count();
//        return json(['code'=>1,'msg'=>'111']);
        $list = OrderModel::order($orderStr)->where($map)->page($Nowpage,$limits)->select();
        $sum =  OrderModel::order($orderStr)->field('sum(total_amount) as total_amount,sum(order_amount) as order_amount,sum(integral_money) as integral_money,sum(user_money) as user_money')->where($map)->find();
        //payment method
        $PayConfig =   new PayConfig();
        $pay_name =  $PayConfig->getKeyVal([]);


        return json(['code'=>1,'data'=>[
            'sum' =>$sum,
            'count' =>$count,
            'list' => $list,
            'order_name' => $order[0],
            'order_sort' => $order[1],
            'pay_name' => $pay_name,
            'order_status' => new \ArrayObject(config('shoporder.ORDER_STATUS')),
            'pay_status' => new \ArrayObject(config('shoporder.PAY_STATUS')),
            'shipping_status' => new \ArrayObject(config('shoporder.SHIPPING_STATUS')),

        ],'msg'=>'']);

    }
    //order details
    public function detail(OrderModel $order, $id)
    {
        $map['order_id']=$id;
        $map['shop_id']=$this->shopId;
        $orderInfo = $order::where($map)->find();
        if (!$orderInfo) {
            return json(['code'=>0,'data'=>[],'msg'=>'Check this order']);
        }
        $goodsList = Db::name('order_goods')->where('order_id', $id)->select();
        //Reading delivery address information
        $region    = Db::name('region')->column('name','id');
        $orderInfo->addr = '';
        isset($region[$orderInfo->province]) && $orderInfo->addr .= $region[$orderInfo->province] . '，';
        isset($region[$orderInfo->city])     && $orderInfo->addr .= $region[$orderInfo->city] . '，';
        isset($region[$orderInfo->district]) && $orderInfo->addr .= $region[$orderInfo->district] . '，';
        isset($region[$orderInfo->twon])     && $orderInfo->addr .= $region[$orderInfo->twon] . '，';
        $orderInfo->address                  && $orderInfo->addr .= $orderInfo->address;

       //Order operation record
        $orderAction = Db::name('order_action')->where('order_id', $id)->order('action_id desc')->select();
        $member = new MemberModel();
        foreach ($orderAction as $k => &$v) {
            $v['action_user'] && $v['user_name'] = $member->where('id', $v['action_user'])->value('nickname');
        }
        //The button that the administrator can operate the order
        $orderManagerBtn = $this->orderManagerBtn($orderInfo->order_status, $orderInfo->pay_status, $orderInfo->shipping_status);
        //Get by logistics
        $shipping = Db::name('shipping')->where('status', 1)->order('id asc')->select();
        //payment method
        $pay_lists = Db::name('pay_config')->where(['status'=>1])->column('name','id');
        return json(['code'=>1,'data'=>[
            'info' => $orderInfo,
            'goodsList' => $goodsList,
            'orderAction' => $orderAction,
            'orderManagerBtn' => $orderManagerBtn,
            'shipping' => $shipping,
            'pay_lists' => $pay_lists,
            'order_status' => new \ArrayObject(config('shoporder.ORDER_STATUS')),
            'pay_status' => new \ArrayObject(config('shoporder.PAY_STATUS')),
            'shipping_status' => new \ArrayObject(config('shoporder.SHIPPING_STATUS')),
        ],'msg'=>'']);
    }
    // set as payment
    public function orderPay(OrderModel $orderModel,$id,$pay_id,$note){
        $order_sn = $this->getOrderSnForOrderId($id);
        if(!$order_sn){
            return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        // method to call order
        $res = $orderModel->update_pay_status($order_sn, ['is_admin' => $this->mid, 'note' => $note,'pay_code'=>$pay_id]);
        return json($res);
    }
    // cancel payment
    public function orderPayCancel(OrderModel $orderModel,$id,$note){
        $order_sn = $this->getOrderSnForOrderId($id);
        if(!$order_sn){
            return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        $res = $orderModel->update_paycancel_status($order_sn, ['is_admin' => $this->mid, 'note' => $note]);
        return json($res);
    }
    // assign store
    public function orderConfirmShop(OrderModel $orderModel,$id,$shop_id,$note){
        $order_sn    = $this->getOrderSnForOrderId($id);
        if(!$order_sn){
          return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        //Do you need to add another one? Does he have the ability to manage this store?
        // method to call order
        $res = $orderModel->confirm_order_shop($order_sn, ['is_admin' => $this->mid, 'note' => $note,'shop_id'=>$shop_id]);
        return json($res);
    }
    //Unassign store
    public function orderConfirmShopCancel(OrderModel $orderModel,$id,$note){
        $order_sn = $this->getOrderSnForOrderId($id);
        if(!$order_sn){
            return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        $res =$orderModel->confirm_order_shop_cancel($order_sn,['is_admin'=>$this->mid,'note'=>$note]);
        return json($res);
    }
    //Ship                                           order_id   5438   type
    public function orderDelivery(OrderModel $orderModel, $id,$invoice_no,$shipping_id)
    {
        $order_sn    = $this->getOrderSnForOrderId($id);
        if(!$order_sn){
          return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
       $res = $orderModel->updata_shoping_delivery($order_sn,$invoice_no,$shipping_id,$this->mid);
       return json($res);
    }
    //confirm the receipt of goods
    public function orderDeliveryConfirm(OrderModel $orderModel,$id,$note){
        $order_sn = $this->getOrderSnForOrderId($id);
        if(!$order_sn){
            return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        $res = $orderModel->confirm_order($order_sn, ['is_admin' => $this->mid, 'note' => $note]);
        return json($res);
    }
    //confirm
    public function orderConfirm($id){
        $order_status=Db::name('order')->where('order_id', $id)->value('order_status');
        if($order_status==0){
            Db::name('order')->where('order_id', $id)->update(['order_status' => 1]);
            return json(['code'=>1,'data'=>[],'msg'=>'confirmed success']);
        }else{
            Db::name('order')->where('order_id', $id)->update(['order_status' => 0]);
            return json(['code'=>1,'data'=>[],'msg'=>'cancelled confirmation']);
        }
    }
    // cancel the order
        public function orderInvalid(OrderModel $orderModel,$id,$note){
            $order_sn = $this->getOrderSnForOrderId($id);
            if(!$order_sn){
                return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
            }
            $res = $orderModel->orderInvalid($id,$note,$this->mid);
            return json($res);
    
        }
        // remove order
        public function orderRemove(OrderModel $orderModel,$id,$note){
            $order_sn = $this->getOrderSnForOrderId($id);
            if(!$order_sn){
                return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
            }
            $res = $orderModel->delOrder($id,$note,$this->mid);
            return json($res);
        }
    
    
    
        // modify the cost
    public function editprice(OrderModel $order, $id,$order_amount=0,$shipping_price=0)
    {

        $map['order_id']=$id;
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $orderInfo = $order::where($map)->find();
        if (!$orderInfo) {
            return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        //The paid cannot be modified
        if($orderInfo->pay_status != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'Payment order cannot modify the price']);
        }
        //The delivered one cannot be modified
        if($orderInfo->shipping_status != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'The delivery order cannot modify the price']);
        }

        if ($this->request->isPost()) {


            if ($shipping_price < 0) {
                return json(['code' => 0, 'msg' => 'Logistics price cannot be negative']);
            }
            if($order_amount < 0){
                return json(['code'=>0,'data'=>[],'msg'=>'The total price of the product cannot be negative']);
            }

            $discount=$order_amount-$orderInfo->order_amount;
            $total_amount=$order_amount+$shipping_price;

            $update = [
                'total_amount'   => $total_amount,
                'order_amount'   => $order_amount,
                'shipping_price' => $shipping_price,
                'discount'       => $discount
            ];
            Db::name('order')->where('order_id', $id)->update($update);
            //operation log
            $order->logOrder($id, 'Administrator to modify order fee', 'Modify fee', $this->mid,1);
            return json(['code' => 1, 'msg' => 'Modified successfully']);

        }
        return json(['code'=>1,'data'=>[
            'total_amount'   => $orderInfo->total_amount,
            'order_amount'   => $orderInfo->order_amount,
            'shipping_price' => $orderInfo->shipping_price,
            'discount'       => $orderInfo->discount,
            'integral_money'       => $orderInfo->integral_money,
            'user_money'       => $orderInfo->user_money,
        ],'msg'=>'']);
    }

    public function editConsignee(OrderModel $order, $id)
    {

        $map['order_id']=$id;
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        $orderInfo = $order::where($map)->find();
        if (!$orderInfo) {
           return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        //The delivered one cannot be modified
        if($orderInfo->shipping_status != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'shipping order cannot be modified']);
        }

        if ($this->request->isPost()) {

            $data=input('post.');
            $orderInfo->allowField(['consignee','country','province','city','district','twon','address','zipcode','mobile'])->save($data );
            //operation log
            $order->logOrder($id, 'Administrator modifies receipt information', 'Modifies receipt information', $this->mid,1);
            return json(['code' => 1, 'msg' => 'Modified successfully']);

        }
   /*     return json(['code'=>1,'data'=>[
            'total_amount'   => $orderInfo->total_amount,
            'order_amount'   => $orderInfo->order_amount,
            'shipping_price' => $orderInfo->shipping_price,
            'discount'       => $orderInfo->discount,
            'integral_money'       => $orderInfo->integral_money,
            'user_money'       => $orderInfo->user_money,
        ],'msg'=>'']);*/
    }









   //edit order
    public function editorder(OrderModel $order, $id)
    {
        $orderInfo = $order::find($id);
        if (!$orderInfo) {
            return json(['code'=>0,'data'=>[],'msg'=>'no such order']);
        }
        if ($orderInfo->order_status >= 2) {
            return json(['code'=>0,'data'=>[],'msg'=>'This order is not allowed to be modified']);
        }
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('OrderValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            //add order method
            $model = new OrderModel();
            $address = ['consignee' => $data['consignee'],
                'province'  => $data['province'],
                'city'      => $data['city'],
                'district'  => $data['district'],
                'address'   => $data['address'],
                'mobile'    => $data['mobile']
            ];
            $res = $model->adminUpdateOrder($id, $address, $data['pay_code'], $data['goods_list'], $data['invoice_title'], $data['admin_note']);
            return json(['code' => $res['status'], 'msg' => $res['msg']]);
        }
       //address
        $model = new RegionModel();
        $province = $model->getsubList(0);

        //Get by logistics
        $shipping = Db::name('shipping')->where('status', 1)->order('id asc')->select();
        //payment method
        $pay_lists = Db::name('pay_config')->where(['status'=>1])->column('name','id');

        //Classification
        $GoodsCategory = new GoodsCategory();
        $categoryList =$GoodsCategory->getCategorySon();
        $brandList = GoodsBrand::order('order asc')->select();
        //Construct the front-end product array json
        $goods_list = Db::name('order_goods')->where('order_id', $id)->select();
        $_goods_list = [];
        foreach ($goods_list as $k => $v) {
            $key = $v['spec_key'] ? $v['goods_id'] . ':' . $v['spec_key'] : $v['goods_id'];
            //check inventory
           /* if ($v['spec_key']) {
                $store_count = Db::name('spec_goods')->where(['goods_id' => $v['goods_id'], 'key' => $v['spec_key']])->value('store_count');
            } else {
                $store_count = Db::name('goods')->where('id', $v['goods_id'])->value('store_count');
            }*/
            $store_count = 0;
            $val = ['goods_id'    => $v['goods_id'],
                    'spec_key'         => $v['spec_key'],
                    'goods_name'  => $v['goods_name'],
                    'key_name'    => $v['spec_key_name'],
                    'shop_price'  => $v['member_goods_price'],
                    'store_count' => $store_count,
                    'goods_num'   => $v['goods_num'],
                    ];
            $_goods_list[$key] = $val;
        }
        return json(['code'=>1,'data'=>['province' => $province, 'pay_lists' => $pay_lists, 'shipping' => $shipping, 'categoryList' => $categoryList, 'brandList' => $brandList, 'orderInfo' => $orderInfo, 'goods_list' => json_encode($_goods_list)],'msg'=>'']);

    }
    //Add order
    public function addorder()
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $validate = \think\Loader::validate('OrderValidate');
            if (!$validate->check($data)) {
                return json(['code' => 0, 'msg' => $validate->getError()]);
            }
            //Add order method
            $model = new OrderModel();
            $address = ['consignee' => $data['consignee'],
                        'province'  => $data['province'],
                        'city'      => $data['city'],
                        'district'  => $data['district'],
                        'address'   => $data['address'],
                        'mobile'    => $data['mobile']
                        ];
            $res = $model->adminAddOrder($address, $data['pay_code'], $data['goods_list'], $data['invoice_title'], $data['admin_note'], $data['user_id']);
            return json(['code' => $res['status'], 'msg' => $res['msg']]);
        }
        //address
        $model = new RegionModel();
        $province = $model->getsubList(0);
        //Delivery Method
        $shipping_name = Db::name('shipping')->where('enabled', 1)->order('id asc')->select();
        //payment method
        $pay_name = Db::name('pay_config')->where('status', 1)->order('id asc')->select();
        //Classification
        $categoryList = model('GoodsCategory')->getCategorySon();
        $brandList = GoodsBrand::order('order asc')->select();
        return view('', ['province' => $province, 'pay_name' => $pay_name, 'shipping_name' => $shipping_name, 'categoryList' => $categoryList, 'brandList' => $brandList]);
    }


    /**
    * Return management
     *Single item of order
     * @param $rec_id order_goods self-incrementing primary key used to identify which product to return
     * @param $refund_type Refund type 0 refund only 1 return refund
     * @param $imgs image
     * @param $reason reason for refund
     * @param $reason problem description
     */
    public function returngoods()
    {
        $map=[];
        $map[] = ['shop_id','=',$this->shopId];
        $status_list = input('get.status_list','');
        $refund_type = input('get.refund_type','');
        $order_sn = input('get.order_sn','');
        if($status_list != ''){
            $map[]=['status','=',$status_list];
        }
        if($refund_type != ''){
            $map[]=['refund_type','=',$refund_type];
        }

        if($order_sn != ''){
            $map[]=['order_sn','like','%'.$refund_type.'%'];
        }
        $limits = input('get.limit',15);
        $list = Db::name('return_goods')->where($map)->order('addtime desc')->paginate($limits);
        $data = $list->toArray();
        return json(['code'=>1,'data'=> ['lists' => $data['data'],'count'=>$data['total'],'refund_type'=>new \ArrayObject([0=>'Refund only', 1=>'Return refund']),'status_list'=>new \ArrayObject(['-2'=>'User canceled','-1'= >'Review failed', 0=>'Pending review', 1=>'Review passed'])],'msg'=>'']);
    }
    public function shenhe($id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            $find = DB::name('return_goods')->where('id', $id)->find();
            $status =$find['status'];
            if ($status != 0) {
              return json(['code' => 0, 'msg' => 'The refund application has been processed and cannot be modified']);
            }
            if($find['refund_confirm'] != 2 && $find['refund_type'] == 1){
                return json(['code' => 0, 'msg' => 'Waiting for the user to continue returning']);
            }
            //Update the refund details
            $update = [
                'refund_money'    => $data['refund_money'],
                'refund_deposit'  => $data['refund_deposit'],
                'refund_integral' => $data['refund_integral'],
                'remark'          => $data['note']
            ];
            DB::name('return_goods')->where('id', $id)->update($update);
            //status update again
            $OrderCommon = new \app\common\model\Order();
            $res = $OrderCommon->returnGoodsStatus($id, $data['status']);
            return json($res);
        }
        $info = DB::name('return_goods')->where('id', $id)->find();
        return json(['code'=>1,'data'=>['info'=>$info,'refund_type'=>new \ArrayObject([0=>'Refund only', 1=>'Return refund']),'status_list'=>new \ArrayObject(['-2'=>'User canceled','-1'= >'Review failed', 0=>'Pending review', 1=>'Review passed'])],'msg'=>'']);

    }

    //confirm return
    public function return_confirm($id){
        if ($this->request->isPost()) {
            $data = input('post.');
            $status = DB::name('return_goods')->where('id', $data['id'])->find();
            if ($status['status'] != 0) {
                return json(['code' => 0, 'msg' => 'The application has been processed and cannot be modified']);
            }
            if($status['refund_confirm'] == 1){
                return json(['code' => 0, 'msg' => 'The return request has been processed and cannot be modified']);
            }
            if($data['status'] == -1){
                //status update again
                $OrderCommon = new \app\common\model\Order();
                $res = $OrderCommon->returnGoodsStatus($id, $data['status']);
                return json($res);
            }
            //Update the refund details
            $update = [
                'shop_addr' => $data['shop_addr'],
                'remark' => $data['note'],
                'refund_confirm'=>1
            ];
            DB::name('return_goods')->where('id', $data['id'])->update($update);
            // and then update the status
            
            return json(['code' => 1, 'msg' => 'Confirmed success']);
        }
        
    }









  /*  public function finduser($search)
    {
        $member = new Member();
        $map['account|nickname']  = ['like', '%' . $search . '%'];
        $list = $member->where($map)->select();
        return json(['code' => 1, 'msg' => $list]);
    }

    public function getregionsublist($id)
    {
        $model = new RegionModel();
        $list = $model->getsubList($id);
        return json(['code' => 1, 'msg' => $list]);
    }

    public function getgoodslist()
    {
        $data  = input('get.');
        $model = new Goods();
        $res = $model->getGoodsList($data['cat_id'], $data['brand_id'], $data['recom_type'], $data['keywords'], 1, 1);
        if ($res['code']) {
            return json(['code' => 1, 'msg' => $res['data']]);
        }
        return json(['code' => 0, 'msg' => '没有数据']);
    }*/

}