<?php
/**
 * 拍卖
 */

namespace app\api\controller;

use think\facade\Db;
use app\common\model\Address;
use app\common\service\Users;
use app\common\model\MemberWalletLogModel;
class Auction extends Base
{

    /**
     * Apply for auction
     * @param $goods_names Goods name
     * @param $goods_img product image
     * @param $goods_content product details
     */
    public function apply_auction($goods_name,$goods_img='',$goods_content='',$contact='',$valuation=0,$price=0,$start_time=0,$is_private,$price_range=0,$password =''){
        $user_id = $this->uid();
        if(!$goods_name){
            return json(['status' => 0, 'msg' => 'Commodity name cannot be empty']);
        }
        if(!$contact){
            return json(['status' => 0, 'msg' => 'Please fill in the contact information']);
        }
        /*if($valuation <= 0){
            return json(['status' => 0, 'msg' => 'Evaluation must be greater than 0']);
        }*/
        if($price < 0){
            return json(['status' => 0, 'msg' => 'The starting price must be greater than or equal to 0']);
        }
        if($is_private==0){
            $password = '';
        }else{
            if(!$password){
                return json(['status' => 0, 'msg' => 'Please fill in the private password for private auctions']);
            }
        }

        /*if(!$start_time){
        return json(['status' => 0, 'msg' => 'Please select a start date']);
        }
        $find = Db::name('auction_goods')->where(['goods_name'=>$goods_name,'is_delete'=>0])->where('status','<>',2)-> value('id');
        if($find){
            return json(['status' => 0, 'msg' => 'The auction name has been occupied']);
        }*/
        $goods_content = input('goods_content','',null);
        $add = [
            'uid'           => $user_id,
            'goods_name'    => $goods_name,
            'goods_img'     => $goods_img,
            'goods_content' => $goods_content,
			'contact' => $contact, //contact information
            'valuation' => $valuation, //valuation
            'price' => $price, //starting price
            'start_time' => $start_time,
            'is_private' => $is_private,
            'password' => $password,
            'price_range' => $price_range,
            'create_time' => time(),
            'update_time' => time()
        ];
        $res = Db::name('auction_goods')->insert($add);
        if($res){
            return json(['status' => 1, 'msg' => 'application successful']);
        }else{
            return json(['status' => 0, 'msg' => 'submission failed']);
        }
    }

    public function apply_auctions($goods_name,$goods_img='',$goods_content='',$contact='',$valuation=0,$price=0,$start_time=NULL,$is_private,$password=''){
        $user_id = $this->uid();
        if(!$goods_name){
			return json(['status' => 0, 'msg' => 'Commodity name cannot be empty']);
        }
        if(!$contact){
            return json(['status' => 0, 'msg' => 'Please fill in the contact information']);
        }
        if($valuation <= 0){
            return json(['status' => 0, 'msg' => 'Evaluation must be greater than 0']);
        }
        if($is_private==0){
            $password = '';
        }else{
            if(!$password){
                return json(['status' => 0, 'msg' => 'non-public auction, please fill in the private password']);
            }
        }
        // if(!$start_time){
        // return json(['status' => 0, 'msg' => 'Please select a start date']);
        // }
        /* $find = Db::name('auction_goods')->where(['goods_name'=>$goods_name,'is_delete'=>0])->where('status','<>',2) ->value('id');
         if($find){
             return json(['status' => 0, 'msg' => 'The auction name has been occupied']);
         }*/
        $add = [
            'uid'           => $user_id,
            'goods_name'    => $goods_name,
            'goods_img'     => $goods_img,
            'goods_content' => $goods_content,
			'contact' => $contact, //contact information
            'valuation' => $valuation, //valuation
            'start_time'    => $start_time,
            'is_private'    => $is_private,
            'password'      => $password,
            'create_time'   => time(),
            'update_time'   => time()
        ];
        $res = Db::name('auction_goods')->insert($add);
        if($res){
			return json(['status' => 1, 'msg' => 'application successful']);
        }else{
            return json(['status' => 0, 'msg' => 'submission failed']);
        }
    }

