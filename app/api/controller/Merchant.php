<?php
/**
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/25
 * Time: 8:59
 */

namespace app\api\controller;

use app\common\model\MemberWalletLogModel;
//use app\common\model\Store;
use think\facade\Db;
use think\facade\Config;
use app\common\model\Price;
use app\common\model\Address;
use app\common\model\Order as OrderModel;
use think\facade\Cache;
use app\common\service\Payment;
use app\common\model\Member;
use app\common\service\Users;
use app\api\validate\MerchantValidate;
use think\exception\ValidateException;


class Merchant extends Base
{
     var $jiam=array(
		 1=>array('name'=>'VIP','money'=>1000),
         2=>array('name'=>'service provider','money'=>10000),
         3=>array('name'=>'area node','money'=>200000),
         4=>array('name'=>'city node','money'=>1000000),
         5=>array('name'=>'province node','money'=>3000000),
         6=>array('name'=>'country node','money'=>5000000),
     );
    //Merchant settled
    public function apply_merchant(){
        $uid = $this->uid();
        $Member =  new Member();
//        $shop_id=$Member->where('id',$uid)->value('shop_id');
        $have_shop=Db::name('shop_lists')->where('uid',$uid)->value('sh_status');
//        return json($have_shop);
        $Member_data=$Member->where('id',$uid)->field('id,shop_id,account,mobile,nickname,reusername')->find();
        //Industry type
        $industry=Db::name('industry')->where('status',1)->select()->toArray();
        $industry_arr=array();
        foreach ($industry as $k =>$v){
                if ($v['parent_id'] == 0) {
                    foreach ($industry as $k1 => $v1) {
                        if ($v1['parent_id'] == $v['id']) {
                            foreach ($industry as $k2 => $v2) {
                                if ($v2['parent_id'] ==  $v1['id']) {
                                    $v1['children'][] = $v2;
                                }
                            }
                            $v['children'][] = $v1;
                        }
                    }
                    $industry_arr[] = $v;
                }
        }
        unset($industry);
        $AddressModel = new Address();
			// area information
        $addr =$AddressModel->getRegionTree();
        //category list
			// $leimu=Db::name('industry')->where('status',1)->select();
        //Shop type type
        $type=Db::name('shop_type')->field('id,name,pid,create_time,is_upload,is_subshop')->select();
			// return json($have_shop);
        if(isset($have_shop)){//Apply for a merchant
			// return 1;
            if($have_shop==1){//Agreed
                return json(['status'=>1,'data'=>[],'msg'=>'Approved...']);
                }
            if($have_shop==2){//Rejected
                $info=Db::name('shop_lists')->where('uid',$uid)->find();
                $s1=Db::name('region')->where([['level','=',1],['id','=',$info['province']]])->value('name');
                $s2=Db::name('region')->where([['level','=',2],['id','=',$info['city']]])->value('name');
                $s3=Db::name('region')->where([['level','=',3],['id','=',$info['area']]])->value('name');
                $info['checkadress']="$s1 $s2 $s3";
                $ind_arr=explode(',',$info['industry_id']);
                $info['industry_name']='';
                foreach ($ind_arr as $a =>$b){
                    $ind1=Db::name('industry')->where([['status','=',1],['id','=',$b]])->value('gname');
                    empty($info['industry_name'])?$info['industry_name']="$ind1":$info['industry_name']=$info['industry_name']."/$ind1";
                }
                $info['type_name']=Db::name('shop_type')->where('id',$info['type'])->value('name');
                if(request()->isPost()){
                    $post=input('param.');
//                    return json($post);
					//Determine user quota and available quota
                    try {
                        validate(MerchantValidate::class)->check($post);
                    } catch (ValidateException $e) {
                        // validation failed output error message
                        return json(['status' => 0, 'data' => [], 'msg' => $e->getError()]);
                    }
                    $post['status']=1;
                    $post['sh_status']=0;
                    $post['uid']=$uid;
                    $post['vaild_time']=strtotime($post['vaild_time']);
                    if($post['vaild_etime']){
                        $post['vaild_etime']=strtotime($post['vaild_etime']);
                    }
                    $post['update_time']=time();
                    $post['create_time']=time();
//                $post['uid']=$uid;
                    Db::name('shop_lists')->save($post);
				return json(['status'=>1,'data'=>[],'msg'=>'submission successful']);
                }
                return json(['status'=>2,'data'=>['info'=>$info,'user'=>$Member_data,'address'=>$addr,'type'=>$type,' industry_arr'=>$industry_arr],'msg'=>'']);
            }
            if($have_shop==0){//To be reviewed
                return json(['status'=>3,'data'=>[],'msg'=>'under review...']);
            }
        }else{//No merchant applied for
            if(request()->isPost()){
				// return 2;
                $post=input('param.');
                //return json($post);
                //Determine user quota and available quota
                try {
                    validate(MerchantValidate::class)->check($post);
                } catch (ValidateException $e) {
                    // Authentication failed output error message
                    return json(['status' => 0, 'data' => [], 'msg' => $e->getError()]);
                }
                $post['status']=1;
                $post['sh_status']=0;
                $post['uid']=$uid;
                $post['vaild_time']=strtotime($post['vaild_time']);
                if($post['vaild_etime']){
                    $post['vaild_etime']=strtotime($post['vaild_etime']);
                }
                $post['update_time']=time();
                $post['create_time']=time();
                $post['type']=intval($post['type']);
//                $post['uid']=$uid;
                unset($post['id']);
//                return json($post);
                Db::name('shop_lists')->save($post);
                return json(['status'=>1,'data'=>[],'msg'=>'Successful application']);
            }
            return json(['status'=>4,'data'=>['user'=>$Member_data,'address'=>$addr,'type'=>$type,'industry_arr'=>$industry_arr],'msg'=>'']);
        }
    }

