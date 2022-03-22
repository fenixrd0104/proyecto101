<?php
/**
 * checkout counter
 */

namespace app\admin\controller;
use app\admin\model\GoodsStock;
use app\admin\model\MemberModel;
use app\admin\model\PurchaseReceiptListsModel;
use app\admin\model\ShopBIllModel;
use app\admin\model\ShopListsModel;
use app\admin\model\ShopOrder;
use app\admin\model\ShopOrderListsModel;
use app\admin\model\ShopOrderModel;
use app\admin\model\ShopPayConfigModel;
use app\admin\model\StockReceiptLogModel;
use app\admin\model\UserModel;
use app\common\model\IntegralLog;
use app\common\model\MoneyLog;
use app\common\service\Payment;
use think\Exception;
use think\facade\Db;

class Cashier extends Base
{

    //front page
    public function index(){
        $ShopOrderModel = new ShopOrderModel();
        $map=[];
        $shopId = input('shopId',0);
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }else if ($shopId){
            $map[]=['think_goods_stock.shop_id','=',$shopId];
        }

        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        $orderStatus = input('get.orderStatus','');
        $payStatus = input('get.payStatus','');
        if($keyWords != ''){
              $map[]=['think_shop_order.order_id|think_member.account|think_shop_order.user_id|sale_uid|sale_account|cashier_uid|cashier_account|think_shop_lists.name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_shop_order.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_shop_order.create_time','<',strtotime($endTime)];
        }
        if($orderStatus != ''){
            $map[]=['think_shop_order.order_status','=',$orderStatus];
        }
        if($payStatus != ''){
            $map[]=['think_shop_order.pay_status','=',$payStatus];
        }