    //my auction
    public function my_auction($type=0,$page=1, $limit=8,$status=0){
        $user_id = $this->uid();
        $map = [];
        $map[] = ['is_delete','=',0];
        //1 auctioneer 2 bidders
        if($type == 1){
            $map[] = ['uid','=',$user_id];
            if($status!=0){
                if($status==1){
                    $statuss = 0;
                }elseif($status==2){
                    $statuss = 2;
                }else{
                    $statuss = -1;
                }
                if($statuss>=0){
                    $map[] = ['status','=',$statuss];
                }

                if($status==3){
                    $is_finish = 1;
                }elseif($status==4){
                    $is_finish = 3;
                }elseif($status==5){
                    $is_finish = 2;
                }else{
                    $is_finish = -1;
                }
                if($is_finish>0){
                    $map[] = ['is_finish','=',$is_finish];
                }

            }

        }else if($type == 2){
            $map[] = ['status','<>',4];
            $map[] = ['bidders','=',$user_id];
        }else{
            return json(['status' => 0, 'msg' => 'Wrong type selected']);
        }

        $list = Db::name('auction_goods')->where($map)
            ->order(['id'=>'desc'])
            ->page($page,$limit)
            ->select()->toArray();
        //Is it expired
        foreach ($list as $k => $v) {

            $where = [];
            $where[] = ['id','=',$v['uid']];
            $user_info = Db::name('member')->where($where)->find();
            $list[$k]['account'] = "By ".$user_info['nickname'];

            $list[$k]['is_overdue'] = 0;
            $list[$k]['dkprice_time'] = '';
            $list[$k]['is_collect'] = 0;
            if(Db::name('auction_collect')->where(['uid'=>$user_id,'auction_id'=>$v['id']])->count()){
                $list[$k]['is_collect'] = 1;
            }
            if($v['bidders']){
                if($v['status'] == 1 && $v['pay_status'] == 0){
                    $list[$k]['dkprice_time'] = date('Y-m-d H:i:s',strtotime($v['end_time'])+86400);
                    if(strtotime($v['end_time']) > (time()-3600)){
                        $list[$k]['is_overdue'] = 1;
                        $list[$k]['pay_money'] = $v['price'] - bcmul($v['price'],0.2,2);
                    }else{
                        $list[$k]['is_overdue'] = 2;
                    }
                }else if($v['pay_status'] == 0){
                    $list[$k]['is_overdue'] = 2;
                }

                //Countdown to the second auction
                if($v['status'] == 1 && $v['pay_status'] == 1){
                    $list[$k]['end_time'] = date('Y-m-d H:i:s',strtotime($v['end_time'])+3600*15*24);
                }

            }
        }
        return json(['status' => 1,'data'=>['list'=>$list],'times'=>time(), 'msg' => 'Get data successfully']);
    }

    //my appointment
    public function auction_arrange($page=1, $limit=8){
        $user_id = $this->uid();
        $map = [];
        $map[] = ['a.uid','=',$user_id];
        $list = Db::name('auction_msg')->alias('a')->join('auction_goods b','a.auction_id=b.id')
            ->field('a.*,b.goods_img,b.price,b.is_private,b.start_time,b.end_time,b.is_finish,b.status')
            ->where($map)->order('id desc')->page($page,$limit)->select();
        return json(['status' => 1,'data'=>['list'=>$list], 'msg' => 'Get data successfully']);
    }

		//Auction pick up application
    public function auction_take_goods($id, $address_id){

        $user_id = $this->uid();
        $auction_goods = Db::name('auction_goods')->where(['id'=>$id,'bidders'=>$user_id])->find();
        if(!$auction_goods){
            return json(['status' => 0, 'msg' => 'No auction information']);
        }
        if($auction_goods['goods_status'] !=0 || $auction_goods['status'] == 6){
            return json(['status' => 0, 'msg' => 'The auction has applied for delivery']);
        }
        if($auction_goods['pay_status'] !=1){
            return json(['status' => 0, 'msg' => 'The auction has not been paid']);
        }
        $addrModel = new Address();
        //call shipping address //mailing address
        $address = $addrModel->getNormalAddress($user_id,$address_id);
        if (!$address['status']) {
            return json(['status' => 0, 'msg' => 'Please set the logistics address first']);
        }
        $edit = [
            'status'=>6,
            'consignee' => $address['data']['consignee'],
            'consignee_mobile' => $address['data']['mobile'],
            'consignee_address'=>$address['data']['address_detail']
        ];
        $res = Db::name('auction_goods')->where(['id'=>$id])->update($edit);

        if($res){
			return json(['status' => 1, 'msg' => 'application successful']);
        }else{
            return json(['status' => 0, 'msg' => 'submission failed']);
        }
    }

