<?php
/**
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/25
 * Time: 8:59
 */

namespace app\api\controller;
use app\api\validate\RechargeValidate;
use app\common\model\Member;
use app\common\model\MemberWalletLogModel;
use app\common\service\Users;
use app\common\model\Withdraw;
use think\facade\Db;
use think\facade\Config;
use think\facade\Cache;
use app\api\validate\WithdrawalValidate;
use think\exception\ValidateException;

	/**
	* Balance Center
	*Class Wallet
	* @package app\api\controller
	*/
	class Money extends Base
	{
    // recharge
    public function recharge(){
        $uid = $this->uid();
        //$uid = 1012;
        $cz_address=Db::name('member')->where('id',$uid)->value('cz_address');
        // Deposit 1 KRC 2 usdt 3 additional coins
        $c_id = input('c_id',1);
        $krc = config('config.shop_cbnk');
        $usdt = config('config.shop_usdt');
        $xxxx = config('config.shop_xxxx');
        //Determine whether to allow recharge
        if($c_id == 1){
            if($krc == 0 || $krc == 2){
                return json(['code'=>0,'data'=>[],'msg'=>'KRC recharge suspended']);
            }
        }

        if($c_id == 2){
            if($usdt == 0 || $usdt == 2){
				return json(['code'=>0,'data'=>[],'msg'=>'usdt recharge suspended']);
            }
        }

        if($c_id == 3){
            if($xxxx == 0 || $xxxx == 2){
                return json(['code'=>0,'data'=>[],'msg'=>'SM suspends recharge']);
            }
        }
        if(!$cz_address){
            $arr = new Users();
            $url = 'getnewaddress';
            $coin = 'eth';
            $params['method'] = 'getnewaddress';
            $params['params'][] = strval($uid);
            $params['id'] = 1;
            $arr = $arr->signedRequest($coin,$url,$params);
            if(empty($arr)){
                return json(['status'=>0,'data'=>[],'msg'=>'Failed to obtain recharge wallet address']);
            }
            //Modify currency account Add recharge address
            Db::name('member')->where('id',$uid)->update(['cz_address'=>$arr['result']]);
            $cz_address=Db::name('member')->where('id',$uid)->value('cz_address');
            return json(['status'=>1,'data'=>$cz_address,'msg'=>'']);
        }else{
            return json(['status'=>1,'data'=>$cz_address,'msg'=>'']);
        }
    }
    /**
     * my recharge list
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function  recharge_lists(){
        $uid=$this->uid();
        $currency = input('currency','');
        $map[] = ['uid', '=', $uid];
        if($currency != '' && $currency){
            $map[] = ['currency', '=', $currency];
        }
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','created_at desc');
        $list=Db::name('recharges')
            ->where($map)
			->withAttr('type', function($value, $data) {if($value==1){return 'background recharge';}else{return 'online recharge';}})->page($page ,$limit)->order($order)->select();
        if(!$list){
            return json(['status'=>1,'data'=>'','msg'=>'No record yet']);
        }
        return json(['status'=>1,'data'=>$list,'msg'=>'']);
    }
    //withdraw
    public function withdraw(){
        $uid= $this->uid();
//        $uid= 1018;
        $arr=Db::name('member')->where('id',$uid)->find();
        $service_charge = [];
        $service_charge[1]=explode(',',config('config.withdraw_service_charge1'));
        $service_charge[2]=explode(',',config('config.withdraw_service_charge2'));
        $service_charge[3]=explode(',',config('config.withdraw_service_charge3'));
			// fee
        $tx_beishu=config('config.tx_beishu');
        if(request()->isPost()){
            // Deposit 1 KRC 2 usdt 3 additional coins
            $c_id = input('post.c_id','');
            $krc = config('config.shop_cbnk');
            $usdt = config('config.shop_usdt');
            $xxxx = config('config.shop_xxxx');
            // Determine if withdrawal is allowed
            if($c_id == 1){
                if($krc == 0 || $krc == 1){
                    return json(['code'=>0,'data'=>[],'msg'=>'KRC suspends withdrawal']);
                }
                $x = 'money';
                $n = 'lock_money';
            }elseif($c_id == 2){
                if($usdt == 0 || $usdt == 1){
                    return json(['code'=>0,'data'=>[],'msg'=>'usdt withdrawal suspension']);
                }
                $x = 'pool_hatch';
                $n = 'lock_usdt';
            }elseif($c_id == 3){
                if($xxxx == 0 || $xxxx == 1){
                    return json(['code'=>0,'data'=>[],'msg'=>'SM suspends withdrawal']);
                }
                $x = 'pool_water';
                $n = 'lock_xxxx';
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'withdrawal error']);
            }

            $service_charge=explode(',',config('config.withdraw_service_charge'.$c_id));

            //tx_money cz_adress pay_password verify
            $param=input('post.');
            $param['money']=$arr[$x];
			//Determine user quota and available quota
            //$param['service_charge']=$service_charge;
            $jypwd=base64_encode(md5($param['password'],true));
            $pay_pwd=Db::name('member')->where('id',$uid)->value('pay_password');
            if($param['tx_money']<$tx_beishu){
                return json(['code'=>0,'data'=>[],'msg'=>'The minimum withdrawal amount is: '.$tx_beishu]);
            }
            if(!preg_match("/^[1-9][0-9]*$/" ,$param['tx_money']/$tx_beishu)){
                return json(['code'=>0,'data'=>[],'msg'=>'The withdrawal amount must be a multiple of '.$tx_beishu.'']);
            }

            if($service_charge[0]==2){
                if($service_charge[1]>$param['tx_money']){
                    return json(['code'=>0,'data'=>[],'msg'=>'withdrawal amount must be greater than handling fee']);
                }
            }

            try {
                validate(WithdrawalValidate::class)->check($param);
            } catch (ValidateException $e) {
			// validation failed output error message
                return json(['code'=>0,'data'=>[],'msg'=>$e->getError()]);
            }
            $pay_password=Db::name('member')->where('id',$uid)->value('pay_password');
            if(empty($pay_password)){
                return json(['status'=>0,'data'=>[],'msg'=>'No transaction password is set']);
            }
            if($jypwd!=$pay_pwd){
                return json(['code'=>0,'data'=>[],'msg'=>'transaction password is incorrect']);
            }

            // Determine whether the verification code is right or wrong
            $mobile=Db::name('member')->where(array('id'=>$uid))->value('mobile');
            $verify_info = check_phone_verify($mobile,$param['verify'],'withDraw_');
            if(!$verify_info['code']){
                return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
            }
            if($service_charge[0]==1){
                $service_withdraw=round($param['tx_money']-($param['tx_money']*$service_charge[1]*0.01),2);
            }else{
                $service_withdraw=round($param['tx_money']-$service_charge[1],2);
            }

            $data=array(
                'uid'=>$uid,
                'pid'=>$n,
                'txnum'=>'WITH'.time() . str_pad(mt_rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                'money'=>$param['tx_money'],
                'handling_fee'=>$param['tx_money']-$service_withdraw,
                'actual'=>$service_withdraw,
                'status'=>1,
                'mark'=>'User withdrawal',
                'qianbao_url'=>$param['cz_address'],
                'created_at'=>time(),
                'again'=>2
            );
            $lock_money=Db::name('member')->where('id',$uid)->value($n);
            $money=floatval($param['money']);
            $tx_money=floatval($param['tx_money']);
            //  $service_charge=floatval($param['service_charge']);
            //      $last_money=$money-$tx_money-$service_charge;
				// start transaction
            Db::startTrans();
            try {
                $log = $this->log($lock_money,$uid,$x,$tx_money,$money,$data,$n,$c_id);
                if($log != true){
                    return json(['status'=>0,'data'=>[],'msg'=>'Withdrawal failed']);
                }
                // commit the transaction
                Db::commit();
                return json(['status'=>1,'data'=>[],'msg'=>'withdrawal successful']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['status'=>0,'data'=>[],'msg'=>'Withdrawal failed']);
            }
        }
        return json(['status'=>1,'data'=>['arr'=>$arr,'service_charge'=>$service_charge,'beishu'=>$tx_beishu],'msg'=>'']);
    }
    // add log
        public function log($lock_money,$uid,$x,$tx_money,$money,$data,$n,$c_id){
            if($c_id == 1){
                $data['currency'] = 'KRC';
                $id=Db::name('apply_withdraw')->insertGetId($data);
                // Deduct available balance plus frozen balance
                Member::Onefield($uid,$x,'down',$tx_money);
                MemberWalletLogModel::log($uid,$tx_money,$money,$money-$tx_money,2,'Withdrawal reduced KRC',$id);
                //add freeze
                Member::Onefield($uid,$n,'up',$tx_money);
                return true;
            }
            if($c_id == 2){
                $data['currency'] = 'USDT';
                $id=Db::name('apply_withdraw')->insertGetId($data);
                // Deduct available balance plus frozen balance
                Member::Onefield($uid,$x,'down',$tx_money);
                MemberWalletLogModel::log($uid,$tx_money,$money,$money-$tx_money,22,'Withdrawal to reduce USDT',$id);
                //add freeze
                Member::Onefield($uid,$n,'up',$tx_money);
                return true;
        }
        if($c_id == 3){
            $data['currency'] = 'SM';
            $id=Db::name('apply_withdraw')->insertGetId($data);
            // Deduct available balance plus frozen balance
            Member::Onefield($uid,$x,'down',$tx_money);
            MemberWalletLogModel::log($uid,$tx_money,$money,$money-$tx_money,42,'Withdrawal reduced SM'.$n,$id);
            //add freeze
            Member::Onefield($uid,$n,'up',$tx_money);
            return true;
        }
        return false;
    }
    //withdrawal record
    public function withdrawRecord()
    {
        $uid = $this->uid();
        $currency = input('currency','');
        $map[] = ['w.uid', '=', $uid];
        if($currency != '' && $currency){
            $map[] = ['w.currency', '=', $currency];
        }
        $page = input('page/d', 1);
        $limit = input('limit/d', 10);
        $order = input('order', 'created_at desc');
        $usdt=config('config.withdraw_service_charge2');
        $Withdraw = new Withdraw();
        $list = $Withdraw->getWithdrawByWhere($map, $page, $limit, $order);
        foreach ($list['list'] as $key =>&$value) {
            //$value['usdt']=round($value['actual']/$usdt,2);
            if($value['statuss']==1){
				$value['status']='Pending review';
            }
            if($value['statuss']==3){
                $value['status']='Rejected';
            }
            if($value['statuss']==4){
                $value['status']='Accounted to';
            }
        }
        return json(['code'=>1,'data'=>$list,'msg'=>'']);
    }



		//Send SMS/Email after login
    public function send_verify($check_type='mobile',$remarks='withDraw_'){
        $uid = $this->uid();
        $info = Db::name('member')->find($uid);
        $param =$info[$check_type];
        //get verification code
        $verify_info = get_phone_verify($param,$remarks);
        if(isset($verify_info) && time()<$verify_info['verify_send_time']+60){
            return json(['code'=>0,'data'=>[],'msg'=>'The verification code is sent too frequently']);
        }else{
            $verify = rand(100000,999999);
            if($check_type=='mobile'){
                $res = \app\common\service\Msg::send_sms(1,$info['country_code'].$info['mobile'],array('name'=>$info['country_code'].$info ['mobile'],'code'=>$verify));
            }elseif($check_type == 'email'){
                $res = \app\common\service\Msg::send_email(0,$info['email'],array('title'=>'email verification code title','content'=>'your verification code is ' . $verify . 'Please don't reveal the verification code to others'));
            }else{
                return json(['code'=>0,'data'=>[],'msg'=> 'Bad request interface']);
            }

            if($res['code']){
                $verify_info = [
                    'verify'  => $verify,
                    'verify_expire'  => time()+300,
                    'verify_send_time'  => time()
                ];
                set_phone_verify($param,$verify_info,$remarks);
                return json(['code'=>1,'data'=>[],'msg'=>'Sent successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
            }
        }
    }

    // balance log
    public function money_log_list($type = 0, $act = 0){
        /* 0 KRC 1USDT 2XXX 3 coupons 4 coupons 5 original shares */
        $uid=$this->uid();
        
        $page=input('page',1);
        $limit=input('limit',10);
        $map[] = ['uid','=',$uid];
        if($act>0){
            $map[] = ['type','=',$act];
        }
        $order=input('order','id desc');

        $beizhu = [];





        //KRC
        $beizhu[0]=[
			1=>'Deposit KRC',
            2=>'KRC withdrawal application',
            3=>'Convert USDT',
            4=>'USDT to KRC',
            5=>'KRC withdrawal refused',
            6=>'KRC withdrawal passed',
            7=>'agent dynamic reward',
            8=>'agent reward',
            9=>'Node pays dynamic rewards',
            10=>'Direct push VIP product reward',
            11=>'VIP commodity node reward under the umbrella',
            12=>'Pay rewards',
            13=>'Pay dynamic rewards on behalf of',
            14=>'node package reward',
            15=>'Buy voucher',
            16=>'Order payment',
            17=>'Order receipt',
            18=>'Pay on behalf of the order',
            19=>'Return to pay',
            27=>'Transfer to reduce KRC',
            28=>'Add KRC to transfer',
            130=>'Auction success',
            131=>'Auction Deduction Deposit',
            132=>'Auction return deposit',
            133=>'Auction repayment fee',
            134=>'Auction reward',
            135=>'Auction team reward',
            136=>'Auction red envelope reward',
            200=>'donation',
            201=>'Challenge task reward',
            202=>'Open experience membership',
            203=>'Direct push experience member reward',
            204=>'Experience team reward',
            205=>'Experience team level reward',
            206=>'Experience payment on behalf of rewards',
            208=>'Experience payment dynamic rewards',
            ];

        //USDT
        $beizhu[1]=[
		  21=>'Deposit USDT',
          22=>'USDT withdrawal application',
          23=>'Convert to KRC',
          24=>'KRC convert usdt',
          25=>'USDT withdrawal refused',
          26=>'USDT withdrawal passed',
        ];

        //SM
        $beizhu[2]=[
            41=>'recharge gift coins',
            42=>'Application for cash withdrawal of gift coins',
            43=>'Refused to withdraw the gift coins',
            44=>'Withdrawal of gift coins passed',
            51=>'Payment reward',
            52=>'Pay dynamic currency rewards on behalf of',
            53=>'Node pays dynamic currency rewards on behalf of',
            207=>'Experience token reward',
            209=>'Experience the payment of dynamic currency rewards',
        ];

        //代付券
        $beizhu[3]=[
            61=>'Buy coupon',
            62=>'Pay on behalf of using coupon',
            63=>'Purchase VIP products to give away vouchers',
        ];

        //coupon
        $beizhu[4]=[
            81=>'Order payment coupon',
            82=>'Order purchase to get a coupon',
        ];

        // original stock
        $beizhu[5]=[
            101=>'Purchase VIP products to get original shares',
            102=>'Original shares decreased',
        ];
        switch ($type) {
            case 0:
                //KRC
                $money=Db::name('member')->where('id',$uid)->value('money');
                $map[] = ['type','in','1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,27,28,130,131,132,133,134,135,136,200,201,202,203,204,205,206,208'];
                $list = Db::name('member_wallet_log')->where($map)->withAttr('number', function($value, $data) {
                    if($data['type']==2 || $data['type']==3 || $data['type']==6 || $data['type']==15 || $data['type']==16 || $data['type']==18 || $data['type']==131 || $data['type']==133 || $data['type']==200 || $data['type']==202|| $data['type']==27){
                        return '-'.$data['number'];
                    }else{
                        return $data['number'];
                    }
                })->page($page,$limit)->order($order)->select();
                break;
            case 1:
                //USDT
                $money=Db::name('member')->where('id',$uid)->value('pool_hatch');
                $map[] = ['type','in','21,22,23,24,25,26'];
                $list = Db::name('member_wallet_log')->where($map)->withAttr('number', function($value, $data) {
                    if($data['type']==23||$data['type']==26||$data['type']==22){
                        return '-'.$data['number'];
                    }else{
                        return $data['number'];
                    }
                })->page($page,$limit)->order($order)->select();
                break;
            case 2:
                //SM
                $money=Db::name('member')->where('id',$uid)->value('pool_water');
                $map[] = ['type','in','41,42,43,44,51,52,53,207,209'];
                $list = Db::name('member_wallet_log')->where($map)->withAttr('number', function($value, $data) {
                    if($data['type']==42 || $data['type']==44){
                        return '-'.$data['number'];
                    }else{
                        return $data['number'];
                    }
                })->page($page,$limit)->order($order)->select();
                break;
            case 3:
                //voucher
                $money=Db::name('member')->where('id',$uid)->value('pool_sale');
                $map[] = ['type','in','61,62,63'];
                $list = Db::name('member_wallet_log')->where($map)->withAttr('number', function($value, $data) {
                    if($data['type']==62){
                        return '-'.$data['number'];
                    }else{
                        return $data['number'];
                    }
                })->page($page,$limit)->order($order)->select();
                break;
            case 4:
                //coupon
                $money=Db::name('member')->where('id',$uid)->value('integral');
                $map[] = ['type','in','81,82'];
                $list = Db::name('member_wallet_log')->where($map)->withAttr('number', function($value, $data) {
                    if($data['type']==81){
                        return '-'.$data['number'];
                    }else{
                        return $data['number'];
                    }
                })->page($page,$limit)->order($order)->select();
                break;
            case 5:
                //original stock
                $money=Db::name('member')->where('id',$uid)->value('encourage');
                $map[] = ['type','in','101,102'];
                $list = Db::name('member_wallet_log')->where($map)->withAttr('number', function($value, $data) {
                    if($data['type']==102){
                        return '-'.$data['number'];
                    }else{
                        return $data['number'];
                    }
                })->page($page,$limit)->order($order)->select();
                break;
            default:
                $list = [];
                break;
        }
        

        return json(['status'=>1,'data'=>$list,'mingxi'=>$beizhu[$type],'money'=>$money,'msg'=>'']);
    }


    //Inventory pool list
    public function pool_water_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','in','62,68'],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                if($data['type']==68){
                    return '-'.$data['number'];
                }else{
                    return $data['number'];
                }
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['pool_water'],'msg'=>'']);
    }
    //List of hatching pools
    public function pool_hatch_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','in','63,69'],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                if($data['type']==69){
                    return '-'.$data['number'];
                }else{
                    return $data['number'];
                }
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['pool_hatch'],'msg'=>'']);
    }

    //List of sales pools
    public function pool_sale_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','in','61,67'],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                if($data['type']==67){
                    return '-'.$data['number'];
                }else{
                    return $data['number'];
                }
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['pool_sale'],'msg'=>'']);
    }
    //List of sales pools
    public function jm_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','=',8],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                return '-'.$data['number'];
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['pool_sale'],'msg'=>'']);
    }
    //List of consumption pools
    public function pool_consumption_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','in','60,66'],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                if($data['type']==66){
                    return '-'.$data['number'];
                }else{
                    return $data['number'];
                }
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['pool_consumption'],'msg'=>'']);
    }
    //List of Fund Pools
    public function encourage_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','in','65,71'],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                if($data['type']==71){
                    return '-'.$data['number'];
                }else{
                    return $data['number'];
                }
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['encourage'],'msg'=>'']);
    }
    //List of replay pools
    public function integral_list(){
        $uid=$this->uid();
        $member=Db::name('member')->where('id',$uid)->find();
        $page=input('page',1);
        $limit=input('limit',10);
        $order=input('order','create_time desc');
        $list = Db::name('member_wallet_log')
            ->where([['type','in','64,70'],['uid','=',$uid]])
            ->withAttr('number', function($value, $data) {
                if($data['type']==70){
                    return '-'.$data['number'];
                }else{
                    return $data['number'];
                }
            })->page($page,$limit)
            ->order($order)
            ->select();
        return json(['status'=>1,'data'=>$list,'money'=>$member['integral'],'msg'=>'']);
    }

    public function change_krc()
    {
        $uid=$this->uid();
        $kou=config('config.krc_change');
        $member=Db::name('member')->where('id',$uid)->find();
        if(request()->isPost()){
            $param=input('post.');//uid //money //password
            if(empty($param['uid'])){
				return json(['code'=>0,'data'=>[],'msg'=>'Current ID cannot be empty']);
            }
            if(empty($param['money'])){
                return json(['code'=>0,'data'=>[],'msg'=>'The current transfer amount cannot be empty']);
            }
            if(empty($param['password'])){
                return json(['code'=>0,'data'=>[],'msg'=>'current transaction cannot be empty']);
            }
            if(empty($member['pay_password'])){
                return json(['code'=>0,'data'=>[],'msg'=>'The user has not set a transaction password']);
            }
            if($param['money']<=0){
                return json(['code'=>0,'data'=>[],'msg'=>'The current transfer amount cannot be less than or equal to 0']);
            }
            if($param['money']<=$kou){
                return json(['code'=>0,'data'=>[],'msg'=>'The current transfer amount cannot be less than the handling fee amount']);
            }
            $members=Db::name('member')->where('id',$param['uid'])->find();
            if(empty($members)){
                return json(['code'=>0,'data'=>[],'msg'=>'current user does not exist']);
            }
            
            $jypwd=base64_encode(md5($param['password'],true));
            if($jypwd!==$member['pay_password']){
                return json(['code'=>0,'data'=>[],'msg'=>'transaction password error']);
            }
            $add_money=$param['money']-$kou;
            // start transaction
            Db::startTrans();
            try {
                //transfer minus KRC
                Member::Onefield($uid,'money','down',$param['money']);
                MemberWalletLogModel::log($uid,$param['money'],$member['money'],$member['money']-$param['money'],27,'Transfer to reduce KRC',$uid );
                //transfer plus KRC
                Member::Onefield($uid,'money','up',$add_money);
                MemberWalletLogModel::log($members['id'],$add_money,$members['money'],$members['money']-$add_money,28,'Transfer to increase KRC',$members['id'] );
                // commit the transaction
                Db::commit();
                return json(['code'=>1,'data'=>[],'msg'=>'transfer successful']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['code'=>0,'data'=>[],'msg'=>'transfer failed']);
            }
        }
        return json(['code'=>1,'data'=>['money'=>$member['money'],'krc_change'=>$kou],'msg'=>'']);
    }
}