    //Edit business profile
    public function edit_merchant_msg(){
        $uid=$this->uid();
        $shop_msg=Db::name('shop_lists')->where('uid',$uid)->field('id,name,image')->find();
        if(!$shop_msg){
            $shop_id=Db::name('shop_lists')->where('uid',$uid)->value('id');
            $shop_msg=Db::name('shop_lits')->where('id',$shop_id)->field('id,name,image')->find();
        }
        if(request()->isPost()){
            $post=input('post.');
            if(empty($post['image'])){
				return json(['status'=>0,'data'=>[],'msg'=>'Shop avatar cannot be empty']);
            }
            if(empty($post['name'])){
                return json(['status'=>0,'data'=>[],'msg'=>'Shop name cannot be empty']);
            }
            Db::name('shop_lists')->save($post);
            return json(['status'=>1,'data'=>[],'msg'=>'modified successfully']);
        }
        return json(['status'=>1,'data'=>$shop_msg,'msg'=>'']);

    }
    // Merchant management
    public function merchant_management(){
        $uid=$this->uid();
        $user_msg=Db::name('member')->where('id',$uid)->field('shop_id,nickname')->find();
        if($user_msg['shop_id']==0){
            return json(['status'=>0,'data'=>[],'msg'=>'You don't have a store']);
        }
        $shop_list=Db::name('shop_lists')->where('id',$user_msg['shop_id'])->field('id as shop_id,image,name,create_time')->find();
        $shop_list['create_time']=date('Y-m-d H:i:s',$shop_list['create_time']);
        $shop_list['nickname']=$user_msg['nickname'];
        $shop_list['uid']=$uid;
        return json(['status'=>1,'data'=>$shop_list,'msg'=>'']);
    }
    //my shop information
    public function merchant_msg(){
        $uid = $this->uid();
        $Member =  new Member();
        $Member_data=$Member->where('id',$uid)->field('id,shop_id,account,mobile,nickname,reusername')->find();
        //Industry type
        $industry=Db::name('industry')->where('status',1)->select()->toArray();
        $industry_arr=array();
        foreach ($industry as $k =>$v){
            if ($v['parent_id'] == 0) {
                foreach ($industry as $k1 => $v1) {
                    if ($v1['parent_id'] == $v['id']) {
                        foreach ($industry as $k2 => $v2) {
                            if ($v2['parent_id'] ==  $v1['id']) {
                                $v1['children'][] = $v2;
                            }
                        }
                        $v['children'][] = $v1;
                    }
                }
                $industry_arr[] = $v;
            }
        }
        unset($industry);
        $AddressModel = new Address();
		// area information
        $addr =$AddressModel->getRegionTree();
        //category list
		// $leimu=Db::name('industry')->where('status',1)->select();
        //Shop type type
        $type=Db::name('shop_type')->field('id,name,pid,create_time,is_upload,is_subshop')->select();
        $info=Db::name('shop_lists')->where('id',$Member_data['shop_id'])->find();
        return json(['status'=>1,'data'=>['info'=>$info,'user'=>$Member_data,'address'=>$addr,'type'=>$type,' industry_arr'=>$industry_arr],'msg'=>'']);
    }
    /**
     * Commodities on the shelf, in the warehouse, sold out
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sell_goods(){
        $uid=$this->uid();
        $type=input('type');
        $keywords=input('keywords');
        $merchant_id=Db::name('member')->where('id',$uid)->value('shop_id');
//        $merchant_id=Db::name('shop_lists')->where('uid',$uid)->value('id');
        $map[]=['shop_id','=',$merchant_id];
        $map[]=['is_delete','=',0];
        $map[]=['status','=',1];
        if($keywords&&$keywords!=""){
            $map[]=['goods_name','like',"%" . $keywords . "%"];
        }

        if($type&&$type==2){
            $map[]=['spec_num|is_on_sale','=',0];
//            $map[]=['spec_num','=',0];
        }else{
            $map[]=['is_on_sale','>',0];
            $map[]=['spec_num','>',0];
        }
//        if($type&&$type==3){
//            $map[]=['spec_num','=',0];
//        }
        $page=input('page',1);
        $limit=input('limit',10);
        $cs_count=Db::name('goods')->where([['is_on_sale','>',0],['spec_num','>',0],['status','=',1],['is_delete','=',0],['shop_id','=',$merchant_id]])->count();
        $ck_count=Db::name('goods')->where([['spec_num|is_on_sale','=',0],['status','=',1],['is_delete','=',0],['shop_id','=',$merchant_id]])->count();
//        return json($map);
//        $ysw_count=Db::name('goods')->where([['spec_num','=',0],['shop_id','=',$merchant_id],['is_delete','=',0]])->count();
//        if($type==1){
//            $count=Db::name('goods')->where($map)->count();
//            $list=Db::name('goods')->where($map)->page($page,$limit)->order('create_time desc')->select();
//        }else{
            $count=Db::name('goods')->where($map)->count();
            $list=Db::name('goods')->where($map)->page($page,$limit)->order('create_time desc')->select();
//        }
        return json(['status'=>1,'data'=>['list'=>$list,'count'=>$count,'ck_count'=>$ck_count,'cs_count'=>$cs_count],'msg'=>'']);
    }

    /**
     * on the shelf/take down
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function putaway(){
        $goods_id=input('goods_id');
        $is_on_sale=Db::name('goods')->where('id',$goods_id)->value('is_on_sale');
        if($is_on_sale==0){
            Db::name('goods')->where('id',$goods_id)->update(['is_on_sale'=>1]);
            return json(['status'=>1,'data'=>[],'msg'=>'Successfully listed']);
        }else{
            Db::name('goods')->where('id',$goods_id)->update(['is_on_sale'=>0]);
            return json(['status'=>1,'data'=>[],'msg'=>'Removed successfully']);
        }
    }


		//confirm
    public function orderConfirm($id){
        $order_status=Db::name('order')->where('order_id', $id)->value('order_status');
        if($order_status==0){
            Db::name('order')->where('order_id', $id)->update(['order_status' => 1]);
            return json(['status'=>1,'data'=>[],'msg'=>'confirmed success']);
        }else{
            Db::name('order')->where('order_id', $id)->update(['order_status' => 0]);
            return json(['status'=>1,'data'=>[],'msg'=>'cancelled confirmation']);
        }
    }

    //Ship                                              order_id   5438   type
    public function orderDelivery(OrderModel $orderModel)
    {
        $uid=$this->uid();
        $shipping_id=input('shipping_id');
        $invoice_no=input('invoice_no');
        $id=input('id');
        $shipping=Db::name('shipping')->where('status',1)->select();
        if(request()->isPost()){
            $order_sn    = $this->getOrderSnForOrderId($id);
            if(!$order_sn){
                return json(['code'=>0,'data'=>[],'msg'=>'Check this order']);
            }
            $res = $orderModel->updata_shoping_delivery($order_sn,$invoice_no,$shipping_id,$uid);
            return json($res);
        }
        return json(['status'=>1,'data'=>$shipping,'msg'=>'']);

    }

    public function shenhe()
    {
        $id=input('id');
        $info = DB::name('return_goods')->where('order_id', $id)->order('addtime desc')->find();
        if ($this->request->isPost()) {
            $data = input('post.');
            $status = DB::name('return_goods')->where('order_id', $id)->order('addtime desc')->value('status');
            if ($status != 0) {
			return json(['code' => 0, 'msg' => 'The refund application has been processed and cannot be modified']);
            }
            //Update the refund details
            $update = [
                'refund_money'    => $info['refund_money'],
                'refund_deposit'  => $info['refund_deposit'],
                'refund_integral' => $info['refund_integral'],
                'remark'          => $data['note']
            ];
            DB::name('return_goods')->where('id', $info['id'])->update($update);
            //status update again
            $OrderCommon = new \app\common\model\Order();
            $res = $OrderCommon->returnGoodsStatus($info['id'], $data['status']);
            return json($res);
        }
		return json(['code'=>1,'data'=>['info'=>$info,'refund_type'=>new \ArrayObject([0=>'Refund only', 1=>'Refund return paragraph']),'status_list'=>new \ArrayObject(['-2'=>'user canceled','-1'=>'review failed',0=>'to be reviewed',1=>' Approved'])],'msg'=>'']);

    }
    //Confirm return
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
            //Update the refund details
            $update = [
                'shop_addr'    => $data['shop_addr'],
                'remark'       => $data['note'],
                'refund_confirm'=>1
            ];
            DB::name('return_goods')->where('id', $data['id'])->update($update);
            if($data['status'] == -1){
			// and then update the status
                $OrderCommon = new \app\common\model\Order();
                $res = $OrderCommon->returnGoodsStatus($id, $data['status']);
                return json($res);
            }
            // and then update the status

            return json(['code' => 1, 'msg' => 'Confirmed success']);
        }

    }

    //Find sn according to the order
    private function getOrderSnForOrderId($id){
        $map['order_id']=$id;
        return Db::name('order')->where($map)->value('order_sn');
    }

    /**
     * Product soft delete
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function good_delete(){
        $goods_id=input('goods_id');
        $is_delete=Db::name('goods')->where('id',$goods_id)->value('status');
        if($is_delete==1){
            Db::name('goods')->where('id',$goods_id)->update(['status'=>0,'is_delete'=>1]);
			return json(['status'=>1,'data'=>[],'msg'=>'deletion successful']);
        }else{
            return json(['status'=>1,'data'=>[],'msg'=>'Item has been deleted']);
        }
    }

    /**
     * @param int $type //0All orders 1st generation payment 2nd generation delivery 3to be received 4to be evaluated 5to be shipped 6to be refunded 7to be successful 8to be cancelled 9to be cancelled
     * @param int $page
     * @param int $num
     * @return \think\response\Json
     */
    public function order_list($type = 0, $page = 1, $num = 8)
    {
        $user_id = $this->uid();
        $shop_id=Db::name('member')->where('id',$user_id)->value('shop_id');
        $map            = [];
        $map[] = ['deleted','=',0];
        $map[] = ['shop_id','=',$shop_id];
		//Pending payment
        $dfk_count=Db::name('order')->where([['pay_status','=',0],['order_status','in', [0, 1]],['shipping_status', '=',0],['shop_id','=',$shop_id]])->count();
        //to be delivered
        $dfh_count=Db::name('order')->where([['shipping_status','=',0],['pay_status','in', [1, 4]],['order_status', 'in', [0, 1]],['shop_id','=',$shop_id]])->count();
        //to be received
        $dsh_count=Db::name('order')->where([['pay_status','in', [1, 4]],['order_status','=',1],['shipping_status', '=',1],['shop_id','=',$shop_id]])->count();
        //Shipped
        $yfh_count=Db::name('order')->where([['shipping_status','=',1],['shop_id','=',$shop_id]])->count();
        //comment
        $dpj_count=Db::name('order')->where([['pay_status','in', [1, 4]],['order_status','=',2],['shipping_status', '=',1],['shop_id','=',$shop_id]])->count();
        //Refund
        $tk_count=Db::name('order')->where([['pay_status','in',[2,3,4]],['shipping_status','=',1],['shop_id ','=',$shop_id]])->count();
        //Cancelled
        $yqx_count=Db::name('order')->where([['order_status','=',3],['shop_id','=',$shop_id]])->count();
        //out of date
        $yzf_count=Db::name('order')->where([['order_status','=',5],['shop_id','=',$shop_id]])->count();
        //completed
        $ywc_count=Db::name('order')->where([['order_status','=',4],['pay_status','=',1],['shipping_status','=', 1],['shop_id','=',$shop_id]])->count();
        //all
        $qb_count=Db::name('order')->where([['shop_id','=',$shop_id]])->count();

        switch ($type) {
            case 1 :
				//pre-payment
                $map[] = ['pay_status','=',0];
                $map[] = ['order_status','=', 0];
                break;
            case 2 :
                //delivery
                $map[] = ['pay_status','in', [1, 4]];
                $map[] = ['order_status','in', [0,1]];
                $map[] = ['shipping_status','=',0];
                break;
            case 3 :
                //to be received
                $map[] = ['pay_status','in', [1, 4]];
                $map[] = ['order_status','=',1];
                $map[] = ['shipping_status','=',1];
                break;
            case 4:
                //comment
                $map[] = ['pay_status','in', [1, 4]];
                $map[] = ['order_status','=',2];
                $map[] = ['shipping_status','=',1];
                break;
            case 5:
                //Shipped
                $map[]  = ['shipping_status','=',1];
                break;
            case 6 :
				//Refund
                $map[] = ['pay_status','in',[2,3,4]];
                $map[] = ['shipping_status','=',1];
                break;
            case 7:
                //succeeded
                $map[] = ['order_status','=',4];
                $map[] = ['pay_status','=',1];
                $map[] = ['shipping_status','=',1];
                break;
            case 8 :
                //out of date
                $map[] = ['order_status','=',5];
                break;
            case 9 :
                //Cancelled
                $map[]  = ['order_status','=',3];
                break;
            default :

        }

        $orderList = Db::name('order')->where($map)->order('order_id desc')->page($page, $num)->select()->toArray();
        if ($orderList) {
            //Query order product information
            foreach ($orderList as $k => $v) {
                if($v['pay_status'] == 2 ||$v['pay_status'] == 4){
                    $orderList[$k]['goods_list'] = Db::name('order_goods')
//                        ->join('return_goods r','o.order_id = r.order_id')
                        ->where('order_id', $v['order_id'])
//                        ->field('o.*,r.status as return_status,r.shop_addr,r.refund_confirm,r.refund_money,r.refund_deposit,r.refund_integral,r.id as retrrn_id')
//                        ->field('o.*,r.')
                        ->select();
                    $orderList[$k]['return_list'] = Db::name('return_goods')->where('order_id','=',$v['order_id'])->where('is_work','=',0)->order('addtime desc')->find();
                }else{
                    $orderList[$k]['goods_list'] = Db::name('order_goods')->where('order_id', $v['order_id'])->select();
                }

                $orderList[$k]['goods_num'] = 0;
                foreach ($orderList[$k]['goods_list'] as $v) {
                    $orderList[$k]['goods_num'] += $v['goods_num'];
                }
            }
            return json(['status' => 1, 'msg' => 'Get order list successfully', 'data' =>['list'=>$orderList,'dfk_count'=>$dfk_count, 'dfh_count'=>$dfh_count, 'dsh_count'=>$dsh_count, 'yfh_count'=>$yfh_count, 'dpj_count'=>$dpj_count, 'tk_count'=>$tk_count, 'yqx_count'=>$yqx_count, 'yzf_count'=>$yzf_count, 'ywc_count'=>$ywc_count, 'qb_count'=>$qb_count,]]);
        }
        return json(['status' => 1, 'data'=>['list'=>[]], 'msg' => 'no order data']);

    }

    /**
		* Merchant order details
     * @param $order_id order ID
     * @is_simple 0 returns all 1 returns only the basic information of the order
     * @return \think\response\Json
     */
    public function order_detail($shop_id, $order_id, $is_simple = 0)
    {
		// $user_id = $this->uid();
        $orderInfo = Db::name('order')->where(['shop_id' => $shop_id, 'order_id' => $order_id])->find();
        if (!$orderInfo) {
            return json(['status' => 0, 'msg' => 'order does not exist']);
        }
        //Shipping address
        if (!$region = Cache::get('regiondata')) {
            $region    = Db::name('region')->column('name','id');
            Cache::set('regiondata', $region, 0);
        }
        $addr = '';
        isset($region[$orderInfo['province']]) && $addr .= $region[$orderInfo['province']] . ',';
        isset($region[$orderInfo['city']])     && $addr .= $region[$orderInfo['city']] . ',';
        isset($region[$orderInfo['district']]) && $addr .= $region[$orderInfo['district']] . ',';
        $orderInfo['address']                  && $addr .= $orderInfo['address'];
        $orderInfo['address_detail'] = trim(str_replace(',', ' ', $addr));
        if ($is_simple == 0) {
		//Order item list
            $orderInfo['goods_list'] = Db::name('order_goods')->where('order_id', $orderInfo['order_id'])->select();
            // shopping rebate points
            $orderInfo['give_integral'] = Db::name('order_goods')->where('order_id', $orderInfo['order_id'])->sum('give_integral');
        }
        //Is there a refund
        $orderInfo['return_id'] = 0;
        if ($orderInfo['pay_status'] > 1) {
            $orderInfo['return_id'] = Db::name('return_goods')->where(['order_id' => $order_id, 'status' => ['neq', -2]])->value(' id');
        }
        // time format conversion
        $orderInfo['add_time'] && $orderInfo['add_time'] = date('Y-m-d H:i:s', $orderInfo['add_time']);
        $orderInfo['shipping_time'] && $orderInfo['shipping_time'] = date('Y-m-d H:i:s', $orderInfo['shipping_time']);
        $orderInfo['confirm_time'] && $orderInfo['confirm_time'] = date('Y-m-d H:i:s', $orderInfo['confirm_time']);
        $orderInfo['pay_time'] && $orderInfo['pay_time'] = date('Y-m-d H:i:s', $orderInfo['pay_time']);
        return json(['status' => 1, 'msg' => 'Get order details successfully', 'data' => $orderInfo]);
    }
		// /**
		// * Merchant order details
//     * @return \think\response\Json
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function merchant_order_details(){
//        $order_id=input('order_id');
//        $list=Db::name('order')->where('order_id',$order_id)->find();
//        $list['good_msg']=Db::name('order_goods')->where('order_id',$order_id)->select();
//        return json(['status'=>1,'data'=>$list,'msg'=>'']);
//    }
    public function merchant_index(){
        $uid=$this->uid();
        $shop_id=input('shop_id');
        $merchant=Db::name('shop_lists')->where('id',$shop_id)->field('image,name,id')->find();
        $merchant['follow_num']=Db::name('shop_follow')->where('shop_id',$shop_id)->count();
        $merchant['is_follow']=Db::name('shop_follow')->where([['shop_id','=',$shop_id],['user_id','=',$uid]])->count();
        $map[]=['is_on_sale','=',1];
        $map[]=['status','=',1];
        $map[]=['spec_num','<>',0];
        $map[]=['shop_id','=',$shop_id];
        $map[]=['is_delete','=',0];
        $keywords=input('keywords');
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        if($keywords&&$keywords!=""){
            $map[]=['goods_name','like',"%" . $keywords . "%"];
        }
        if($order&&$order=='sales'){
            $order="sales_num desc";
        }
        if($order&&$order=='priceup'){
            $order="shop_price asc";
        }
        if($order&&$order=='pricedown'){
            $order="shop_price desc";
        }
        $merchant['count']=Db::name('goods')->where($map)->count();
        $merchant['list']=Db::name('goods')->where($map)->order($order)->page($page,$limit)->select();
        return json(['status'=>1,'data'=>$merchant,'msg'=>'']);
    }

    /**
     * Store Category
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_category(){
        $shop_id=input('shop_id');
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list=Db::name('goods_category')->where([['shop_id','=',$shop_id],['level','=',3]])->page($page,$limit)->order($order)->select();
        return json(['status'=>1,'data'=>$list,'msg'=>'']);
    }

    /**
     * Store product classification
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIndustry(){
        $shop_id=input('shop_id');
        $industry = Db::name('goods_category')->where('parent_id',0)->where('shop_id',$shop_id)->order('order asc')->select()->toArray();
        $data=getChild($industry,$shop_id);
        return json(['status' => 1, 'data' => $data, 'msg' => 'Get the stores product classification success']);
    }
    /**
     *Store product list
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function merchant_goods_lists(){
        $cat_id=input('cat_id');
        $shop_id=input('shop_id');
        $type=input('type',0);
        $page=input('page',1);
        $limit=input('limit',10);
        $keywords=input('keywords');
        $order=input('order','create_time desc');
        $map[]=['shop_id','=',$shop_id];
        $map[]=['is_delete','=',0];
        $map[]=['status','=',1];
        $map[]=['spec_num','>',0];
        $map[]=['is_on_sale','=',1];
        if($keywords&&$keywords!=""){
            $map[]=['goods_name','like',"%" . $keywords . "%"];
        }
        if($cat_id&&$cat_id!=""){
            $map[]=['cat_id','in',$cat_id];
        }
        if($order&&$order=='sales'){
            $order="sales_num desc";
        }
        if($order&&$order=='priceup'){
            $order="shop_price asc";
        }
        if($order&&$order=='pricedown'){
            $order="shop_price desc";
        }
        if($type&&$type!=""){
            if($type=='new'){
                $map[]=['is_new','=',1];
            }
            if($type=='hot'){
                $map[]=['is_hot','=',1];
            }
        }
        $count=Db::name('goods')->where($map)->count();
        $list=Db::name('goods')->where($map)->order($order)->page($page,$limit)->select();
        return json(['status'=>1,'data'=>['list'=>$list,'count'=>$count],'msg'=>'']);
    }
			//offline order
    public function down_line_order(){
        $uid=$this->uid();//merchant->user id
        $merant=Db::name('member')->where('id',$uid)->find();
        if(request()->isPost()){
            $param=input('post.'); //User mobile phone number //Consumption amount
            if(empty($param['mobile'])){
                return json(['status'=>0,'msg'=>'mobile phone number cannot be empty']);
            }
            if(empty($param['xf_money'])||$param['xf_money']<0){
                return json(['status'=>0,'msg'=>'consumption amount cannot be empty']);
            }
            $member=Db::name('member')->where('mobile',$param['mobile'])->find();
            if($member['id']==$uid){
                return json(['status'=>0,'msg'=>'Cannot enter this account']);
            }
            if(!$member ||$member['status']==0){
                return json(['status'=>0,'msg'=>'user does not exist/disable']);
            }
            if($merant['pool_water']<$param['xf_money']){
                return json(['status'=>0,'msg'=>'Insufficient balance in the flow pool, please recharge']);
            }
			// start transaction
            Db::startTrans();
            try {
                // Order reward settlement
                $Users=new Users();
                $Users->order_jiangli($member['id'],$param['xf_money']);
                //Subtract the merchant's flow pool quota
                Member::Onefield($uid,'pool_water','down',$param['xf_money']);
                MemberWalletLogModel::log($uid,$param['xf_money'],$merant['pool_water'],$member['pool_water']-$param['xf_money'],68,'reduce the merchant's pool quota for orders' ,$uid);
                //Increase the user's consumption pool quota
                Member::Onefield($member['id'],'pool_consumption','up',$param['xf_money']);
                MemberWalletLogModel::log($member['id'],$param['xf_money'],$member['pool_consumption'],$member['pool_consumption']+$param['xf_money'],60,'order increase User resonant computing power amount', $member['id']);
                // commit the transaction
                Db::commit();
                return json(['status'=>1,'msg'=>'operation successful']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['status'=>0,'msg'=>'operation failed']);
            }
        }
    }

			//join
    public function member_jm(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $jm_money=config('config.commission_fees');
        $jm_arr=explode(',',$jm_money);
			// return json($jm_arr);
        if(request()->isPost()){
            $param=input('post.');//type
            $money=$jm_arr[$param['type']-1];
            if(!$param['pwd']){
                return json(['status'=>0,'data'=>[],'msg'=>'payment password cannot be empty']);
            }
            if($member['dl_level']>=$param['type']){
                return json(['status'=>0,'data'=>[],'msg'=>'The joining level must be greater than the original level']);
            }
            if($money>$member['money']){
                return json(['status'=>0,'data'=>[],'msg'=>'Insufficient balance please recharge']);
            }
            if(empty($member['pay_password'])){
                return json(['status'=>0,'data'=>[],'msg'=>'No transaction password is set']);
            }
            $jypwd=base64_encode(md5($param['pwd'],true));
            if($member['pay_password']!=$jypwd){
                return json(['status'=>0,'data'=>[],'msg'=>'payment password error']);
            }
//            $verify_info = check_phone_verify($member['mobile'],$param['verify'],'jm_verify');
//            if(!$verify_info['code']){
//                return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
//            }
            $Users=new Users();
			// start transaction
            Db::startTrans();
            try {
                Db::name('member')->where('id',$uid)->update(['dl_level'=>$param['type']]);
                //Subtract the user's balance
                Member::Onefield($uid,'money','down',$money);
                MemberWalletLogModel::log($uid,$money,$member['money'],$member['money']-$money,8,'Join deduction US',$uid);
                // Double the user consumption pool
                Member::Onefield($uid,'pool_consumption','up',$money);
                MemberWalletLogModel::log($uid,$money,$member['pool_consumption'],$member['pool_consumption']+$money,60,'Joining to increase the amount of resonance computing power',$uid);
                //Join reward
                $Users->jm_jiangli($param['type'],$money,$uid);
                // commit the transaction
                Db::commit();
                return json(['status'=>1,'msg'=>'operation successful']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['status'=>1,'msg'=>'operation successful']);
            }
        }
        return json(['status'=>1,'data'=>$member['money'],'list'=>$jm_arr,'msg'=>'']);
    }



}