    //Secondary auction application
    //$auction last auction id
    public function re_auction(){
        $user_id = $this->uid();
        $post=input('post.');
        $auction_id = $post['auction_id'];
        $goods_name=$post['goods_name'];
        $goods_img=$post['goods_img'];
        $goods_content=$post['goods_content'];
        $contact=$post['contact'];
        $valuation=$post['valuation'];
        $start_time=$post['start_time'];
        $is_private=$post['is_private'];
        $password=$post['password'];

        if(!$goods_name){
			return json(['status' => 0, 'msg' => 'Commodity name cannot be empty']);
        }
        if(!$contact){
            return json(['status' => 0, 'msg' => 'Please fill in the contact information']);
        }
        /*if($valuation <= 0){
            return json(['status' => 0, 'msg' => 'Evaluation must be greater than 0']);
        }*/
        if($valuation < 0){
            return json(['status' => 0, 'msg' => 'The starting price must be greater than or equal to 0']);
        }

        $auction_goods = Db::name('auction_goods')->where(['id'=>$auction_id,'bidders'=>$user_id])->find();
        if(!$auction_goods){
            return json(['status' => 0, 'msg' => 'No auction information']);
        }
        if($auction_goods['status'] != 1 ){
            return json(['status' => 0, 'msg' => 'The goods can only be picked up after the auction is over']);
        }
        if($auction_goods['pay_status'] !=1){
            return json(['status' => 0, 'msg' => 'The auction has not been paid']);
        }

        if(strtotime($auction_goods['end_time']) >= time()-3600*24*15){
            return json(['status' => 0, 'msg' => 'You can apply for another auction after 15 days']);
        }

        if($is_private==0){
            $password = '';
        }else{
            if(!$password){
                return json(['status' => 0, 'msg' => '非公开拍卖请填写私有密码']);
            }
        }
		// if(!$start_time){
        // return json(['status' => 0, 'msg' => 'Please select a start date']);
        // }
        /*$find = Db::name('auction_goods')->where(['goods_name'=>$goods_name,'is_delete'=>0])->where('id','<>',$auction_id )->value('id');
        if($find){
            return json(['status' => 0, 'msg' => 'The auction name has been occupied']);
        }*/
        $goods_content = input('goods_content','',null);
        $add = [
            'uid'           => $user_id,
            'goods_name'    => $goods_name,
            'goods_img'     => $goods_img,
            'goods_content' => $goods_content,
			'contact' => $contact, //contact information
            'valuation' => 0, //valuation
            'price' => $valuation, //starting price
            'start_time'    => $start_time,
            'is_private'    => $is_private,
            'password'      => $password,
            'auction_num'   => 2,
            'create_time'   => time(),
            'update_time'   => time(),
            'apply_id'      =>$auction_id,
            'service_fee'   =>0.08
        ];
        DB::name('auction_goods')->where('id',$auction_id)->update(['status'=>5,'update_time'=>time()]);
        $res = Db::name('auction_goods')->insert($add);

        if($res){
			return json(['status' => 1, 'msg' => 'application successful']);
        }else{
            return json(['status' => 0, 'msg' => 'submission failed']);
        }
    }

    /**
     * My favorites list
     */
    public function auction_collect_list($page=1, $limit=8){
        $user_id = $this->uid();
        $list = Db::name('auction_collect')->alias('a')
            ->join('auction_goods g','g.id=a.auction_id')
            ->where('a.uid', $user_id)
            //->field('a.uid,a.yprice,a.price,a.create_time,a.update_time,b.nickname')
            ->order(['a.id'=>'desc'])->page($page,$limit)
            ->select();
             return json(['status' => 1,'data'=>['list'=>$list], 'times'=>time(),'msg' => 'data obtained successfully']);
    }

    /**
     * collect
     */
    public function auction_collect($id=0){
        $user_id = $this->uid();
//        $user_id = 1112;
        $add = [
            'auction_id'=>$id,
            'uid'=>$user_id,
            'time'=>time(),
        ];
		//Determine whether it has been favorited
        $getOne = Db::name('auction_collect')->where(['uid'=>$user_id,'auction_id'=>$id])
            ->find();
        //var_dump($getOne);
        if($getOne){
            Db::name('auction_collect')->where('id',$getOne['id'])->delete();
            return json(['status' => 1, 'msg' => 'cancelled']);
        }
        $res = Db::name('auction_collect')->insert($add);

        if($res){
            return json(['status' => 1, 'msg' => 'collection success']);
        }else{
            return json(['status' => 0, 'msg' => 'collection failed']);
        }
    }