        $limits = input('get.limit',10);
        $res = $ShopOrderModel->getPaginate($map,$limits);
        $sum = $ShopOrderModel->getSumArr($map);
        $lists = $res->toArray();
        $ShopListsModel = new ShopListsModel();
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'shop_lists'=>$ShopListsModel->getKeyVal($shop_map),'orderStatus'=>new \ArrayObject($ShopOrderModel->orderStatus),'payStatus'=>new \ArrayObject($ShopOrderModel->payStatus)],'msg'=>'']);
    }
    //Choose a member
    public function getUserLists(){
        $key = input('keyWords');
        $map[] =['closed','=',0];//0 not deleted, 1 deleted
        if($key&&$key!=="")
        {
            $map[] = ['account|nickname|mobile','like',"%" . $key . "%"];
        }
        $member = new MemberModel();
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count = $member->getAllCount($map);//Calculate total pages
        $lists = $member->getMemberByWhere($map, $Nowpage, $limits);
        return json(['code'=>1,'data'=>['lists'=>$lists,'count'=>$count],'msg'=>'']);
    }

    //select user by id
    public function getUserById(){
        $id = input('get.uid',0);
        if($id == 0){
			return json(['code'=>0,'data'=>[],'msg'=>'user ID is empty']);
        }
        $info = Db::name('member')->where('id','=',$id)->find();
        if(!$info){
            return json(['code'=>0,'data'=>[],'msg'=>'The user was not found']);
        }
        return json(['code'=>1,'data'=>['id'=>$id,'account'=>$info['account']],'msg'=>'']);
    }
    public function getUserByPhone(){
        $id = input('get.uid',0);
        if($id == 0){
			return json(['code'=>0,'data'=>[],'msg'=>'user ID is empty']);
        }
        $info = Db::name('member')->where('mobile','=',$id)->find();
        if(!$info){
            return json(['code'=>0,'data'=>[],'msg'=>'The user was not found']);
        }
        return json(['code'=>1,'data'=>['id'=>$info['id'],'account'=>$info['account']],'msg'=>'']);
    }

    //Choose to sell
    public function getWorkerLists(){
        $UserModel = new UserModel();
        $keyWords = input('get.keyWords','');
        $map = [];
        $map[]=['shop_id','>',0];
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }
        if($keyWords&&$keyWords!=="")
        {
            $map[] = ['username|real_name','like',"%" . $keyWords . "%"];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',10);
        $count =  $UserModel->where($map)->count();
        $lists =  $UserModel->field('think_admin.id,username,real_name,shop_id,think_shop_lists.name shop_name')->join('think_shop_lists','think_shop_lists.id=think_admin.shop_id','left')->where($map)->page($Nowpage,$limits)->select();
        return json(['code'=>1,'data'=>['lists'=>$lists,'count'=>$count],'msg'=>'']);
    }

    //Select sales based on UID
    public function getWorkerById(){
        $id = input('get.uid',0);
        if($id == 0){
            return json(['code'=>0,'data'=>[],'msg'=>'User ID is empty']);
        }
        $map = [];
        $map[]=['shop_id','>',0];
        $map[]=['id','=',$id];
        $info = Db::name('admin')->where($map)->find();
        if(!$info){
            return json(['code'=>0,'data'=>[],'msg'=>'clerk not found']);
        }
        return json(['code'=>1,'data'=>['id'=>$id,'username'=>$info['real_name']],'msg'=>'']);
    }
    //Choose an item
    public function getSpecStock($spec_id){
        $GoodsStock =  new GoodsStock();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
		// Check first if it exists
        $stock = $GoodsStock->getOneStockDetails($shop_id,$spec_id);
        // Update if it exists
        if($stock){
            unset($stock['trade_price']);
            unset($stock['cost_price']);
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);
        }else{
            return json(['code'=>0,'data'=>[],'msg'=>'No stock of this specification']);
        }
    }

    public function searchByGoodsSku($spec_sku){
        $GoodsStock =  new GoodsStock();
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
		// Check first if it exists
        $stock = $GoodsStock->getOneStockDetailsBySku($shop_id,$spec_sku);
        // Update if it exists
        if($stock){
            unset($stock['trade_price']);
            unset($stock['cost_price']);
            return json(['code'=>1,'data'=>$stock,'msg'=>'']);
        }else{
            return json(['code'=>0,'data'=>[],'msg'=>'No stock with this specification']);
        }
    }


    /*    public function close(){
        $Payment = new Payment();
        $res2 = $Payment->wx_close('15660408225196');

        if($res2['code'] == 1){
            //Change the payment status to refund
            Db::name('pay_log')->where(['out_trade_no'=>'15660399678766'])->update(['status'=>2]);
        }
       $res2 = $Payment->ali_close('15660399678766');

        if(isset($res2['data']['action']) && $res2['data']['action']== 'refund'){
            //Change the payment status to refund
            Db::name('pay_log')->where(['out_trade_no'=>'15660399678766'])->update(['status'=>2]);
        }
        pe($res2);
    }*/
    public function getPayStatus($order_id){

      $info = Db::name('pay_log')->where(['order_number'=>$order_id])->order('id desc')->find();
      if($info['status'] != 0){
          return json(['code'=>0,'data'=>[],'msg'=>'The order has been processed']);
      }
      $Payment = new Payment();
      $ShopOrderModel =  new ShopOrderModel();
      $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
      if($info['pay_type'] == 25){
          $res = $Payment->ali_query($info['out_trade_no']);
          if($res['code'] == 0){
              return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
          }
          $data = $res['data'];
          if(strtoupper($data['trade_status']) == 'TRADE_SUCCESS'){
              //Change the order status and change the Alipay package
              Db::name('pay_log')->where(['out_trade_no'=>$data['out_trade_no']])->update(['status'=>1]);
              $ShopBIllModel = new ShopBIllModel();
              $order_info = Db::name('shop_order')->where(['order_id'=>$order_id])->find();
              //Add to a payment reconciliation table
              $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,2,'Alipay scan code',$order_info['order_amount'],$order_info['user_id'],$order_info['user_account'],'Alipay scan code');
              $ShopOrderModel->payOk($order_id,2,'Alipay scan code');

              return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
          }
          if(strtoupper($data['trade_status']) == 'WAIT_BUYER_PAY' && $info['create_time']+30 < time()){
              //call close order and return trade close
              $res2 = $Payment->ali_close($info['out_trade_no']);
              if($res2['code'] == 1){
                  if(isset($res2['data']['action']) && $res2['data']['action']== 'refund'){
                     //When the user has paid, the payment status is changed to refund
                      Db::name('pay_log')->where(['out_trade_no'=>$data['out_trade_no']])->update(['status'=>2]);
                  }
                  return json(['code'=>0,'data'=>[],'msg'=>'Trading closed']);
              }
          }
			//The payment is in progress, let the user wait
          if(strtoupper($data['trade_status']) == 'WAIT_BUYER_PAY'){
              return json(['code'=>2,'data'=>[],'msg'=>'waiting for buyers payment']);
          }
          // error, close directly
          return json(['code'=>0,'data'=>[],'msg'=>'transaction closed']);
      }else{
          $res = $Payment->wx_query($info['out_trade_no']);
          if($res['code'] == 0){
              return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
          }
          $data = $res['data'];
          if(strtoupper($data['trade_state']) == 'SUCCESS'){
			  //Change the order status and change the Alipay package
              Db::name('pay_log')->where(['out_trade_no'=>$data['out_trade_no']])->update(['status'=>1]);

              $ShopBIllModel = new ShopBIllModel();
              $order_info = Db::name('shop_order')->where(['order_id'=>$order_id])->find();
              //Add to a payment reconciliation table
              $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,3,'WeChat scan code',$order_info['order_amount'],$order_info['user_id'],$order_info['user_account'],'WeChat scan code');

			$ShopOrderModel->payOk($order_id,3,'WeChat scan code');

              return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
          }
          if(strtoupper($data['trade_state']) == 'USERPAYING' && $info['create_time']+10 < time()){
				//call close order and return trade close
              $res2 = $Payment->wx_close($info['out_trade_no']);
              if($res2['code'] == 1){
                  if(isset($res2['data']['action']) && $res2['data']['action']== 'refund'){
                      //When the user has paid, the payment status is changed to refund
                      Db::name('pay_log')->where(['out_trade_no'=>$data['out_trade_no']])->update(['status'=>2]);
                  }
                  return json(['code'=>0,'data'=>[],'msg'=>'Trading closed']);
              }
          }
          //The payment is in progress, let the user wait
          if(strtoupper($data['trade_state']) == 'USERPAYING'){
              return json(['code'=>2,'data'=>[],'msg'=>$data['trade_state_desc']]);
          }
         // error, close directly
          return json(['code'=>0,'data'=>[],'msg'=>$data['trade_state_desc']]);
      }




    }
   // get payment option
    public function getPayConfig($order_id,$pay_id=0,$auth_code=''){

        if(request()->isPost()){
            $ShopOrderModel = new ShopOrderModel();
            $shop_id = $this->shopId;
            if(!$shop_id){
                $shop_id=config('config.shop_default_manage');
            }
            $order_info = $ShopOrderModel->where(['order_id'=>$order_id,'shop_id'=>$shop_id])->find();
            if($order_info->getData('order_status') != 1 || $order_info->getData('pay_status') != 0  ){
                return json(['code'=>0,'data'=>[],'msg'=>'Current status cannot be paid']);
            }
            //Determine whether the payment is the payment method of this store.
            $ShopPayConfigModel = new ShopPayConfigModel();
            $pay_arr = $ShopPayConfigModel->where('shop_id','=',$shop_id)->whereOr("shop_id",'=',0)->column('id,name,type','id');
            if(!key_exists($pay_id,$pay_arr)){
                return json(['code'=>0,'data'=>[],'msg'=>'The current payment method does not exist']);
            }
            //Determine whether it is currently online or offline
            if($pay_arr[$pay_id]['type'] > 0 ){

				// balance payment
                if($pay_arr[$pay_id]['id'] == 1){
                    //First determine how much money there is currently to pay
                    // Less than 0 means that the payment is no longer required
                    if($order_info->order_amount <= 0){
                        //The payment is directly changed to success
                        $ShopOrderModel->payOk($order_id,$pay_id,$pay_arr[$pay_id]['name']);
                        return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
                    }else{
                        //If it is greater than 0, do we judge that the user still has so much money?
                        $user=[
                            'id'=>0,
                            'money'=>'0.00',
                            'integral'=>0,
                        ];
                        //First check whether there is a UID and a balance
                        if($order_info->user_id){
                            $user =  Db::name('member')->field('id,money,integral')->where(['id'=>$order_info->user_id])->find();
                        }
                        if($user['id'] == 0){
                            return json(['code'=>0,'data'=>[],'msg'=>'User account does not exist']);
                        }
                        //Determine whether the user's balance is sufficient
                        if($order_info->order_amount > $user['money']){
                            return json(['code'=>0,'data'=>[],'msg'=>'Users available balance is not enough']);
                        }
                        // Debit if enough
                        MoneyLog::setDec($order_info->user_id,$order_info->order_amount);
                        MoneyLog::operate($order_info->user_id,-$order_info->order_amount,21,1,'Store purchases deductions',$this->userId);
                        //Order balance payment is adding current
                        $order_info->user_money=$order_info->user_money+$order_info->order_amount;
                        $order_info->order_amount=0;
                        if($order_info->save()){
                            $ShopOrderModel->payOk($order_id,$pay_id,$pay_arr[$pay_id]['name']);
                            return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
                        }

                    }

                //Alipay payment
                }elseif($pay_arr[$pay_id]['id'] == 2){
                    $Payment =  new  Payment();
                    $res = $Payment->ali_bar($order_info['user_id'],'Store merchandise payment', 'Store merchandise payment',$order_id,$order_info['order_amount'],$auth_code,$this->shopId,$this->userId);
                    if($res['code'] != 1){
                        return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
                    }
                    $data = $res['data'];
                    if($data['code']==10000){
                        //Change order status Change payment status
                        Db::name('pay_log')->where(['out_trade_no'=>$data['out_trade_no']])->update(['status'=>1]);
                        $ShopBIllModel = new ShopBIllModel();
                        //Add to a Payment Reconciliation
                        $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,$pay_id,$pay_arr[$pay_id]['name'],$order_info['order_amount'],$order_info['user_id'],$order_info['user_account'],$pay_arr[$pay_id]['name']);
                        $ShopOrderModel->payOk($order_id,$pay_id,$pay_arr[$pay_id]['name']);
					return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
                    }
                    if($data['code']== 10003){
                        return json(['code'=>2,'data'=>[],'msg'=>'waiting for user payment']);
                    }
                    if($data['code']== 20000){
                        return json(['code'=>2,'data'=>[],'msg'=>'User is paying']);
                    }
                    if($data['code']== 40004){
                        return json(['code'=>0,'data'=>[],'msg'=>$data['sub_msg']]);
                    }
                //WeChat payment
                }elseif ($pay_arr[$pay_id]['id'] == 3){
                    $Payment =  new  Payment();
                    $res = $Payment->wx_bar($order_info['user_id'],'Store merchandise payment', 'Store merchandise payment',$order_id,$order_info['order_amount'],$auth_code,$this->shopId,'');
                    if($res['code'] != 1){
                        return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
                    }
                    $data = $res['data'];
                    if(strtoupper($data['result_code']) == 'FAIL'){
                        //Indicates that an unknown error occurred and a loop request is required
                        return json(['code'=>2,'data'=>[],'msg'=>$data['err_code_des']]);
                    }
                    //Indicate that the real payment is successful
                    //: Change order status Change payment status
                    Db::name('pay_log')->where(['out_trade_no'=>$data['out_trade_no']])->update(['status'=>1]);
                    $ShopBIllModel = new ShopBIllModel();
                    //Add to a Payment Reconciliation
                    $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,$pay_id,$pay_arr[$pay_id]['name'],$order_info['order_amount'],$order_info['user_id'],$order_info['user_account'],$pay_arr[$pay_id]['name']);
                    $ShopOrderModel->payOk($order_id,$pay_id,$pay_arr[$pay_id]['name']);
					return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
                }else{
                    return json(['code'=>0,'data'=>[],'msg'=>'non-existent payment method']);
                }

            }else{
                //Offline Change order status Set to paid
                $ShopBIllModel = new ShopBIllModel();
                //Add to a Payment Reconciliation
                $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,$pay_id,$pay_arr[$pay_id]['name'],$order_info['order_amount'],$order_info['user_id'],$order_info['user_account'],$pay_arr[$pay_id]['name']);
                //Change order status
                $order_info->pay_status=1;
                if($order_info->save()){
                    $ShopOrderModel->payOk($order_id,$pay_id,$pay_arr[$pay_id]['name']);
				return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);
                }
                return json(['code'=>0,'data'=>[],'msg'=>'payment failed']);

            }
        }else{
            $ShopPayConfigModel = new ShopPayConfigModel();
            $ShopOrderModel = new ShopOrderModel();
            $shop_id = $this->shopId;
            if(!$shop_id){
                $shop_id=config('config.shop_default_manage');
            }
            $order_info = $ShopOrderModel->where(['order_id'=>$order_id,'shop_id'=>$shop_id])->find();
            if($order_info->getData('order_status') != 1 || $order_info->getData('pay_status') != 0  ){
                return json(['code'=>0,'data'=>[],'msg'=>'Current status not available for payment']);
            }
            $info =[
                "total_amount" => $order_info['total_amount'],
                "order_amount" => $order_info['order_amount'],
                "goods_price" => $order_info['goods_price'],
                "discount" => $order_info['discount'],
                "num"=>  $order_info['num'],
                "integral_money"=>$order_info['integral_money'],
                "user_money"=>$order_info['user_money']
            ];
            $online = $ShopPayConfigModel->where('type','>',0)->column('name','id');
            //TODO::niufmspecial requirements。
            // $offline = $ShopPayConfigModel->where([['shop_id','=',0],['type','=',0]])->whereOr('shop_id','=',$shop_id)->column('name','id');
            $offline = $ShopPayConfigModel->where(['type'=>0,'status'=>1])->column('name','id');
            return json(['code'=>1,'data'=>['info'=>$info,'online'=>$online,'offline'=>$offline],'msg'=>'']);
        }

    }
    //offline payment method
    public function offlinePay($order_id,$pay_ids){
        if(request()->isPost()){
            $ShopOrderModel = new ShopOrderModel();
            $shop_id = $this->shopId;
            if(!$shop_id){
                $shop_id=config('config.shop_default_manage');
            }
            $order_info = $ShopOrderModel->where(['order_id'=>$order_id,'shop_id'=>$shop_id])->find();
            if($order_info->getData('order_status') != 1 || $order_info->getData('pay_status') != 0  ){
                return json(['code'=>0,'data'=>[],'msg'=>'Current status cannot be paid']);
            }
			//Determine whether the payment is the payment method of this store.
            $ShopPayConfigModel = new ShopPayConfigModel();

            //TODO Newman's mixed payment method
           // $pay_arr = $ShopPayConfigModel->where('shop_id','=',$shop_id)->whereOr("shop_id",'=',0)->column('id,name,type','id');
            $pay_arr = $ShopPayConfigModel->where(['type'=>0,'status'=>1])->column('id,name,type','id');
            $pays=[];
            $order_amount = $order_info['order_amount'];
            $temp = 0;
            foreach ($pay_ids as $pay_id => $money ){
                if(!key_exists($pay_id,$pay_arr)){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current payment method does not exist']);
                }
                if($money > 0){
                    $pays[$pay_id]  = $money;
                    $temp+=$money;
                }
            }
            if(!$pays){
				return json(['code'=>0,'data'=>[],'msg'=>'Please set the correct payment method']);
            }
            if(abs($order_amount-$temp) > 1 ){
                return json(['code'=>0,'data'=>[],'msg'=>'Please set the correct amount']);
            }
            //Offline Change order status Set to paid
            $ShopBIllModel = new ShopBIllModel();
            foreach ($pays as $pid => $money){
                $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,$pid,$pay_arr[$pid]['name'],$money,$order_info['user_id'],$order_info['user_account'],$pay_arr[$pid]['name']);
            }
            $pid = count($pays) > 1 ?0:key($pays);
            $pnme =  count($pays) > 1?'mixed payment':$pay_arr[key($pays)]['name'];
            $ShopOrderModel->payOk($order_id,$pid,$pnme);
            return json(['code'=>1,'data'=>[],'msg'=>'payment successful']);

        }
    }
    //list
    public function lists(){
        $order_id=input('order_id',0);
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $ShopListsModel = new ShopListsModel();
        $shop =$ShopListsModel->getOneSubshop($shop_id);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }
        $ShopOrderModel = new ShopOrderModel();
        $ShopOrderListsModel =  new ShopOrderListsModel();
        if($this->request->isPost()){
            $post_goods= input('post.goods');
            $sale_uid=input('post.sale_uid',0);
            $user_id=input('post.user_id',0);
            $invoice_title=input('post.invoice_title','');
            $invoice_tax_number=input('post.invoice_tax_number','');
            $admin_note=input('post.admin_note','');
			$total_amount=0;//Total order price
            $goods_price=0;//Total price of goods
            $order_amount=0;//Payable
            $num=0;//Number
            $exchange_integral = 0;//Number of points available for exchange
            if($post_goods){
                $GoodsStock =  new GoodsStock();
                foreach ($post_goods['spec_id'] as $k => $v){
                    $info = $GoodsStock->getOneStockDetails($shop_id,$v);
                    if(!$info){
					return json(['code'=>0,'data'=>[],'msg'=>'Product specification does not exist:'.$v]);
                    }
                    if($info['stock'] < $post_goods['num'][$k]){
                        return json(['code'=>0,'data'=>[],'msg'=>'Product ID:'.$info['goods_id'].'Spec ID:'.$v.'Insufficient stock :']);
                    }
                    $data=[];
                    $data['order_id']=$order_id;
                    $data['shop_id']=$shop_id;
                    $data['spec_id']=$v;
                    $data['order_status']=0;
                    $data['goods_id']=$info['goods_id'];
                    $data['goods_name']=$info['goods_name'];
                    $data['goods_sn']=$info['goods_sn'];;
                    $data['market_price']=$info['market_price'];;
                    $data['shop_price']=$info['shop_price'];
                    $data['cost_price']=$info['cost_price'];
                    $data['member_goods_price']=$post_goods['price'][$k];
                    $data['goods_num']=$post_goods['num'][$k];
                    $data['give_integral']=$info['give_integral']*$data['goods_num'];
                    $data['spec_key']=$info['spec_key'];
                    $data['spec_key_name']=$info['spec_name'];
                    $data['goods_sku']=$info['spec_sku'];
                    $data['remarks']=$post_goods['remarks'][$k];
                    $data['update_time']=time();
                    $data['create_time']=time();
                    $data['total_money']=$data['member_goods_price']*$data['goods_num'];
                    $goods_price += $data['goods_num']*$data['shop_price'];
                    $total_amount+= $data['goods_num']*$data['shop_price'];
                    $order_amount+= $data['goods_num']*$data['member_goods_price'];
                    $num+= $data['goods_num'];
                    //TODO::The points of this place should have been calculated according to the product settings. Now their points are the number of points for the price of the product.
                   // $exchange_integral+=$data['goods_num']* $info['exchange_integral'];
                    $exchange_integral+=$data['goods_num']*$data['member_goods_price'];
                    $goods[]=$data;
                }
            }

            $discount = $total_amount-$order_amount;

            if($order_id){
                $order_info = $ShopOrderModel->where(['order_id'=>$order_id,'shop_id'=>$shop_id])->find();
                if(!$order_info){
                    return json(['code'=>0,'data'=>[],'msg'=>'The order does not exist']);
                }
                //Amendments can only be made if the payment has not been submitted.
                if($order_info->getData('order_status') != 0 || $order_info->getData('pay_status') != 0  ){
                    return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be modified']);
                }
                $order_info->sale_uid=$sale_uid;
                $order_info->user_id=$user_id;
                $order_info->user_account='';
                $order_info->pay_id=0;
                $order_info->total_amount=$total_amount;
                $order_info->goods_price=$goods_price;
                $order_info->order_amount=$order_amount;
                $order_info->num=$num;
                $order_info->discount=$discount;
                $order_info->invoice_title=$invoice_title;
                $order_info->invoice_tax_number=$invoice_tax_number;
                $order_info->admin_note=$admin_note;
                $order_info->exchange_integral=$exchange_integral;
                if($order_info->save()){
                    //delete the original item
                    $ShopOrderListsModel->where(['order_id'=>$order_id])->delete();
                    //reinsert
                    $ShopOrderListsModel->insertAll($goods);
                    return json(['code'=>1,'data'=>['id'=>$order_id],'msg'=>'Created successfully']);
                }
            }else{
                $id = $ShopOrderModel->createOrder($shop_id,$this->userId,$this->userName,$sale_uid,$user_id,$order_amount,$total_amount,$goods_price,$num,$discount,$exchange_integral,$admin_note);
                foreach ($goods as &$v){
                    $v['order_id']=$id;
                }

            $ShopOrderListsModel->insertAll($goods);
            return json(['code'=>1,'data'=>['id'=>$id],'msg'=>'Created successfully']);
            }
        }else{
            if($order_id){
                $info = $ShopOrderModel->where(['order_id'=>$order_id,'shop_id'=>$shop_id])->find();
                $info['shop_name']=$shop['name'];
                $lists = $ShopOrderListsModel->where(['order_id'=>$order_id])->select();
                return json(['code'=>1,'data'=>['info'=>$info,'lists'=>$lists],'msg'=>'']);
            }else{
				//new
                $info['order_id']='';//Order id
                $info['shop_id']=$shop_id;
                $info['shop_name']=$shop['name'];
                $info['sale_uid']=0;//Sale ID
                $info['sale_account']='';//Sales account
                $info['cashier_uid']=$this->userId;//Cashier ID
                $info['cashier_account']=$this->userName;//Cashier account
                $info['user_id']='';//User ID
                $info['user_account']='';//User account
                $info['num']=0;//Number
                $info['pay_id']=0;//Payment method ID
                $info['pay_name']='';//Payment name
                $info['create_time']='';
                $info['total_amount']='0.00';//Order price
                $info['order_amount']='0.00';//Order price
                $info['discount']='0.00';//Price adjustment
                $info['user_money']='0.00';//Use balance
                $info['integral_money']='0.00';//Use points
                $info['invoice_title']='';//Invoice title
                $info['invoice_tax_number']='';//Invoice tax number
                $info["order_status"]= "Not Submitted";
                $info["pay_status"]= "Unpaid";
                return json(['code'=>1,'data'=>['info'=>$info,'lists'=>[]],'msg'=>'']);
            }
        }
    }
    //Confirm Order
    public function complete(){
        $order_id=input('order_id',0);
        if($order_id == 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'缺少订单ID']);
        }
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $ShopOrderModel = new ShopOrderModel();
        $order_info = $ShopOrderModel->where(['shop_id'=>$shop_id,'order_id'=>$order_id])->find();
        if($order_info->getData('order_status') != 0 || $order_info->getData('pay_status') != 0  ){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be modified']);
        }
        $user=[
            'id'=>0,
            'money'=>'0.00',
            'integral'=>0,
        ];
        //First check whether there is a UID and a balance
        if($order_info->user_id){
            $user =  Db::name('member')->field('id,money,integral')->where(['id'=>$order_info->user_id])->find();
        }
        $data=[
            "total_amount" => $order_info['total_amount'],
            "order_amount" => $order_info['order_amount'],
            //  "shipping_price" => 0,
            "goods_price" => $order_info['goods_price'],
            "discount" => $order_info['discount'],
            // "cut_fee" => 0,
            "num"=>  $order_info['num'],
            //TODO::This time it's because Newfman's mode points are all used, otherwise you can comment out the following line
            $order_info->exchange_integral=$order_info['order_amount'],
            "exchange_integral"=>$order_info['exchange_integral'],//How many points can be deducted
            "integral_money"=>sprintf("%.2f",$order_info['exchange_integral']/config('config.point_rate')),//How much can be deducted with points


            // "user_money"=> 0,
            //  "coupon_price"=> 0
            'integral_pi'=> config('config.point_rate')
        ];
        if($this->request->isPost()){
            $integral = input('post.use_integral',0);
            $user_money = input('post.use_money',0);
            $moling = input('post.moling',0);
            if($moling){
                $order_info->exchange_integral=round($order_info->exchange_integral);
            }

            if($integral && $order_info->exchange_integral && $order_info->order_amount){
                if($user['integral'] < $integral){
                    return json(['code'=>0,'data'=>[],'msg'=>'Not enough points']);
                }
                //The deducted points are more than the order can use
                if($integral > $order_info['exchange_integral']){
                    $integral=$order_info['exchange_integral'];
                }
                $integral_money =sprintf("%.2f",$integral/config('config.point_rate'));
                $order_info->integral=$integral;
                $order_info->integral_money=$integral_money;
                $order_info->order_amount=$order_info->order_amount-$integral_money;
            }
            //calculating
            if($user_money && $order_info->order_amount){
                if($user['money'] < $user_money){
                    return json(['code'=>0,'data'=>[],'msg'=>'Insufficient available balance']);
                }
                // If the user's money is greater than the remaining money in the order, use the remaining money in the order
                if($user_money > $order_info->order_amount){
                    $user_money = $order_info->order_amount;
                }
                $order_info->user_money=$user_money;
                $order_info->order_amount=$order_info->order_amount-$user_money;
            }
            //First judge whether the inventory is sufficient and then subtract the inventory
            $ShopOrderListsModel =  new ShopOrderListsModel();
            $GoodsStock = new GoodsStock();
            $goods = $ShopOrderListsModel->where(['order_id'=>$order_id])->select();
            Db::startTrans();
            try {
                foreach ($goods as $v){
                    $GoodsStock->decStock($this->userId,$v['goods_id'],$v['spec_id'],$v['shop_id'],$v['goods_num'],$v['member_goods_price'],$order_id,$type=9,$rem = 'cashier order');
                }
                $order_info->order_status = 1;
                //minus points
                if($order_info->user_id && $order_info->integral){
                    IntegralLog::setDec($order_info->user_id,$order_info->integral);
                    //$uid,$num,$act=1,$status=1,$remark='',$executor=0
                    IntegralLog::operate($order_info->user_id,-$order_info->integral,21,1,'Store purchases deductions',$this->userId);
                }
                //minus balance
                if($order_info->user_id && $order_info->user_money){
                    MoneyLog::setDec($order_info->user_id,$order_info->user_money);
                    MoneyLog::operate($order_info->user_id,-$order_info->user_money,21,1,'Store purchases deductions',$this->userId);
                }
				//Tick ​​to erase
                if($moling){
                    $moling_qian = $order_info->order_amount;
                    $order_info->order_amount = round($order_info->order_amount);
                    $order_info->moling=$moling_qian-$order_info->order_amount;
                }
				//order time
                $order_info->add_time=time();
                if($order_info->save()){
                    $ShopOrderListsModel->where(['order_id'=>$order_id])->update(['order_status'=>1]);
                    // commit the transaction
                    Db::commit();
                  /* //Indicate that the payment is no longer required
                    if($order_info->order_amount <= 0){
                        //The payment is directly changed to success
                        $ShopOrderModel->payOk($order_id,1,'Balance payment');
                        return json(['code'=>2,'data'=>[],'msg'=>'submission successful']);
                    }*/
                    return json(['code'=>1,'data'=>[],'msg'=>'submission successful']);
                }else{
                    Db::rollback();
                    return json(['code'=>0,'data'=>[],'msg'=>'Save failed']);
                }
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
            }

        }else{
            return json(['code'=>1,'data'=>['info'=>$data,'user'=>$user],'msg'=>'']);
        }

    }
    //cancel order
    public function cancel(){
        $order_id=input('order_id',0);
        if($order_id == 0 ){
            return json(['code'=>0,'data'=>[],'msg'=>'Missing orderID']);
        }
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $ShopOrderModel = new ShopOrderModel();
        $order_info = $ShopOrderModel->where(['shop_id'=>$shop_id,'order_id'=>$order_id])->find();
        if($order_info->getData('order_status') == 2 || $order_info->getData('pay_status') != 0){
            return json(['code'=>0,'data'=>[],'msg'=>'The current state cannot be canceled']);
        }
        $ShopOrderListsModel =  new ShopOrderListsModel();
        $GoodsStock = new GoodsStock();
        $goods = $ShopOrderListsModel->where(['order_id'=>$order_id])->select();
        Db::startTrans();
        try {
            if($order_info->getData('order_status') == 1){
                foreach ($goods as $v){
                    $GoodsStock->incStock($this->userId,$v['goods_id'],$v['spec_id'],$v['shop_id'],$v['goods_num'],$v['member_goods_price'],$order_id,$type=10,$rem = 'Cashier Cancellation');
                }


                //plus points
                if($order_info->user_id && $order_info->integral){
                    IntegralLog::setInc($order_info->user_id,$order_info->integral);
                    //$uid,$num,$act=1,$status=1,$remark='',$executor=0
                    IntegralLog::operate($order_info->user_id,$order_info->integral,22,1,'Store canceled order refund',$this->userId);
                }
                //add balance
                if($order_info->user_id && $order_info->user_money){
                    MoneyLog::setInc($order_info->user_id,$order_info->user_money);
                    MoneyLog::operate($order_info->user_id,$order_info->user_money,22,1,'Store canceled order refund',$this->userId);
                }
            }
            $order_info->order_status = 2;
            if($order_info->save()){
                $ShopOrderListsModel->where(['order_id'=>$order_id])->update(['order_status'=>2]);
			// commit the transaction
                Db::commit();
                return json(['code'=>1,'data'=>[],'msg'=>'Cancel success']);
            }else{
                Db::rollback();
                return json(['code'=>0,'data'=>[],'msg'=>'cancellation failed']);
            }
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
        }
    }

    //order return
    public function refunds(){
        $order_id=input('order_id',0);
        if($order_id == 0 ){
		return json(['code'=>0,'data'=>[],'msg'=>'Missing order ID']);
        }
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $ShopOrderModel = new ShopOrderModel();
        $order_info = $ShopOrderModel->where(['shop_id'=>$shop_id,'order_id'=>$order_id])->find();
        if(!$order_info){
            return json(['code'=>0,'data'=>[],'msg'=>'The order was not found']);
        }
        if($order_info->getData('order_status') != 1 || $order_info->getData('pay_status') != 1 || $order_info->getData('returns_status') == 3){
            return json(['code'=>0,'data'=>[],'msg'=>'current status cannot be returned']);
        }
        if(request()->isPost()){
            $refunds_price = input('refunds_price',0);
            $refunds_integral = input('refunds_integral',0);
            $refunds_money = input('refunds_money',0);

            if(!$order_info->user_id && ($refunds_integral || $refunds_money)){
                return json(['code'=>0,'data'=>[],'msg'=>'Non-member purchases, balance and points cannot be returned']);
            }
            $ShopOrderListsModel =  new ShopOrderListsModel();
            $GoodsStock = new GoodsStock();
            $goods = $ShopOrderListsModel->where(['order_id'=>$order_id])->select();
            Db::startTrans();
            try {
                //add stock
                foreach ($goods as $v){
                    if($v['goods_num']-$v['returns_num']>0){
                        $GoodsStock->incStock($this->userId,$v['goods_id'],$v['spec_id'],$v['shop_id'],$v['goods_num']-$v['returns_num'],$v['member_goods_price'],$order_id,$type=11,$rem = 'cashier return');
                        Db::name('shop_order_lists')->where(['id'=>$v['id']])->update(['returns_num'=>$v['goods_num']]);
                    }
                }
                //plus points
                if($order_info->user_id && $refunds_integral){
                    IntegralLog::setInc($order_info->user_id,$refunds_integral);
                    //$uid,$num,$act=1,$status=1,$remark='',$executor=0
                    IntegralLog::operate($order_info->user_id,$refunds_integral,23,1,'Store return merchandise return',$this->userId);
                    $order_info->refunds_money=$order_info->refunds_money+$refunds_integral;
                }
                //add balance
                if($order_info->user_id && $refunds_money){
                    MoneyLog::setInc($order_info->user_id,$refunds_money);
                    MoneyLog::operate($order_info->user_id,$refunds_money,23,1,'Store return merchandise return',$this->userId);
                    $order_info->refunds_integral=$order_info->refunds_integral+$refunds_money;
                }
                if($refunds_price){
					//Add to a payment reconciliation table
                    $ShopBIllModel = new ShopBIllModel();
                    //Add to a payment reconciliation table
                    $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,4,'cash receipt',-$refunds_price,$order_info['user_id'],$order_info['user_account'],'cash refund');
                    $order_info->returns_price=$order_info->returns_price+$refunds_price;
                }
                $order_info->order_status = 2;
                $order_info->pay_status = 2;
                $order_info->returns_status = 2;
                if($order_info->save()){
                    $ShopOrderListsModel->where(['order_id'=>$order_id])->update(['order_status'=>2]);
				// commit the transaction
                    Db::commit();
                    return json(['code'=>1,'data'=>[],'msg'=>'successful return']);
                }else{
                    Db::rollback();
                    return json(['code'=>0,'data'=>[],'msg'=>'cancellation failed']);
                }
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['code'=>0,'data'=>[],'msg'=>$e->getMessage()]);
            }
        }else{
            $propose_user_money =$order_info->user_money-$order_info->refunds_money >0?$order_info->user_money-$order_info->refunds_money:0;
            $propose_exchange_integral =$order_info->integral-$order_info->refunds_integral >0?$order_info->integral-$order_info->refunds_integral:0;
            $propose_returns_price =$order_info->order_amount-$order_info->returns_price >0?$order_info->order_amount-$order_info->returns_price:0;
            $order_info->propose_refunds_money=$propose_user_money;
            $order_info->propose_refunds_integral=$propose_exchange_integral;
            $order_info->propose_returns_price=$propose_returns_price;
            return json(['code'=>1,'data'=>$order_info,'msg'=>'']);
        }

    }

    //individual refund
    public function dantui(){
        $order_id =  input('order_id');
        $order_lists_id = input('order_lists_id',0);
        $refunds_num = input('refunds_num',0);
        $refunds_price = input('refunds_price',0);
        $refunds_integral = input('refunds_integral',0);
        $refunds_money = input('refunds_money',0);
        $ShopOrderModel = new ShopOrderModel();
        $ShopOrderListsModel =  new ShopOrderListsModel();
        $map =['order_id'=>$order_id];
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $order_info = $ShopOrderModel->where($map)->find();
        if(!$order_info){
			return json(['code'=>0,'data'=>[],'msg'=>'order does not exist']);
        }
        if($order_info->getData('order_status') != 1 || $order_info->getData('pay_status') != 1){
            return json(['code'=>0,'data'=>[],'msg'=>'current status cannot be returned']);
        }
        //Check the order status first returns_status
        if($order_info->getData('returns_status') == 3){
            return json(['code'=>0,'data'=>[],'msg'=>'All returned orders']);
        }
        //then check the number of returns
        $order_lists_info = $ShopOrderListsModel->where(['order_id'=>$order_id,'id'=>$order_lists_id])->find();
        if(!$order_lists_info){
            return json(['code'=>0,'data'=>[],'msg'=>'The product list was not found']);
        }
        if($order_lists_info->returns_num >= $order_lists_info->goods_num){
            return json(['code'=>0,'data'=>[],'msg'=>'The item has been returned all']);
        }
        if($order_lists_info->returns_num+$refunds_num > $order_lists_info->goods_num){
            return json(['code'=>0,'data'=>[],'msg'=>'return quantity is greater than purchase quantity']);
        }

        if(!$order_info->user_id && ($refunds_integral || $refunds_money)){
            return json(['code'=>0,'data'=>[],'msg'=>'non-member purchase, balance and points cannot be returned']);
        }
        //TODO:: Amount of money refunded Amount No judgment
        $order_info->returns_price=$order_info->returns_price+$refunds_price;
        $order_info->user_id && $order_info->refunds_integral=$order_info->refunds_integral+$refunds_integral;
        $order_info->user_id && $order_info->refunds_money=$order_info->refunds_money+$refunds_money;
        //Process return status

        Db::startTrans();
        try {

            $order_lists_info->returns_num= $order_lists_info->returns_num+$refunds_num;
            $order_lists_info->save();
            //plus points
            if($order_info->user_id && $refunds_integral){
                IntegralLog::setInc($order_info->user_id,$refunds_integral);
                //$uid,$num,$act=1,$status=1,$remark='',$executor=0
                IntegralLog::operate($order_info->user_id,$refunds_integral,23,1,'Store return merchandise return',$this->userId);
            }
            //add balance
            if($order_info->user_id && $refunds_money){
                MoneyLog::setInc($order_info->user_id,$refunds_money);
                MoneyLog::operate($order_info->user_id,$refunds_money,23,1,'Store return merchandise return',$this->userId);
            }
            //record current refund
            if($refunds_price){
				//Offline change the order status and set it to paid
                $ShopBIllModel = new ShopBIllModel();
                //Add to a payment reconciliation table
                $ShopBIllModel->operate($this->userId,$this->userName,$shop_id,$order_id,4,'cash receipt',-$refunds_price,$order_info['user_id'],$order_info['user_account'],'offline refund');
            }

			//add stock
            $GoodsStock = new GoodsStock();
            //TODO:: This place has points, balance and amount for returns. How should the price be calculated?
            $price=$refunds_money+$refunds_price;//Return cash + return balance
            $GoodsStock->incStock($this->userId,$order_lists_info['goods_id'],$order_lists_info['spec_id'],$shop_id,$refunds_num,$price,$order_id,$type=11,$rem = 'cashier return');

           $return_tag = $ShopOrderListsModel->where(['order_id'=>$order_id])->where("goods_num",">",Db::raw('returns_num'))->count();
            if($return_tag){
                $order_info->returns_status = 1;
            }else{
                $order_info->returns_status = 2;
                $order_info->order_status = 2;
                $order_info->pay_status = 2;
            }
            if($order_info->save()){
			// commit the transaction
                Db::commit();
                return json(['code'=>1,'data'=>[],'msg'=>'refund successful']);
            }else{
                Db::rollback();
                return json(['code'=>0,'data'=>[],'msg'=>'Save failed']);
            }


        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
        }












    }
    //日志
    public function log($receipt_id){
        return json(['code'=>1,'data'=>StockReceiptLogModel::log($receipt_id),'msg'=>'']);
    }

    //对账单
    public function bill(){
        $ShopBIllModel = new ShopBIllModel();
        $map=[];
        $shop_id = input('get.shop_id',0,'trim');
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }else  if($shop_id){
            $map[]=['shop_id','=',$shop_id];
        }

        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['think_shop_bill.order_id|think_shop_bill.user_id|user_account|think_shop_bill.uid|account|pay_name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_shop_bill.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_shop_bill.create_time','<',strtotime($endTime)];
        }

        $limits = input('get.limit',10);
        $res = $ShopBIllModel->getPaginate($map,$limits);

        $lists = $res->toArray();
        $sum = $ShopBIllModel->getSumArr($map);
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel =   new ShopListsModel();
        return json(['code'=>1,'data'=>['sum'=>$sum,'count'=>$lists['total'],'lists'=>$lists['data'],'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);
    }

    //收银统计
    public function statistical(){
        $ShopBIllModel = new ShopBIllModel();
        $map=[];
        $shop_id = input('get.shop_id',0,'trim');
        if($this->shopId){
            $map[]=['shop_id','=',$this->shopId];
        }else  if($shop_id){
            $map[]=['shop_id','=',$shop_id];
        }

        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $keyWords = input('get.keyWords','','trim');
        if($keyWords != ''){
            $map[]=['think_shop_bill.order_id|think_shop_bill.user_id|user_account|think_shop_bill.uid|account|pay_name', 'like', "%".$keyWords."%"];
        }
        if($startTime){
            $map[]=['think_shop_bill.create_time','>',strtotime($startTime)];
        }
        if($endTime){
            $map[]=['think_shop_bill.create_time','<',strtotime($endTime)];
        }
        $limits = input('get.limit',10);
        $page = input('get.page',1);
        $count = $ShopBIllModel->join('think_shop_lists','think_shop_bill.shop_id=think_shop_lists.id','left')
            ->group('shop_id,remark,date_tag')
            ->order('think_shop_bill.id desc')
            ->where($map)->count();
        $lists = $ShopBIllModel->field('think_shop_bill.*,sum(money) as money,think_shop_lists.name shop_name')
            ->join('think_shop_lists','think_shop_bill.shop_id=think_shop_lists.id','left')
           ->group('shop_id,remark,date_tag')
            ->order('think_shop_bill.id desc')
            ->where($map)
            ->page($page,$limits)
            ->select();
        $shop_map=[];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
        }
        $ShopListsModel =   new ShopListsModel();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$lists,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map)],'msg'=>'']);


    }

    //收银管理 营业员业绩统计
    public function cashierSummary(){
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $shop_id = input('get.shopId',0,'trim');
        $sale_uid = input('get.saleUid','','trim');
        $map[]=['pay_status','=',1];
        if($this->shopId){
            $map[]=['think_shop_order.shop_id','=',$this->shopId];
        }else  if($shop_id){
            $map[]=['think_shop_order.shop_id','=',$shop_id];
        }

        if($startTime){
            $map[]=['think_shop_order.create_time','>',strtotime($startTime)];
        }else{
            $map[]=['think_shop_order.create_time','>',time()-3600*24*31];
        }
        if($endTime){
            $map[]=['think_shop_order.create_time','<',strtotime($endTime)];
        }else{
            $map[]=['think_shop_order.create_time','<',time()];
        }

        if($sale_uid){
            $map[]=['think_shop_order.sale_uid','=',$sale_uid];
        }
        $page=input('get.page',1);
        $limit=input('get.limit',10);
        $ShopOrderModel = new ShopOrderModel();
        $lists = $ShopOrderModel->join('think_shop_lists','think_shop_lists.id=think_shop_order.shop_id')->field('think_shop_lists.name as shop_name,sale_uid,sale_account,sum(num) as count_num,sum(goods_price) as count_money')->where($map)->order('count_money desc')->group('sale_uid')->page($page,$limit)->select();
        $sum = $ShopOrderModel->join('think_shop_lists','think_shop_lists.id=think_shop_order.shop_id')->field('sum(num) as count_num,sum(goods_price) as count_money')->where($map)->find();
        $shop_map=[];
        $user_map=[];
        $user_map[]=['shop_id','<>',0];
        if($this->shopId){
            $shop_map['id']=$this->shopId;
            $user_map[]=['shop_id','=',$this->shopId];
        }
        $ShopListsModel =   new ShopListsModel();
        $user_lists = Db::name('admin')->where($user_map)->order('id desc')->column('id','username');
        return json(['code'=>1,'data'=>['sum'=>$sum,'lists'=>$lists,'shop_lists'=>$ShopListsModel->getKeyVal($shop_map),'user_list'=>$user_lists],'msg'=>'']);

    }

    //营业员 详情
    public function cashierDesc(){
        $startTime = input('get.startTime',0,'trim');
        $endTime = input('get.endTime',0,'trim');
        $sale_uid = input('get.saleUid',0,'trim');
        $map[]=['pay_status','=',1];
        if($sale_uid == 0){
            return json(['code'=>0,'data'=>[],'msg'=>'缺少营业员ID']);
        }

        if($this->shopId){
            $map[]=['think_shop_order.shop_id','=',$this->shopId];
        }
        if($startTime){
            $map[]=['think_shop_order.create_time','>',strtotime($startTime)];
        }else{
            $map[]=['think_shop_order.create_time','>',time()-3600*24*31];
        }
        if($endTime){
            $map[]=['think_shop_order.create_time','<',strtotime($endTime)];
        }else{
            $map[]=['think_shop_order.create_time','<',time()];
        }
        if($sale_uid){
            $map[]=['think_shop_order.sale_uid','=',$sale_uid];
        }
        $page=input('get.page',1);
        $limit=input('get.limit',10);

        $ShopOrderModel = new ShopOrderModel();
        $lists = $ShopOrderModel->field('order_id,user_account,goods_price,num,pay_name,pay_time,order_status,pay_status')->order('order_id desc')->where($map)->page($page,$limit)->select();

        return json(['code'=>1,'data'=>$lists,'msg'=>'']);

    }


}