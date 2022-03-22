<?php

namespace app\common\service;
use app\common\model\IntegralLog;
use app\common\model\Member;
use app\common\model\MemberMsg;
use app\common\model\MemberWalletLogModel;
use app\common\model\MoneyLog;
use app\common\model\PayLog;
use think\Model;
use think\facade\Db;
class Users extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->Member=new Member();
        $this->Msg=new MemberMsg();
    }
    //change Password
    public function update_pwd($uid,$old_pwd,$new_pwd){
        $map = array('uid'=>$uid,'password'=>md5(md5($old_pwd) . config('auth_key')));
        return $this->Member->save(['password'=>md5(md5($new_pwd) . config('auth_key'))],$map);
    }

    //retrieve password
    public function edit_pwd($account,$pwd){
        $map['account|mobile|email'] =$account;
        return $this->Member->save(['password'=>md5(md5($pwd) . config('auth_key'))],$map);
    }

    //Modify login password
    public function edit_pwds($uid,$pwd){
        $map['id'] =$uid;
        return $this->Member->where($map)->save(['password'=>md5(md5($pwd) . config('app.auth_key'))]);
    }

    //Modify transaction password
    public function jiaoyiedit_pwd($uid,$pwd){
        $map['id'] =$uid;
        return $this->Member->where($map)->save(['pay_password'=>base64_encode(md5($pwd,true))]);
    }
    //Determine whether the user account is occupied
    public function check_account($account){
        $map['account|mobile|email'] =$account;
        $map['closed'] = 0;
        return  $this->Member->where($map)->find();
    }
    //Determine whether the user account is occupied
    public function check_mobile($mobile){
        $map['mobile'] =$mobile;
        $map['closed'] = 0;
        return  $this->Member->where($map)->find();
    }
    //Determine if the mailbox is occupied
    public function check_email($mobile){
        $map['email'] =$mobile;
        return  $this->Member->where($map)->find();
    }
    //User registration
    //   public function reg($account,$password){
    // $user_num = $this->Member->count();
    //       $param = [
    //            'account' => $account,
    //            'nickname' => 'tourists'.$user_num+1,
    //            'password' => md5(md5($password) . config('auth_key')),
    // 		'group_id' => 2
    //        ];
    //       return $this->Member->save($param);
    //   }
    //Sign Up
    public function reg($account,$password,$jy_password,$tid,$reaccount,$phone,$country_code,$check_type,$tjuser=''){
        $yma = $this->generateRandomString();
        $param = [
            'tma' => $yma, //invitation code
            'account' =>$phone, //account
            'nickname'=>$account,//nickname
            'country_code'=>$country_code, //Country phone number code
            'password'=>md5(md5($password).config('app.auth_key')),
            'pay_password'=>base64_encode(md5($jy_password,true)),
            'referid' => $tid, //referrer id
            'reusername' => $reaccount, //referrer account
            'account_type' =>0, //Register member
            //'token' =>$this->setAppLoginToken($phone)
            // 'password' => base64_encode(md5('111111',true)),
        ];

        if($tjuser){
            $param['tjuser'] = $tjuser.$tid.'-';
        }else{
            $param['tjuser'] = '-'.$tid.'-';
        }

        if($check_type=='mobile'){
            $param['mobile'] = $phone;
        }else{
            $param['email'] = $phone;
        }
        return $this->Member->save($param);
    }

    //Generate an invitation code
    public function generateRandomString($length = 6) {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $members = Db::name('member')->where(['tma'=>$randomString])->find();
        if($members){
            $this->generateRandomString();
        }else{
            return $randomString;
        }

    }

    //Modify user basic information
    public function update_info($uid,$data){
        return $this->Member->update($data,['id'=>$uid]);
    }

    // public function login($account,$password){
    // $map['closed']=0;
    // $map['status']=1;
    // //TODO time relationship is not judged separately in this place
    //     $map['account|mobile|email'] =$account;
    //     $map['password']=md5(md5($password) . config('auth_key'));
    //     return  $this->Member->where($map)->find();
    // }
    //password login
    public function login($account,$password){
        $password=md5(md5($password) . config('app.auth_key'));
        $mobile = $this->Member->where(['mobile'=>$account,'password'=>$password])->find();
        if($mobile){
            return $mobile;
        }else{
            $email = $this->Member->where(['email'=>$account,'password'=>$password])->find();
            if($email){
                return $email;
            }else{
                return $this->Member->where(['account'=>$account,'password'=>$password])->find();
            }

        }
        //return  $this->Member->where(['mobile'=>$account,'password'=>$password])->whereOr(['email'=>$account,'password'=>$password])->find();
    }

    //login by phone number
    public function phone_login($account){
        $map['mobile'] = $account;
        return $this->Member->where($map)->find();
    }

    //login by phone number
    public function email_login($account){
        $map['email'] =$account;
        return $this->Member->where($map)->find();
    }

    //Auto login user login
    public function autoLogin($user){
        /* Update login information */
        $data = array(
            'id' => $user['id'],
            'login_num' => array('exp', 'login_num+1'),
            'last_login_ip' => get_client_ip(0),
        );
        $this->Member->update($data);
        /* record login SESSION and COOKIES */
        $auth = array(
            'uid' => $user['id'],
            'nickname' => $user['nickname'],
            'last_login_time' => $user['update_time'],
        );
        session('user_auth', $auth);
        session('user_auth_sign', data_auth_sign($auth));

    }

    /**
     * description  Set the uniqueness of the login token
     * @param string $to cellphone number
     * @return string
     */
    public static function setAppLoginToken ($to = "")
    {
        $string = md5(uniqid(md5(microtime(true)), true));
        $string = sha1($string . $to);
        return $string;
    }

    // user exits
    public function logout(){
        if (is_login()) {
            session('user_auth', null);
            session('user_auth_sign', null);
            return array('status'=>1,'info'=>'exit successfully');
        }
        return array('status'=>0,'info'=>'user not logged in');
    }
    //getUserInfo
    public function getUserInfo($uid){
        $uesr =$this->Member->field('id,shop_id,account,nickname,referid,dl_level,dls_level,reusername,sex,age,jifen,group_id,head_img,integral,money,pool_consumption,pool_sale,pool_hatch,encourage ,country_code,mobile,email,create_time,status,closed,tma,tma_qrcode,pm_level')->find($uid);
        if(!$uesr){
            return array('status'=>-1,'msg'=>'user does not exist');
        }
        //TODO can't be calculated by points here, the points are still consumed and only the experience value can be used to calculate the total consumption
        // membership level
        $uesr->grade=$this->getUserLevel($uesr->integral);
        // Message Center
        $uesr->unread_msg=$this->getUserUnreadMsg($uid);
        //TODO user avatar URL processing
        //Order status processing
        //Pending payment Unpaid Order pending or confirmed
        $uesr->unpay_order_num = Db::name('order')->where([['user_id', '=', $uid], ['pay_status','=', 0], ['order_status' , 'in',[0, 1]], ['shipping_status', '=', 0]])->count();
        //to be received, paid, order to be confirmed or confirmed
        $uesr->unconfirm_order_num = Db::name('order')->where([['user_id','=', $uid], ['pay_status','in', [1,4]], [ 'order_status', '=', 1], ['shipping_status', '=', 1]])->count();

        //To be evaluated, paid, received
        $uesr->unpingjia_order_num = Db::name('order')->where([['user_id','=', $uid], ['pay_status','in', [1,4]], ['order_status','=', 2],['shipping_status','=', 1]])->count();
        unset($uesr['password']);

        return ['code'=>1,'data'=>$uesr];

    }
    //Get user level based on experience
    public function getUserLevel($dl_level,$ty_level=0){
        switch ($dl_level) {
            case 1:
                $name = 'SS';
                break;
            case 2:
                $name = 'SSS';
                break;
            case 3:
                $name = 'SSSS';
                break;
            default:
                if($ty_level>0){
                    $name = 'E';
                }else{
                    $name = 'S';
                }
                break;
        }
        return ['code'=>1,'img'=>"",'name'=>$name];
        // //Get current user registration according to points
        // $map[]=['min','<=',$integral];
        // $map[]=['max','>=',$integral];
        // $row=Db::name('member_grade')->where($map)->find();
        // if($row){
        // 	return ['code'=>1,'img'=>"{$row['portrait']}?imageMogr2/thumbnail/128x128!",'name'=>$row['name']];
        // }else{
        // 	return ['code'=>1,'img'=>"http://resources-user-image.vxue360.com/b5b04ea1e7122f4d1f3b1898cf5ba60e.png?imageMogr2/thumbnail/128x128!",'name'=>'Ordinary member'];
        // }
    }
   //Number of unread messages
    public function getUserUnreadMsg($uid){
        return $this->Msg->where(['receive_uid'=>$uid,'status'=>0])->count();
    }

    //add money to the user
    public function userIncMoney($uid,$money){
        return $this->Member->incMoney($uid,$money);
    }

    //Reduce money to user
    public function userDecMoney($uid,$money){
        return $this->Member->decMoney($uid,$money);
    }

    //add points to the user
    public function userIncIntegral ($uid, $integral)
    {
        return $this->Member->incIntegral($uid, $integral);
    }
    // deduct points for the user
    public function userDecIntegral ($uid, $integral)
    {
        return $this->Member->decIntegral($uid, $integral);

    }
    // get user message
    public function get_msg($uid,$page,$pagesize){
        return $this->Msg->msg_list($uid,$page,$pagesize);
    }
    // view a message
    public function read_msg($uid,$id){
        //get message content
        $info = $this->Msg->where("id",$id)->find();
        $info = $info->toArray();
        if($info['status'] == 0){
            // mark the message as read
            $this->Msg->save(['status'=>1],['receive_uid'=>$uid,'id'=>$id]);
        }
        $info['content'] = unserialize($info['content']);
        return $info;

    }
    //delete a message
    public function del_msg($uid,$id){
        return $this->Msg->where(['receive_uid'=>$uid,'id'=>$id])->delete();
    }


    public function userPayLog($uid,$map=['status'=>1]){
        $map['uid']=$uid;
        $PayLog=new PayLog();
        $res =$PayLog->order('id desc')->field('money,pay_type,update_time')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'No record'];
        }
    }
    public function userMoneyLog($uid,$map=['status'=>1]){
        $map['uid']=$uid;
        $PayLog=new MoneyLog();
        $res =$PayLog->order('create_time desc')->field('out_trade_no,money,act,remark,update_time')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'No record'];
        }
    }

    public function userIntegralLog($uid,$map=['status'=>1]){
        $map['uid']=$uid;
        $IntegralLog=new IntegralLog();
        $res =$IntegralLog->order('id desc')->field('id,num,act,remark,update_time')->where($map)->select();
        if($res){
            return ['code'=>1,'data'=>$res,'msg'=>''];
        }else{
            return ['code'=>0,'data'=>'','msg'=>'No record'];
        }
    }

    public function recharge($uid,$money){
        $MoneyLog=new MoneyLog();
        $data = $MoneyLog->operate($uid,$money,1,0,'User recharge');
        if($data){
            return ['code'=>1,'data'=>$data,'msg'=>''];
        }
        return ['code'=>0,'data'=>'','msg'=>'Failed to generate recharge order'];
    }


    /*--------------------------------------------Wallet docking start--------------- --------------*/
    //Wallet interface request method
    public function qbapiPost($url,$para=[]){
        $times = time();
        $sign = md5($url.'/hzzjftyoPdeeHiV44zj7rKyJCgm2Z13f8/'.$times);
        $postUrl ='http://47.244.207.216:7788'.$url;
        $curlPost = array('timestamp:'.$times,'token:'.$sign);
        $curlPost[]='number:1';
        //initialization
        $curl = curl_init();
        //Set the fetched url
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        / / Set the information of the header file as the data stream output
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //Set the obtained information to be returned in the form of a file stream, rather than output directly.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlPost);

        //Declare to use POST method to send
        curl_setopt($curl, CURLOPT_POST, 1);
        //what data to send
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($para));
        //Excuting an order
        $data = curl_exec($curl);
        //close the URL request
        curl_close($curl);
        //display the obtained data
        if ($data === false){
            $data = [];
            $data = json_encode($data);
        }

        $data = json_decode($data, true);
        if (!$data){
            $data = [];
        }
        return $data;
    }
    public function qbapiGet($url,$para=[]){
        $par = http_build_query($para);
        $par=$par?'?'.$par:'';
        $times = time();
        $sign = md5($url.'/hzzjftyoPdeeHiV44zj7rKyJCgm2Z13f8/'.$times);
        $postUrl = 'http://47.244.207.216:7788'.$url;
        $curlPost = array('timestamp:'.$times,'token:'.$sign);
        $curlPost[]='number:1';
        //initialization
        $curl = curl_init();
        //Set the fetched url
        curl_setopt($curl, CURLOPT_URL, $postUrl);
        / / Set the information of the header file as the data stream output
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //Set the obtained information to be returned in the form of a file stream, rather than output directly.
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlPost);
        //Excuting an order
        $data = curl_exec($curl);
        //close the URL request
        curl_close($curl);
        //display the obtained data
        if ($data === false){
            $data = [];
            $data = json_encode($data);
        }
        $data = json_decode($data, true);
        if (!$data){
            $data = [];
        }
        return $data;
    }


    function signedRequest($coin,$url, $params = [], $method = "POST") {
        $timestamp = time()*1000;
        $url = 'http://47.89.27.111:10013/'.$url;
        $Authorization = base64_encode(strtoupper($coin).':'.strtolower($coin).'password');
        $opt = [
            "http" => [
                "method" => $method,
                "ignore_errors" => true,
                "header" => "Authorization:Basic {$Authorization}\r\nJson-Rpc-Tonce: {$timestamp}\r\nContent-Type: application/x-www-form-urlencoded\r\n"
            ]
        ];
        $postdata = json_encode($params);
        $opt['http']['content'] = $postdata;
        $context = stream_context_create($opt);
        return json_decode(file_get_contents($url, false, $context),true);
    }


    /*------------------------------Wallet docking end-----------------------------*/





    /*------------------------------Platform reward start-----------------------------*/

    /*
     * Agent level judgment
     * uid start level UID
     * isty 0 only judges the agent 1 judges both the agent and the experience 2 only judges the experience
     */
    public function dls_level($uid,$isty=0){
        $dengji = array(1=>explode(',',config('config.daili_v1')),2=>explode(',',config('config.daili_v2')),3=>explode(',',config('config.daili_v3')),4=>explode(',',config('config.daili_v4')));
        $member = Db::name('member')->where(['id'=>$uid])->find();
        $tydengji = array('1'=>explode(',',config('config.krc_tydj2')),'2'=>explode(',',config('config.krc_tydj3')),'3'=>explode(',',config('config.krc_tydj4')));
        if($member){
            $ttuid = $member['id'];
            while ($ttuid){
                $ttmember = Db::name('member')->where(['id'=>$ttuid])->find();
                if(!$ttmember){
                    break;
                }

                //Experience membership level judgment
                $ty_level = $ttmember['ty_level'];
                if($ttmember['ty_level']>0 && $isty>=1){
                    foreach ($tydengji as $tykey=>$tyvalue){
                        if($ty_level==$tykey){
                            //Upgrade to primary
                            if($tykey==1) {
                                // direct push number
                                $znum = Db::name('member')->where([['referid', '=', $ttuid], ['ty_level', '>', 0]])->count();
                                // team size
                                $tnum = Db::name('member')->where([['tjuser', 'like', '%-'.$ttuid.'-%'], ['ty_level', '>', 0]])->count();
                                if($znum>=$tyvalue[0]&& $tnum>=$tyvalue[1]){
                                    $ty_level = 2;
                                }else{
                                    break;
                                }
                            }else{
                                //Intermediate and advanced judgment

                                //Direct push number
                                $znum = Db::name('member')->where([['referid', '=', $ttuid], ['ty_level', '>=', $tykey]])->count();
                                $znums = 0;
                                if($znum<$tyvalue[0]){
                                    $xmember = Db::name('member')->field('id')->where([['referid', '=', $ttuid], ['ty_level', '<', $tykey]])->select()->toArray();
                                    foreach ($xmember as $vvvv){
                                        $xtun = Db::name('member')->where([['tjuser', 'like', '%-'.$vvvv['id'].'-%'], ['ty_level', '>=', $tykey]])->count();
                                        if($xtun>0){
                                            $znums = $znums+1;
                                        }
                                    }
                                }
                                //Team size
                                $tnum = Db::name('member')->where([['tjuser', 'like', '%-'.$ttuid.'-%'], ['ty_level', '>=', $tykey]])->count();

                                if($znum>=$tyvalue[0] && $tnum>=$tyvalue[1]){
                                    $ty_level = $tykey+1;
                                }else{
                                    break;
                                }
                            }
                        }
                    }
                    //Update experience membership level
                    Db::name('member')->where(['id'=>$ttmember['id']])->update(['ty_level'=>$ty_level]);
                }


                //Agent judgment
                if($ttmember['dl_level']>=2 && $isty<2){
                    $dj = $ttmember['dls_level'];
                    foreach ($dengji as $key=>$value){
                        if($dj<$key){
                            $map = [];
                            if($dj==0){
                                $map[] = ['dl_level','>',0];
                            }else{
                                $map[] = ['dls_level','>=',$value[0]];
                            }
                            $map[] = ['referid','=',$ttmember['id']];
                            $conts = Db::name('member')->where($map)->count();

                            if($conts>=$value[1]){
                                $dj = $key;
                            }else{
                                break;
                            }
                        }
                    }
                    Db::name('member')->where(['id'=>$ttmember['id']])->update(['dls_level'=>$dj]);
                }
                $ttuid = $ttmember['referid'];

            }

        }

    }

      /**
    * Level 10 reward +1
     *
     * @param [type] $tjuser
     * @param [type] $field
     * @param [type] $type '1 recommendation 2 payment on behalf of 3 direct purchase 4 auction',
     * @param [type] $uid user id,
     * @return void
     */
    public function get_redbag_num($tjuser,$field='',$type=1,$uid=0)
    {
        //User's level 10 recipients receive +1
        $users_info=$this->get_user10($tjuser);
        foreach ($users_info as $key => $value) {
            // Db::name('member')->where([['id','=',$value['id']],['status','=',1]])->inc($field,1)->update();
            Db::name('get_record')->save(['uid'=>$value['id'],'type'=>$type,'act'=>$uid,'create_time'=>time(),'update_time'=>time()]);
        }
        return true;
    }

    /**
     * Level 10 of the current user
     *
     * @param [type] $tjuser
     * @return void
     */
    public function get_user10($tjuser)
    {
        $str=trim($tjuser, "-");
        $arr=explode('-',$str);
        if(count($arr)){
            $users_id=implode(',',$arr);
        }else{
            $users_id=implode(',',array_slice($arr,(count($arr)-10),10));
        }
        $users_info=Db::name('member')->where([['id','in',$users_id],['status','=',1]])->column('id,is_set_type');
        return $users_info;
    }
  
    /**
    * Issue rewards to current users
     *
     * @param integer $type '1 recommendation 2 payment on behalf of 3 direct purchase 4 auction',
     * @param integer $is_set_type '2 user members 1 customer service member',
     * @return void
     */
    public function give_redbag($is_set_type,$type=3)
    {
            // Reward for the tenth level
            $nums=rand(1,100);

            $rate_info=$this->get_redbag_msg($is_set_type,$type);
            if(!$rate_info){
                return json(['status' => 0, 'msg' => 'The red envelope is not opened']);
            }
            // $money=0;
            foreach ($rate_info as $key => $value) {
                if($nums>=$value['min_rate']&&$nums<=$value['max_rate']){
                    $money=rand($value['min_number'],$value['max_number']);
                    if(empty($money)){
                        return $value['min_number'];
                    }
                    return $money;
                    break;
                }
            }
    }
       /**
     * Red envelope probability information function
     *
     * @param integer $type 1 user 2 customer service
     * @param integer $act 1 referral 2 payment on behalf of 3 direct purchase 4 auction
     * @return void
     */
    public function get_redbag_msg($type=1,$act=1)
    {
        // $zongg=Db::name('auction_redbag')->where([['status','=',2],['type','=',$type]])->sum('rate');
        $list=Db::name('auction_redbag')->where([['status','=',2],['type','=',$type],['act','=',$act]])->select()->toArray();
        foreach ($list as $key => &$value) {
            if($key==0){
                $value['min_rate']=1;
                $value['max_rate']=$value['rate'];
            }else{
                $value['min_rate']=$list[$key-1]['max_rate']+1;
                $value['max_rate']=$list[$key-1]['max_rate']+$list[$key]['rate'];
            }
        }
        return $list;
    }
    /*
     * VIP merchandise purchase
     * uid buy member uid
     * Purchase VIP product type 1 Promoter Ambassador 2 Partner
     */
    public function vip_goods($uid,$goodstype){
        // commodity price
        $tuiguan = config('config.jl_goodsprice'.$goodstype);
        //member information
        $member = Db::name('member')->where(['id'=>$uid])->find();
        if($member){
            //Member upgrade and reward
            $ysg = config('config.jl_yuanshigu'.$goodstype);//Original shares
            $dfq = config('config.jl_daifuquan'.$goodstype);//Voucher

            //update membership level
            if($member['ty_level']>0){
                $ty_level = $member['ty_level'];
                $isty = 0;//The experience member has not changed
            }else{
                $ty_level = 1;
                $isty = 1;//The experience membership level has changed
            }
            Db::name('member')->where(['id'=>$uid])->update(['dl_level'=>$goodstype,'ty_level'=>$ty_level,'is_mian'=>0]);

            //update agent level
            $this->dls_level($uid,$isty);
            //The number of times the current user has received red packets at level 10
            $this->get_redbag_num($member['tjuser'],'tj_rednum',1);
            if($dfq>0) {
                //change the voucher
                Member::Onefield($uid, 'pool_sale', 'up', $dfq);
                //add log
                MemberWalletLogModel::log($uid, $dfq, $member['pool_sale'], $member['pool_sale'] + $dfq, 63, 'Purchase VIP merchandise to receive a coupon', $goodstype);
            }
            if($ysg>0) {
                //change the original stock
                Member::Onefield($uid, 'encourage', 'up', $ysg);
                //add log
                MemberWalletLogModel::log($uid, $ysg, $member['encourage'], $member['encourage'] + $ysg, 101, 'Purchase VIP goods to get original shares', $goodstype);
            }

            //reward superior
            if($member['referid']){
                $tmember = Db::name('member')->where(['id'=>$member['referid']])->find();
                if($tmember){
                    //Directly push VIP product rewards
                    if($tmember['dl_level']>0 && $tmember['status']==1){
                        //Determine whether it is a node member The node is rewarded with partner rebates
                        if($tmember['dl_level']==3){
                            $levels = 2;
                        }else{
                            $levels = $tmember['dl_level'];
                        }
                        // Judge the level of burn
                        if($goodstype==2 && $levels==1){
                            $tuiguand = config('config.jl_goodsprice1');
                            $vipmoeny = round($tuiguand*config('config.jl_shengji'.$levels)*0.01,4);
                        }else{
                            $vipmoeny = round($tuiguan*config('config.jl_shengji'.$levels)*0.01,4);
                        }

                        if($vipmoeny>0) {
                           //change KRC
                            Member::Onefield($tmember['id'], 'money', 'up', $vipmoeny);
                            //add log
                            MemberWalletLogModel::log($tmember['id'], $vipmoeny, $tmember['money'], $tmember['money'] + $vipmoeny, 10, 'Direct push VIP product reward', $member['id ']);
                        }
                    }

                    //VIP commodity node rewards under the umbrella
                    $tuid = $member['referid'];
                    $jiedian = 0; //whether the node is rewarded
                    $cengji = 1;//Current reward level
                    while ($tuid){
                        $memberinfo = Db::name('member')->where(['id'=>$tuid])->find();
                        if(!$memberinfo){
                            break;
                        }

                        //node reward
                        if($memberinfo['dl_level']==3 && $memberinfo['status']==1 && $jiedian==0){
                            if($memberinfo['jlzhitui']>0) {
                                $jvipmoeny = round($tuiguan * $memberinfo['jlzhitui']*0.01, 4);
                                if($jvipmoeny>0) {
                                    //change KRC
                                    Member::Onefield($memberinfo['id'], 'money', 'up', $jvipmoeny);
                                    //add log
                                    MemberWalletLogModel::log($memberinfo['id'], $jvipmoeny, $memberinfo['money'], $memberinfo['money'] + $jvipmoeny, 11, 'VIP commodity node rewards under the umbrella', $member['id']);
                                }
                            }
                            $jiedian = 1;
                            // break;
                        }


                        //Agent reward
                        $xjiangli = explode(',',config('config.daili_xjl'));
                        $dengji = array(1=>explode(',',config('config.daili_v1')),2=>explode(',',config('config.daili_v2')),3=>explode(', ',config('config.daili_v3')),4=>explode(',',config('config.daili_v4')));

                        if($memberinfo['dls_level']>0 && count($xjiangli)>=$cengji){
                            //reward level
                            $cj = $dengji[$memberinfo['dls_level']][2];
                            if($cj>=$cengji){
                                $cjj = $cengji-1;
                                $jlmoney = round($tuiguan*0.01*$xjiangli[$cjj],4);
                                if($jlmoney>0){
                                    //change KRC
                                    Member::Onefield($memberinfo['id'], 'money', 'up', $jlmoney);
                                    //add log
                                    MemberWalletLogModel::log($memberinfo['id'], $jlmoney, $memberinfo['money'], $memberinfo['money'] + $jlmoney, 8, 'Agency Rewards', $member['id']);
                                }
                            }
                        }

                        $tuid = $memberinfo['referid'];
                        $cengji = $cengji + 1;
                        if($jiedian==1 && count($xjiangli)>=$cengji){
                            break;
                        }

                    }

                }

            }

        }

    }


    /*
     * On behalf of the purchase of goods
     * $duid payer uid
     * $uid purchaser uid
     * $money total order amount
     * $orderid order id
     * $song 1 is a free gift order
     */
    public function dai_goods($duid,$uid,$money){
        //pay member
        $member = Db::name('member')->where(['id'=>$duid])->find();
         //The number of times the current user has received red packets at level 10
         $this->get_redbag_num($member['tjuser'],'df_rednum',2);
        // Pay the reward
        if($member && $member['status']==1 && $member['dl_level']>0){
            // Pay the reward
            $df_money = round(config('config.jl_daifu'.$member['dl_level'])*$money*0.01,4);
            if($df_money>0) {
                //Change the payment reward KRC
                Member::Onefield($member['id'], 'money', 'up', $df_money);
                //add log
                MemberWalletLogModel::log($member['id'], $df_money, $member['money'], $member['money'] + $df_money, 12, 'Payment of rewards', $uid);
            }

           //Pay the gift coins
            $df_bi = round(config('config.jl_daifusong'.$member['dl_level'])*$money*0.01,4);
            if($df_bi>0) {
                //Change the reward coin for payment on behalf of others
                Member::Onefield($member['id'], 'pool_water', 'up', $df_bi);
                //add log
                MemberWalletLogModel::log($member['id'], $df_bi, $member['pool_water'], $member['pool_water'] + $df_bi, 51, 'Token reward', $uid);
            }

        }else{

            //Experience member judgment

            // Pay the reward
            $df_money = round(config('config.krc_dfjiang')*$money*0.01,4);
            if($df_money) {
                //Change the payment reward KRC
                Member::Onefield($member['id'], 'money', 'up', $df_money);
                //add log
                MemberWalletLogModel::log($member['id'], $df_money, $member['money'], $member['money'] + $df_money, 206, 'Experience payment on behalf of the reward', $uid);
            }

            //Pay the gift coins
            $df_bi = round(config('config.krc_bjiang')*$money*0.01,4);
            if($df_bi) {
                //Change the reward coin for payment on behalf of others
                Member::Onefield($member['id'], 'pool_water', 'up', $df_bi);
                //add log
                MemberWalletLogModel::log($member['id'], $df_bi, $member['pool_water'], $member['pool_water'] + $df_bi, 207, 'Experience token reward', $uid);
            }

        }

        //Pay dynamic rewards
        if($member && $member['referid']){
            $ttmember = Db::name('member')->where(['id'=>$member['referid']])->find();
            if($ttmember && $ttmember['dl_level']>0 && $ttmember['status']==1){
                if($ttmember['dl_level']==3){
                    $dlevel = 2;
                }else{
                    $dlevel = $ttmember['dl_level'];
                }

                //Pay dynamic rewards
                $ddf_money = round(config('config.jl_dongtai'.$dlevel)*$money*0.01,4);
                if($ddf_money>0) {
                    //Change the payment reward KRC
                    Member::Onefield($ttmember['id'], 'money', 'up', $ddf_money);
                    //add log
                    MemberWalletLogModel::log($ttmember['id'], $ddf_money, $ttmember['money'], $ttmember['money'] + $ddf_money, 13, 'Pay dynamic rewards', $uid);
                }

                //On behalf of the payment dynamic gift coins
                $ddf_bi = round(config('config.jl_dongtaibi'.$dlevel)*$money*0.01,4);
                if($ddf_bi>0) {
                    //Change the reward coin for payment on behalf of others
                    Member::Onefield($ttmember['id'], 'pool_water', 'up', $ddf_bi);
                    //add log
                    MemberWalletLogModel::log($ttmember['id'], $ddf_bi, $ttmember['pool_water'], $ttmember['pool_water'] + $ddf_bi, 52, 'Pay dynamic currency rewards', $uid);
                }

            }else{

                if($member['dl_level']<=0 && $ttmember && $ttmember['ty_level']>0 && $ttmember['status']==1){

                    //Pay dynamic rewards
                    $ddf_money = round(config('config.krc_dtdfu')*$money*0.01,4);
                    if($ddf_money>0) {
                        //Change the payment reward KRC
                        Member::Onefield($ttmember['id'], 'money', 'up', $ddf_money);
                        //add log
                        MemberWalletLogModel::log($ttmember['id'], $ddf_money, $ttmember['money'], $ttmember['money'] + $ddf_money, 208, 'Experience the dynamic rewards of payment on behalf of others', $uid);
                    }

                    //On behalf of the payment dynamic gift coins
                    $ddf_bi = round(config('config.krc_tybjiang')*$money*0.01,4);
                    if($ddf_bi>0) {
                        //Change the reward coin for payment on behalf of others
                        Member::Onefield($ttmember['id'], 'pool_water', 'up', $ddf_bi);
                        //add log
                        MemberWalletLogModel::log($ttmember['id'], $ddf_bi, $ttmember['pool_water'], $ttmember['pool_water'] + $ddf_bi, 209, 'Experience payment dynamic currency reward', $uid);
                    }

                }else{
                    //When the member level is greater than 0 and the superior level is less than 0, burns
                    //No reward for other states

                }



            }
        }


       //Pay dynamic node rewards
        $tuid = $member['referid'];
        $jiedian = 0; //whether the node is rewarded
        $cengji = 1;//Current reward level
        while ($tuid){
            $memberinfo = Db::name('member')->where(['id'=>$tuid])->find();
            if(!$memberinfo){
                break;
            }

            if($memberinfo['dl_level']==3 && $memberinfo['status']==1 && $jiedian==0){
                if($memberinfo['jldong']>0) {
                    $jvipmoeny = round($money * $memberinfo['jldong']*0.01, 4);
                    if($jvipmoeny>0) {
                        //change KRC
                        Member::Onefield($memberinfo['id'], 'money', 'up', $jvipmoeny);
                        //add log
                        MemberWalletLogModel::log($memberinfo['id'], $jvipmoeny, $memberinfo['money'], $memberinfo['money'] + $jvipmoeny, 9, 'Node pays dynamic rewards', $uid);
                    }
                }

                if($memberinfo['jldongb']>0) {
                    $cvipmoeny = round($money * $memberinfo['jldongb']*0.01, 4);
                    if($cvipmoeny>0) {
                       //change KRC
                        Member::Onefield($memberinfo['id'], 'pool_water', 'up', $cvipmoeny);
                        //add log
                        MemberWalletLogModel::log($memberinfo['id'], $cvipmoeny, $memberinfo['pool_water'], $memberinfo['pool_water'] + $cvipmoeny, 53, 'Node pays dynamic currency rewards', $uid);
                    }
                }
                $jiedian==1;
                //break;
            }

            //Agent reward
            $xjiangli = explode(',',config('config.daili_djl'));
            $dengji = array(1=>explode(',',config('config.daili_v1')),2=>explode(',',config('config.daili_v2')),3=>explode(', ',config('config.daili_v3')),4=>explode(',',config('config.daili_v4')));

            if($memberinfo['dls_level']>0 && count($xjiangli)>=$cengji){
                //reward level
                $cj = $dengji[$memberinfo['dls_level']][2];
                if($cj>=$cengji){
                    $cjj = $cengji-1;
                    $jlmoney = round($money*0.01*$xjiangli[$cjj],4);
                    if($jlmoney>0){
                        //change KRC
                        Member::Onefield($memberinfo['id'], 'money', 'up', $jlmoney);
                        //add log
                        MemberWalletLogModel::log($memberinfo['id'], $jlmoney, $memberinfo['money'], $memberinfo['money'] + $jlmoney, 7, 'Agent dynamic reward', $member['id' ]);
                    }
                }
            }

            $tuid = $memberinfo['referid'];
            $cengji = $cengji+1;
            if($jiedian==1 && count($xjiangli)>=$cengji){
                break;
            }
        }

    }





    /*
     * Pay for gift purchases
     * $duid payer uid
     * $uid purchaser uid
     * $money total order amount
     * $orderid order id
     * $song 1 is a free gift order
     */
    public function song_goods($duid,$uid,$money){
        //pay member
        $member = Db::name('member')->where(['id'=>$duid])->find();

        // Pay the reward
        if($member && $member['status']==1 && $member['ty_level']>0){

            //Experience member judgment

            // Pay the reward
            $df_money = round(config('config.krc_dfjiang')*$money*0.01,4);
            if($df_money) {
                //Change the payment reward KRC
                Member::Onefield($member['id'], 'money', 'up', $df_money);
                //add log
                MemberWalletLogModel::log($member['id'], $df_money, $member['money'], $member['money'] + $df_money, 206, 'Experience payment reward', $uid);
            }

            //Pay the gift coins
            $df_bi = round(config('config.krc_bjiang')*$money*0.01,4);
            if($df_bi) {
                //Change the reward coin for payment on behalf of others
                Member::Onefield($member['id'], 'pool_water', 'up', $df_bi);
                //add log
                MemberWalletLogModel::log($member['id'], $df_bi, $member['pool_water'], $member['pool_water'] + $df_bi, 207, 'Experience token reward', $uid);
            }

        }

    }








    /*
     * 拍卖团队结算逻辑
     * $money  拍卖增加金额
     * $uid    出局会员UID
     */

    public function pm_jiangli($money,$uid){
        //等级对应人数
        $dj_renshu = [];
        //等级对应代数
        $dj_daishu = [];
        //等级对接代数奖励百分比
        $dj_jiangli = [];

        for ($i=1;$i<7;$i++){
            $dengji = config('config.pm_dengji'.$i);
            $chai = explode(',',$dengji);
            $dj_renshu[$i] = $chai[0];
            $dj_daishu[$i] = $chai[1];
            unset($chai[0]);
            unset($chai[1]);
            $dj_jiangli[$i] = $chai;
        }

        $tuid = Db::name('member')->where(['id'=>$uid])->value('referid');
        if($tuid){
            $cengji = 0;
            while ($tuid){
                $tmember = Db::name('member')->where(['id'=>$tuid])->find();
                if(!$tmember){
                    break;
                }
                if($cengji>20){
                    break;
                }
                //正常会员  参加过拍卖
                if($tmember['status']==1 && $tmember['is_auction']==1){
                    //直推有效会员数
                    $pmnum = Db::name('member')->where(['referid'=>$tuid,'is_auction'=>1,'status'=>1])->count();
                    if($pmnum>0){
                        $level = $this->pmlevel($dj_renshu,$pmnum);
                        Db::name('member')->where('id',$tuid)->update(['pm_level'=>$level]);

                        if($level>0){
                            //发奖励
                            $daishu = $dj_daishu[$level];
                            if($daishu>$cengji){
                                $jdaishu = $cengji+1;
                                $jiangli_arr = $dj_jiangli[$level];
                                $bfb = $jiangli_arr[$jdaishu+1];
                                if($tmember['dl_level']>0 && $tmember['dl_level']<=3){
                                    $bfb = $bfb+config('config.paimai'.$tmember['dl_level']);
                                }

                                //奖励金额
                                $jmoneys = round($money*$bfb*config('config.chuju')*0.01*0.01,4);
                                if($jmoneys>0) {
                                    //变更KRC
                                    Member::Onefield($tmember['id'], 'money', 'up', $jmoneys);
                                    //添加日志
                                    MemberWalletLogModel::log($tmember['id'], $jmoneys, $tmember['money'], $tmember['money'] + $jmoneys, 135, '拍卖团队奖励', $uid);
                                }

                            }

                        }
                    }
                }

                $cengji = $cengji+1;
                $tuid = $tmember['referid'];

            }

        }


    }

    /*
     * 拍卖等级
     */
    public function pmlevel($dj_renshu,$nums){
        if($nums==0){
            $level = 0;
        }elseif($nums>=$dj_renshu[6]){
            $level = 6;
        }else{
            $level = 0;
            foreach ($dj_renshu as $key=>$value){
                if($nums<=$value){
                    $level = $key;
                    break;
                }
            }
        }

        return $level;
    }


    /*
     * 每日任务
     */
    public function daytask($id,$userid){

        if(!$id || !$userid){
            return ['status' => 0, 'msg' => '错误操作'];
        }
        $info = Db::name('assignment')->where([["type","=",1],['id','=',$id]])->find();
        if(!$info){
            return ['status' => 0, 'msg' => '该任务不存在'];
        }
        /*if($info['jl_type']==1){
            $type_name = '签到任务';
        }elseif ($info['jl_type']==2){
            $type_name = '视频任务';
        }elseif ($info['jl_type']==3){
            $type_name = '推广任务';
        }elseif ($info['jl_type']==4){
            $type_name = '代付任务';
        }elseif ($info['jl_type']==5){
            $type_name = '拍卖任务';
        }elseif ($info['jl_type']==6){
            $type_name = '充值任务';
        }elseif ($info['jl_type']==7){
            $type_name = '阅读任务';
        }*/
        $times = strtotime(date('Y-m-d'));
        $taskinfo = Db::name('task_log')->where([['task_id','=',$info['id']],['uid','=',$userid],['create_time','>',$times],['jl_type','=',$info['jl_type']]])->find();
        if($taskinfo){
            return ['status' => 0, 'msg' => '该任务当天已完成'];
        }

        $memberinfo = Db::name('member')->field('jifen,account')->where('id',$userid)->find();

        $datas = [];
        $datas['uid'] = $userid;
        $datas['task_id'] = $info['id'];
        $datas['title'] = $info['title'];
        $datas['jl_type'] = $info['jl_type'];
        $datas['account'] = $memberinfo['account'];
        $datas['integral_num'] = $info['jl_integral'];
        $datas['status'] = 1;
        $datas['task_type'] = 1;
        $datas['create_time'] = time();
        $datas['end_time'] = time();
        Db::name('task_log')->insert($datas);

        return ['status' => 1, 'msg' => '任务完成'];
    }









}