    //Auction listing
    public function auction_list($type=0, $page=1, $limit=8){
        //$user_id = $this->uid();
        $uid=get_uid();
        $map = [];
        $map[] = ['is_delete','=',0];
        if($type == 0){
            $map[] = ['is_finish','in','3'];
        }else if($type == 1){
            $map[] = ['is_finish','in','1'];
        }else if($type == 2){
            $map[] = ['is_finish','in','2'];
        }else{
            return json(['status' => 0, 'msg' => 'Wrong type selected']);
        }
        $list = Db::name('auction_goods')->where($map)
            ->order(['orderby'=>'asc','id'=>'desc'])
            ->field('id,uid,goods_name,goods_img,goods_content,price,start_time,end_time,is_private,pay_status,status,is_finish,create_time,update_time')
            ->page($page,$limit)
            ->select();
        $tmp = [];
        foreach ($list as $key=>$val){
            $where = [];
            $where[] = ['id','=',$val['uid']];
            $user_info = Db::name('member')->where($where)->find();
            $val['account'] = "By ".$user_info['nickname'];

            if($uid) {
                $getOne = Db::name('auction_collect')->where(['uid' => $uid, 'auction_id' => $val['id']])
                    ->find();
                $val['is_collect'] = 0;
                if ($getOne) {
                    $val['is_collect'] = 1;
                }
            }else{
                $val['is_collect'] = 0;
            }
            $tmp[$key] = $val;
        }

        return json(['status' => 1,'data'=>['list'=>$tmp],'times'=>time(), 'msg' => 'Get data successfully']);
    }

