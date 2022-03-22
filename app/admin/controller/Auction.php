<?php

namespace app\admin\controller;
use app\admin\model\AuctionGoods;
use think\facade\Db;
use app\common\model\MemberWalletLogModel;
class Auction extends Base
{

    //Auction item
    public function auction_goods(){
        $AuctionGoods = new AuctionGoods();
        $map[] = ['is_delete','=',0];
        $keywords = input('get.keywords');
        if($keywords){
            $map[] = ['id|goods_name','like','%'.$keywords.'%'];
        }
        $bidders = input('get.bidders');
        if($bidders){
            $map[] = ['bidders','=',$bidders];
        }
        $auction_num = input('get.auction_num');
        if($auction_num){
            $map[] = ['auction_num','=',$auction_num];
        }
        $status = input('get.status');
        if($status){
            $map[] = ['status','=',$status];
        }
        $pay_status = input('get.pay_status');
        if($pay_status){
            $map[] = ['pay_status','=',$pay_status];
        }
        $is_remit = input('get.is_remit');
        if($is_remit){
            $map[] = ['is_remit','=',$is_remit];
        }
        $Nowpage = input('get.page',1);
        $limits = input('get.limit',10);
        $AuctionGoods = $AuctionGoods->getAuctionGoodsWhere($map, $Nowpage, $limits);
        foreach ($AuctionGoods as $k => $v) {
            $AuctionGoods[$k]['is_re_auction'] = 0;
            if($v['bidders']){
                if($v['status'] == 1 && $v['pay_status'] == 0){
                    if(strtotime($v['end_time']) < (time()-3600)){
                        $AuctionGoods[$k]['is_re_auction'] = 1;
                    }
                }
            }else{
                $AuctionGoods[$k]['is_re_auction'] = 1; 
            }
            
        }
        $count = Db::name('auction_goods')->where($map)->count();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$AuctionGoods]]);
    }

    //add auction //add end time
    public function add_auction_goods(){
        if(request()->isPost()){
            $param = input('post.');
            $param['goods_content'] = input('post.goods_content','',null);
            $password = input('post.password');
            $is_private = input('post.is_private');
            if($is_private==1){
                if(!$password){
                    return json(['code' => 0, 'data' => '', 'msg' =>'Non-public auction, please fill in private password']);
                }
            }else{
                $param['password']='';
            }
            $time=date('Y-m-d H:i:s',time());
            if($param['start_time']<=$time){
                $param['is_finish']=3;
            }else if($param['start_time']>$time){
                $param['is_finish']=1;
            }
            $param['status']=3;
            $param['start_price']=$param['price'];
            $param['end_time'] = date('Y-m-d H:i:s',strtotime($param['start_time']) + ($param['longtime'] * 60));
            $AuctionGoods = new AuctionGoods();
            $flag = $AuctionGoods->insertAuctionGoods($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);

        }
        
    }

    public function edit_auction_goods(){
        $AuctionGoods = new AuctionGoods();
        $id = input('param.id');
        $info = $AuctionGoods->getOneAuctionGoods($id);
        if(request()->isPost()){
            $param = input('post.');
			$param['goods_content'] = input('post.goods_content','',null);
            $password = input('post.password');
            $is_private = input('post.is_private');
            $param['start_price'] = $param['price'];
            /*if($info['status'] != 0){
			return json(['code' => 0, 'data' => '', 'msg' => 'It has been processed and cannot be edited any more']);
            }*/
            if($param['status'] !=2 ){
                if($param['longtime']<=0){
                    return json(['code' => 0, 'data' => '', 'msg' => 'Auction duration must be greater than 0']);
                }
                if(!$param['start_time']){
                    return json(['code' => 0, 'data' => '', 'msg' => 'Please set the auction start or end time']);
                }
               /* if(strtotime($param['start_time']) <= time()){
                    return json(['code' => 0, 'data' => '', 'msg' => 'The start time cannot be less than the current time']);
                }*/
                $param['end_time'] = date('Y-m-d H:i:s',strtotime($param['start_time']) + ($param['longtime'] * 60));
                if(strtotime($param['end_time']) <= strtotime($param['start_time'])){
                    return json(['code' => 0, 'data' => '', 'msg' => 'end time must be greater than start time']);
                }
                if($param['price_range'] <= 0){
                    return json(['code' => 0, 'data' => '', 'msg' => 'The price increase should be greater than 0']);
                }
                $param['is_finish'] = 1;
            }

            if($is_private==1){
                if(!$password){
                    return json(['code' => 0, 'data' => '', 'msg' =>'Non-public auction, please fill in private password']);
                }
            }else{
                $param['password']='';
            }

            $flag = $AuctionGoods->updateAuctionGoods($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }
        
        return json(['code'=>1,'data'=>['info'=>$info],'msg'=>'']);
    }

    public function detail(){
        $id = input('get.id');
        $AuctionGoods = new AuctionGoods();
        $info = $AuctionGoods->getOneAuctionGoods($id);
        $memberAuction = Db::name('member_auction')->where('auction_id',$id)->order('id desc')->select()->toArray();
        foreach ($memberAuction as $k => $v) {
            $memberAuction[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        }
        return json(['code'=>1,'data'=>['info'=>$info,'memberAuction'=>$memberAuction],'msg'=>'']);
    }

    public function shipping(){
        //Obtained by logistics
        $shipping = Db::name('shipping')->where('status', 1)->order('id asc')->select();
        return json(['code' => 1, 'data' =>$shipping, 'msg' => '']);
    }

    public function tui_auction()
    {
        $id=input('param.id');
        $status = Db::name('auction_goods')->where(array('id'=>$id))->value('is_push');//Judging the current state
        if($status==1){
            $flag = Db::name('auction_goods')->where(array('id'=>$id))->update(['is_push'=>0]);
			return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }else{
            $flag = Db::name('auction_goods')->where(array('id'=>$id))->update(['is_push'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }

    }

    //Review auction
    public function shen_auction(){
        $param = input('post.');
        $find = Db::name('auction_goods')->where('id',$param['id'])->find();
        /*if($find['status'] != 0){
				return json(['code' => 0, 'data' => '', 'msg' => 'The item has been processed']);
        }*/
        if($param['status'] !=2 ){
            if(!$find['start_time'] || !$find['end_time']){
                return json(['code' => 0, 'data' => '', 'msg' => 'Please set the auction start or end time']);
            }
            /*if(strtotime($find['start_time']) <= time()){
                return json(['code' => 0, 'data' => '', 'msg' => 'The start time cannot be less than the current time']);
            }*/
            if(strtotime($find['end_time']) <= strtotime($find['start_time'])){
                return json(['code' => 0, 'data' => '', 'msg' => 'end time must be greater than start time']);
            }
            if($find['price_range'] <= 0){
                return json(['code' => 0, 'data' => '', 'msg' => 'The price increase should be greater than 0']);
            }
        }else{
            if($find['apply_id']){
                Db::name('auction_goods')->where('id',$find['apply_id'])->update(['status'=>1,'update'=>time()]);
            }
        }

        $res = Db::name('auction_goods')->where('id',$param['id'])->update(['status'=>$param['status'],'is_finish'=>$param['is_finish'],'remark'=>$param['remark']]);
        if($res){
			return json(['code' => 1, 'data' => '', 'msg' => 'Audit successful']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'Audit failed']);
        }
    }

    //make payment
    public function remit_auction(){
        $id = input('post.id');
        $find = Db::name('auction_goods')->where('id',$id)->find();
        if($find['is_remit'] == 1){
			return json(['code' => 0, 'data' => '', 'msg' => 'Payment has been made']);
        }
        if($find['bidders']){
            $money = $find['price'] - $find['price']*$find['service_fee'] - $find['other_fee']; //Deduct service fee and other fees
            $user_money = Db::name('member')->where('id',$find['uid'])->value('money');
            if($money > 0){
                Db::name('member')->where('id',$find['uid'])->inc('money',$money)->update();
                $MemberWalletLogModel = new MemberWalletLogModel();
                $MemberWalletLogModel->log($find['uid'],$money,$user_money,$user_money + $money,130,'Auction successful payment',$find['id']);
            }
            Db::name('auction_goods')->where('id',$id)->update(['is_remit'=>1]);
            return json(['code' => 1, 'data' => '', 'msg' => 'Successful payment']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'The auction did not succeed']);
        }
    }

		// handle pickup
    public function take_auction(){
        $param = input('post.');
        $find = Db::name('auction_goods')->where('id',$param['id'])->find();
        if($find['status'] != 6){
            return json(['code' => 0, 'data' => '', 'msg' => 'The item has been processed']);
        }
        if($find['pay_status'] != 1){
            return json(['code' => 0, 'data' => '', 'msg' => 'The item has not been paid for the remainder']);
        }
        if($find['goods_status'] != 0){
            return json(['code' => 0, 'data' => '', 'msg' => 'The item has been shipped']);
        }
        $edit = [
            'status'        => 7,
            'shipping_name' => $param['shipping_name'],
            'invoice_no'    => $param['invoice_no'],
            'goods_status'  => 1,
            'update_time'   => time()
        ];
        Db::name('auction_goods')->where('id',$find['id'])->update($edit);
        return json(['code' => 1, 'data' => '', 'msg' => 'Shipped successfully']);
    }

    //Second auction review
    public function re_auction(){
        $param = input('post.');
        $find = Db::name('auction_goods')->where('id',$param['id'])->find();
        /*if($find['status'] != 1){
			return json(['code' => 0, 'data' => '', 'msg' => 'The item has been processed']);
        }*/
        $param['goods_content'] = input('post.goods_content','',null);
        $password = input('post.password');
        $is_private = input('post.is_private');
        $param['start_price'] = $param['price'];
        
        if(!$find['start_time'] || !$find['end_time']){
            return json(['code' => 0, 'data' => '', 'msg' => 'Please set the auction start or end time']);
        }

        if($param['longtime']<=0){
            return json(['code' => 0, 'data' => '', 'msg' => 'Auction duration must be greater than 0']);
        }

       /* if(strtotime($param['start_time']) <= time()){
            return json(['code' => 0, 'data' => '', 'msg' => 'The start time cannot be less than the current time']);
        }*/
        
        if($param['price_range'] <= 0){
            return json(['code' => 0, 'data' => '', 'msg' => 'The price increase should be greater than 0']);
        }

        $param['end_time'] = date('Y-m-d "H:i:s',strtotime($param['start_time']) + ($param['longtime'] * 60));

        if($is_private==1){
            if(!$password){
                return json(['code' => 0, 'data' => '', 'msg' =>'non-public auction, please fill in the private password']);
            }
        }else{
            $param['password']='';
        }

        $param['status']    = 3;
        $param['is_finish'] = 1;
        $param['auction_num'] = 2;
        $param['create_time'] = time();
        $param['update_time'] = time();
        $param['apply_id'] = $param['id'];
        $param['service_fee'] = 0.08;
        unset($param['id']);
        Db::name('auction_goods')->where('id',$find['id'])->update(['status'=>7,'update_time'=>time()]);
        Db::name('auction_goods')->insert($param);

        return json(['code' => 1, 'data' => '', 'msg' => 'Processed successfully']);
        
    }

		//delete
    public function del_auction_goods()
    {
        $id = input('param.id');
        $status = Db::name('auction_goods')->where('id', $id)->value('status');
        if($status == 4){
            return json(['code' => 0, 'data' => '', 'msg' => 'The auction is in progress and cannot be deleted']);
        }
        $flag = Db::name('auction_goods')->where('id', $id)->update(['is_delete'=>1]);
        if($flag){
            Db::name('auction_msg')->where('auction_id',$id)->delete();
            return json(['code' => 1, 'data' => '', 'msg' => 'Delete successful']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'deletion failed']);
        }

    }


    /********************************************************Auction red envelopes******************************************************************** */
    /**
     * Red Packet List
     *
     * @return void
     */
    public function redbag()
    {
        $Nowpage = input('get.page',1);
        $limits = input('get.limit',10);
        $status = input('get.status');
        $type = input('get.type');
        $act = input('get.act');
        $map=[];
        if($status != ''){
            $map[] = ['status','=',$status];
        }
        if($act != ''){
            $map[] = ['act','=',$act];
        }
        if($type != ''){
            $map[] = ['type','=',$type];
        }
        $count = Db::name('auction_redbag')->where($map)->count();
        $list=Db::name('auction_redbag')->where($map)->page($Nowpage,$limits)->order('id desc')->select();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);
    }
    /**
     * Add red envelope
     *
     * @param Type $var
     * @return void
     */
    public function addredbag()
    {
        if(request()->isPost()){
            $data=input('post.');
            if($data['min_number']<=0||$data['max_number']<=0||$data['rate']<=0){
				return json(['code'=>0,'data'=>[],'msg'=>'minimum/maximum/probability cannot be less than or equal to 0']);
            }
            if(empty($data['type'])){
                return json(['code'=>0,'data'=>[],'msg'=>'Red packet type cannot be empty']);
            }
            if(empty($data['min_number'])){
                return json(['code'=>0,'data'=>[],'msg'=>'minimum value cannot be empty']);
            }
            if(empty($data['max_number'])){
                return json(['code'=>0,'data'=>[],'msg'=>'Maximum value cannot be empty']);
            }
            if(empty($data['rate'])){
                return json(['code'=>0,'data'=>[],'msg'=>'probability cannot be empty']);
            }
            $data['create_time']=time();
            $data['update_time']=time();
            if(Db::name('auction_redbag')->save($data)){
                return json(['code'=>1,'data'=>[],'msg'=>'Added successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'Add failed']);
            }
            
            
        }
    }
    /**
     * Modify red envelopes
     *
     * @return void
     */
    public function editredbag()
    {
        if(request()->isPost()){
            $data=input('post.');
            if($data['min_number']<=0||$data['max_number']<=0||$data['rate']<=0){
				return json(['code'=>0,'data'=>[],'msg'=>'minimum/maximum/probability cannot be less than or equal to 0']);
            }
            if(empty($data['type'])){
                return json(['code'=>0,'data'=>[],'msg'=>'Red packet type cannot be empty']);
            }
            if(empty($data['min_number'])){
                return json(['code'=>0,'data'=>[],'msg'=>'minimum value cannot be empty']);
            }
            if(empty($data['max_number'])){
                return json(['code'=>0,'data'=>[],'msg'=>'Maximum value cannot be empty']);
            }
            if(empty($data['rate'])){
                return json(['code'=>0,'data'=>[],'msg'=>'probability cannot be empty']);
            }
            $data['update_time']=time();
            if(Db::name('auction_redbag')->save($data)){
                return json(['code'=>1,'data'=>[],'msg'=>'edited successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'edit failed']);
            }
        }
        $id=input('id');
        $res=Db::name('auction_redbag')->where('id',$id)->find();
        return json(['code'=>1,'data'=>$res,'msg'=>'']);
    }
    /**
     * red envelope status
     *
     * @return void
     */
    public function stateredbag()
    {
        $id=input('param.id');
        $status = Db::name('auction_redbag')->where(array('id'=>$id))->value('status');//Judging the current state
        if($status==1)
        {
            $flag = Db::name('auction_redbag')->where(array('id'=>$id))->update(['status'=>2]);
			return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }
        else
        {
            $flag = Db::name('auction_redbag')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
    }
    /**
     * delete red packet
     *
     * @return void
     */
    public function delredbag()
    {
        $id = input('param.id');
		$status = Db::name('auction_redbag')->where(array('id'=>$id))->value('status');//Judge the current status
        if($status==2){
            return json(['code' => 0, 'data' => [], 'msg' => 'The status is enabled and cannot be deleted']);
        }
        $flag = Db::name('auction_redbag')
              ->where('id', $id)
              ->delete();
        if($flag){
            return json(['code' => 1, 'data' => '', 'msg' => 'Delete successful']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'deletion failed']);
        }
    }

}