			//current details
     public function get_now_auction($id){
         $user_id=get_uid();
         $getOne = Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])
             ->field('id,uid,goods_name,goods_img,goods_content,start_price,price,price_range,start_time,end_time,valuation,contact,longtime,is_private,pay_status,view_num,join_num,call_num,status,is_finish,bidders,create_time,update_time ')
             ->find();
         if(!$getOne){
             return json(['status' => 0, 'msg' => 'The auction no longer exists']);
         }
         if($getOne['is_private'] == 1){
             $auction_id = session('auction_id');
             if($getOne['pay_status'] !=0 && $user_id != $getOne['bidders']){
                 if($auction_id != $getOne['id'] && $getOne['pay_status'] !=0){
                     return json(['status' => 0, 'msg' => 'Please enter your password to enter']);
                }
            }

        }

        $getOne['is_call'] = 0;
        if(Db::name('auction_msg')->where(['auction_id'=>$id,'uid'=>$user_id])->value('id')){
            $getOne['is_call'] = 1;
        }
        //Is it expired
        $getOne['is_overdue'] = 0;
        if($getOne['bidders'] == $user_id){
            if($getOne['status'] == 1 && $getOne['pay_status'] == 0){
                if(strtotime($getOne['end_time']) > (time()-3600)){
                    $getOne['is_overdue'] = 1;
                    $getOne['pay_money'] = $getOne['price'] - bcmul($getOne['price'],0.2,2);
                }else{
                    $getOne['is_overdue'] = 2;
                }
            }else if($getOne['pay_status'] == 0){
                $getOne['is_overdue'] = 2;
            }
        }

        if($getOne['goods_img']){
            $getOne['img_tuku'] = explode(',',$getOne['goods_img']);
        }
        if($getOne['status'] == 3 || $getOne['status'] == 4){
            Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])->inc('view_num')->update();
        }else{
            Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])->update();
        }

        return json(['status' => 1,'data'=>$getOne,'times'=>time(), 'msg' => 'Get data successfully']);

    }

	//Auction reminder
    //$time advance time
    public function auction_call($id,$time=0){
        $user_id = $this->uid();
        $getOne = Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])
            ->field('id,uid,goods_name,start_time,is_private,call_num,status')
            ->find();
        if(!$getOne){
            return json(['status' => 0, 'msg' => 'The auction no longer exists']);
        }
        if($getOne['status'] != 3){
            return json(['status' => 0, 'msg' => 'Reminders can only be set until the auction starts']);
        }


        $member = Db::name('member')->where('id',$user_id)->field('country_code,mobile')->find();
        if(!$member['mobile']){
            return json(['status' => 0, 'msg' => 'You haven't bound your phone number']);
        }

        if(Db::name('auction_msg')->where(['auction_id'=>$id,'uid'=>$user_id])->value('id')){
            Db::name('auction_msg')->where(['auction_id'=>$id,'uid'=>$user_id])->delete();
            Db::name('auction_goods')->where('id',$getOne['id'])->dec('call_num')->update();
            return json(['status' => 1, 'msg' => 'Reminder cancelled']);
            // return json(['status' => 0, 'msg' => 'Reminder is set']);
        }else{

            if($time < 2){
                return json(['status' => 0, 'msg' => 'Set reminders for at least two minutes or more']);
            }

            $msg_time = strtotime($getOne['start_time']) - ($time*60);
            if($msg_time < time() + $time*60 ){
                return json(['status' => 0, 'msg' => 'Set lead time not in range']);
            }

            $add = [
                'auction_id'    => $getOne['id'],
                'goods_name'    => $getOne['goods_name'],
                'uid'           => $user_id,
                'country_code'  => $member['country_code'],
                'mobile'        => $member['mobile'],
                'time'          => $time,
                'msg_time'      => $msg_time
            ];
            Db::name('auction_msg')->where(['auction_id'=>$id,'uid'=>$user_id])->insert($add);
            Db::name('auction_goods')->where('id',$getOne['id'])->inc('call_num')->update();
            return json(['status' => 1, 'msg' => 'set successfully']);
        }
    }

       //Auction make-up fee
    public function renew_auction($id,$password){

        $user_id = $this->uid();
        $find = Db::name('auction_goods')->where(['id'=>$id,'bidders'=>$user_id])->find();
        if(!$find){
            return json(['status' => 0, 'msg' => 'The item you bid for was not found']);
        }
        if($find['pay_status'] == 1){
            return json(['status' => 0, 'msg' => 'The competitor has paid the fee']);
        }
        if($find['status'] != 1){
            return json(['status' => 0, 'msg' => 'Auction has been processed!']);
        }

        //transaction password
        $jypwd=base64_encode(md5($password,true));
        $pay_pwd=Db::name('member')->where('id',$user_id)->value('pay_password');
        if(!$pay_pwd){
            return json(['code'=>0,'data'=>[],'msg'=>'Please set the transaction password first']);
        }
        if($jypwd != $pay_pwd){
            return json(['code'=>0,'data'=>[],'msg'=>'transaction password is incorrect']);
        }
        if(strtotime($find['end_time'])+86400 < time()){
            return json(['status' => 0, 'msg' => 'Failed to pay the fee, you did not pay the fee within the validity period']);
        }
        $money = Db::name('member')->where('id',$user_id)->value('money');
        $fee = $find['price'] - bcmul($find['price'],0.2,2);
        if($fee > $money){
            return json(['status' => 0, 'msg' => 'Your KRC is insufficient, please recharge']);
        }
        // start things
        Db::startTrans();
        try {
            Db::name('auction_goods')->where('id',$find['id'])->update(['pay_status'=>1,'update_time'=>time()]);
            Db::name('member')->where('id',$user_id)->dec('money',$fee)->update();
            $MemberWalletLogModel = new MemberWalletLogModel();
            $MemberWalletLogModel->log($user_id,$fee,$money,$money + $fee,133,'Auction make-up fee',$find['id']);
            //TODOO:: auction
            // Db::name('member')->where([['id','=',$user_id],['status','=',1]])->inc('pm_rednum',1)->update();
            Db::name('get_record')->save(['uid'=>$user_id,'type'=>4,'act'=>$user_id,'create_time'=>time(),'update_time'=>time()]);
            Db::commit();
        } catch (\Exception $e) {
            // rollback transaction
            Db::rollback();
            return json(['status' => 0, 'msg' => $e->getMessage()]);
        }

        return json(['status' => 1,'is_red'=>config('config.open_redbag'), 'msg' => 'payment successful']);

    }

    //Enter the auction Enter password
    public function into_auction($id,$password = ''){
        $user_id = $this->uid();
        $getOne = Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])
            ->field('id,uid,goods_name,goods_img,goods_content,price,start_time,end_time,longtime,is_private,pay_status,view_num,join_num,call_num,status,is_finish,create_time,update_time,password')
            ->find();
        if(!$getOne){
			return json(['status' => 0, 'msg' => 'The auction no longer exists']);
        }
        if($getOne['is_private'] == 1){
            if(!$password){
                return json(['status' => 0, 'msg' => 'Please enter your password to enter']);
            }
            if($password != $getOne['password']){
                return json(['status' => 0, 'msg' => 'input password error']);
            }
            session('auction_id',$getOne['id']);

        }
        $getOne['is_call'] = 0;
        if(Db::name('auction_msg')->where(['auction_id'=>$id,'uid'=>$user_id])->value('id')){
            $getOne['is_call'] = 1;
        }
        unset($getOne['password']);
        return json(['status' => 1,'data'=>$getOne, 'msg' => 'Get data successfully']);
    }

    /**
     * Auction increase
     * $id auction item id
     * $yprice original price
     */
    public function auction_price($id,$yprice){
        $user_id = $this->uid();
        $filenames=app()->getRuntimePath().'auction'.$id.'.log';
        if(!is_file($filenames)){
            file_put_contents($filenames,$id);
        }
        $fp = fopen($filenames, "r");
        if(flock($fp,LOCK_EX)){
            $getOne = Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])
                ->field('id,uid,goods_name,goods_img,goods_content,price,price_range,start_time,end_time,is_private,join_num,pay_status,bidders,status,create_time,update_time')
                ->find();
            if(!$getOne){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'This auction no longer exists']);
            }
            if($getOne['is_private'] == 1){
                $auction_id = session('auction_id');
                if($auction_id != $getOne['id']){
                    flock($fp,LOCK_UN);
                    fclose($fp);
                    return json(['status' => 0, 'msg' => 'Please enter the password to enter']);
                }
            }
            if($getOne['status'] != 4){
                flock($fp,LOCK_UN);
                fclose($fp);
			return json(['status' => 0, 'msg' => 'Auction has ended']);
            }
            $money = Db::name('member')->where('id',$user_id)->value('money');
            $deposit = ($getOne['price'] + $getOne['price_range']) * 0.2;
            if($money < $deposit){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'Your KRC is not enough to be deducted, margin'. $deposit]);
            }
            $member_auction = Db::name('member_auction')->where(['uid'=>$user_id,'auction_id'=>$getOne['id']])->value('id');
            if($user_id == $getOne['uid']){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'Cannot participate in the auction of your own products']);
            }
            if($user_id == $getOne['bidders']){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'Bid failed, you are currently the highest bid']);
            }
            //Has anyone increased the price
            // if($yprice == $getOne['price']){
            $add = [
                'uid' => $user_id,
                'auction_id' => $getOne['id'],
                'yprice'     => $getOne['price'],
                'price'      => $getOne['price'] + $getOne['price_range'],
                'create_time'=> time(),
                'update_time'=> time()
            ];
            //open transaction
            Db::startTrans();
            try {
                //Update markup
                $update = [
                    'bidders'=>$user_id,
                    'auction_time'=>time(),
                    'update_time'=>time()
                ];
                //Determine whether it is the last five minutes to increase the price
                if((strtotime($getOne['end_time']) - 300) <= time()){
                    $update['end_time'] = date('Y-m-d H:i:s',strtotime($getOne['end_time'])+300);
                }

                if(!$member_auction){
                    $update['join_num'] = $getOne['join_num'] + 1;
                }

                $MemberWalletLogModel = new MemberWalletLogModel();

                Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])->inc('price',$getOne['price_range'])->update($update);

                Db::name('member')->where('id',$user_id)->dec('money',$deposit)->update(['is_auction'=>1]); //20% of the deposit is set to participate in the auction user
                $MemberWalletLogModel->log($user_id,$deposit,$money,$money - $deposit ,131,'Auction deduction deposit',$id);
                Db::name('member_auction')->insert($add);
                if($getOne['bidders']){
                    $chuju = config('config.chuju')/100;
                    //Release the deposit of the previous person and reward the previous person with % of the markup
                    $re_deposit = bcadd(bcmul($getOne['price'],0.2,2),bcmul($getOne['price_range'],$chuju,2),2);
                    $bidders_money = Db::name('member')->where('id',$getOne['bidders'])->value('money');
                    Db::name('member')->where('id',$getOne['bidders'])->inc('money',$re_deposit)->update();
                    $Users = new Users();
                    $Users->pm_jiangli($getOne['price_range'],$getOne['bidders']);
                    $MemberWalletLogModel->log($getOne['bidders'],bcmul($getOne['price'],0.2,2),$bidders_money,$bidders_money + bcmul($getOne['price'],0.2,2) ,132,'Auction release bond',$id);
                    if(bcmul($getOne['price_range'],$chuju,2)>0){
                        $MemberWalletLogModel->log($getOne['bidders'],bcmul($getOne['price_range'],$chuju,2),$bidders_money,$bidders_money + bcmul($getOne['price_range'],$chuju,2) ,134,'Auction Rewards',$id);
                    }
                }

                //Daily task judgment completed Auction
                $pminfo = Db::name('assignment')->where(['jl_type'=>5,'status'=>1,'type'=>1])->find();
                if($pminfo){
                    $Users = new Users();
                    $Users->daytask($pminfo['id'],$user_id);
                }
                Db::commit();
                flock($fp,LOCK_UN);
                fclose($fp);
				return json(['status' => 1, 'msg' => 'Successful price increase']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                flock($fp,LOCK_UN);
                fclose($fp);
                // return json(['status' => 0, 'msg' => 'price increase failed']);
                return json(['status' => 0, 'msg' => $e->getMessage()]);
            }

            // }else{
            // return json(['status' => 0, 'msg' => 'The price increase failed, please try again']);
            // }

        }
    }
    /**
     * open red envelopes
     *
     * @return void
     */
    public function getredbag()
    {
        $user_id = $this->uid();
        // return json(['code'=>0,'msg'=>config('config.open_redbag')]);
        $type=input('type');    //1 recommendation 2 payment on behalf of 3 direct purchase 4 auction
        $filenames=app()->getRuntimePath().'redbag'.$user_id.'.log';
        if(!is_file($filenames)){
            file_put_contents($filenames,$user_id);
        }
        $fp = fopen($filenames, "r");
        if(flock($fp,LOCK_EX)){
            if(empty($type)){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'Operation error']);
            }
            
            if(config('config.open_redbag')==0){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'The grab bag function is not enabled']);
            }
            $member=Db::name('member')->where('id',$user_id)->find();
            $red_msg=Db::name('get_record')->where([['uid','=',$user_id],['type','=',$type],['status','=',0]])->order('id asc')->find();
            if(empty($red_msg)){
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => "No red envelopes received"]);
            }
            $save_num=$this->save_num($type);  //minus one
            
            $MemberWalletLogModel = new MemberWalletLogModel();
            $Users=new Users();
			// start transaction
            Db::startTrans();
            try {
                //The red envelope is randomly paid according to the user type and red envelope type
                $money=$Users->give_redbag($member['is_set_type'],$type);
                //Add money and reduce the number of times by 1
                // return json(['status' => $money, 'dec'=>$member[$save_num['field']], 'inc'=>$member[$save_num['add_field']],'msg' => '1111']);
                Db::name('member')->where('id',$user_id)->inc('money',$money)->update();
                Db::name('get_record')->where('id',$red_msg['id'])->update(['money'=>$money,'status'=>1,'update_time'=>time()]);
                $MemberWalletLogModel->log($user_id,$money,$member['money'],$member['money'] + $money ,$save_num['type'],$save_num['remark'],$user_id);
                // sleep(10);
                // commit transaction
                Db::commit();
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 1, 'money'=>$money, 'msg' => 'Successfully grab the package']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                flock($fp,LOCK_UN);
                fclose($fp);
                return json(['status' => 0, 'msg' => 'Failed to grab packet']);
            }
        }
        
    }
    public function redbag_before_info($type)
    {
        $user_id = $this->uid();
        $type=input('type');    //1 recommendation 2 payment on behalf of 3 direct purchase 4 auction
        if(empty($type)){
            return json(['status' => 0, 'msg' => 'Operation error']);
        }
		$info['zong_num']=Db::name('get_record')->where([['uid','=',$user_id],['type','=',$type],[' status','=',1]])->count(); //The total number of times the current red envelope type has been received

        $info['zong_money']=Db::name('get_record')->where([['uid','=',$user_id],['type','=',$type],[' status','=',1]])->sum('money');//The current red envelope type has received the total amount of money

        $starttime=strtotime(date("Y-m-d"),time());

        $info['today_yetnum']=Db::name('get_record')->where([['uid','=',$user_id],['type','=',$type],[' status','=',1],['create_time','>=',$starttime],['create_time','<=',$starttime+86400]])->count(); //Today Number of times received

        $info['today_num']=Db::name('get_record')->where([['uid','=',$user_id],['type','=',$type],[' status','=',0],['create_time','>=',$starttime],['create_time','<=',$starttime+86400]])->count(); //Today Unclaimed times

        $info['today_money']=Db::name('get_record')->where([['uid','=',$user_id],['type','=',$type],[' status','=',1],['create_time','>=',$starttime],['create_time','<=',$starttime+86400]])->sum('money'); //The current red envelope type has received the total amount of money today
        return json(['code'=>1,'data'=>$info,'msg'=>'']);
    }
    public function save_num($type)
    {
        switch ($type) {
            case 1:
                // $arr['field']='tj_rednum';
                // $arr['add_field']='tj_redyet';
                $arr['type']=139;
				$arr['remark']='recommended red envelope reward';
                break;
            case 2:
                // $arr['field']='df_rednum';
                // $arr['add_field']='df_redyet';
                $arr['type']=138;
                $arr['remark']='Pay for red envelope rewards';
                break;
            case 3:
                // $arr['field']='zg_rednum';
                // $arr['add_field']='zg_redyet';
                $arr['type']=137;
                $arr['remark']='Direct purchase red envelope reward';
                break;
            case 4:
                // $arr['field']='pm_rednum';
                // $arr['add_field']='pm_redyet';
                $arr['type']=136;
                $arr['remark']='Auction red envelope reward';
                break;
        }
        return $arr;
    }


    /**
     * Single auction information return
     * @param $id auction item id
     */
    public function auction_each($id){

        $getOne = Db::name('auction_goods')->where(['id'=>$id,'is_delete'=>0])
            ->field('id,uid,goods_name,price,price_range,start_time,end_time,view_num,join_num,call_num,is_private,auction_time,status')
            ->find();
        if(!$getOne){
				return json(['status' => 0, 'msg' => 'The auction no longer exists']);
        }
        if($getOne['is_private'] == 1){
            $auction_id = session('auction_id');
            if($auction_id != $getOne['id']){
                return json(['status' => 0, 'msg' => 'Please enter your password to enter']);
            }
        }
        if($getOne['status'] == 4){

            $end_time = strtotime($getOne['end_time']);
            if($end_time < time()){
                Db::name('auction_goods')->where('id',$id)->update(['status'=>1,'is_finish'=>2,'update_time'=>time()]);
                return json(['status'=>0,'data'=>[],'msg'=>'Auction has ended']);

            }

            $djs_time = $this->djs($end_time);

            $return = [
                'id'=>$getOne['id'],
                'djs'=>$djs_time,
                'deposit' => ($getOne['price'] + $getOne['price_range']) * 0.2,
                'price'=>$getOne['price'],
                'status'=>$getOne['status'],
                'view_num'=>$getOne['view_num'],
                'join_num'=>$getOne['join_num'],
                'call_num'=>$getOne['call_num'],
                'end_time'=>$getOne['end_time']
            ];
            return json(['status'=>1,'data'=>$return,'msg'=>'']);
        }else if($getOne['status'] == 1){
			return json(['status'=>0,'data'=>[],'msg'=>'Auction has ended']);
        }else{
            return json(['status'=>0,'data'=>[],'msg'=>'The auction has not been held yet']);
        }
    }

    //List of bidders
    public function member_auction($id, $page=1, $limit=8){
        $list = Db::name('member_auction')->alias('a')->join('member b','b.id=a.uid')
            ->where('auction_id',$id)
            ->field('a.uid,a.yprice,a.price,a.create_time,a.update_time,b.nickname')
            ->order('a.id desc')->page($page,$limit)
            ->select();
        return json(['status'=>1,'data'=>$list,'msg'=>'']);
    }

    /**
     * Express information
     */
    public function get_send_info($id){

        $user_id = $this->uid();
        if (!$order = Db::name('auction_goods')->where(['id' => $id, 'bidders' => $user_id])->field('invoice_no,shipping_name')->find()) {
            return json(['status' => -1, 'msg' => 'There are currently no matching auctions or the auction has not shipped']);
        }
        //$host = "http://jisukdcx.market.alicloudapi.com";
        $host = "https://wuliu.market.alicloudapi.com";
        $path = "/express/query";
        $path = "/kdi";
        $method = "GET";
        $appcode = "e32c5f7604c247ba94a9867fdd8fe23f";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //$querys = "number=$code&type=auto";
        //$querys = "no=$code&type=zto";
        $code = $order['invoice_no'];
        $querys = "no=$code";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result = curl_exec($curl);
        $result = json_decode($result);
        return json(['status'=>1,'order'=>$order,'result'=>$result]);

    }

		// countdown
    public function djs($end_time){
        /* Event countdown */
        $today =time(); //The current timestamp is June 7th
        //$end_time = '2018-6-9 09:00:00'; //The end time of the activity generally queried from the database
        //$second = strtotime($end_time)-$today; //end timestamp minus current timestamp
        $second = $end_time-$today; //end timestamp minus current timestamp
        // echo $second;
        $day = floor($second/3600/24); //How many days are left in the countdown
        $hr = floor($second/3600%24); //How many hours are left in the countdown (% take the remainder)
        $min = floor($second/60%60); //How many minutes are left in the countdown
        $sec = floor($second%60); //How many seconds are left in the countdown
        $str = $day. ' : ' . $hr . ' : ' . $min . ' : ' . $sec; // combine into a string
        return $str;
    }

}