<?php

namespace app\api\controller;


use app\common\model\Store;
use app\common\service\Payment;
use app\common\service\Users;
use app\common\service\Upload;
use think\Controller;
use think\Db;
use think\Url;
use org\Verify;

/**
 * swagger:User Center+
 */
class User
{



	/**
	 * get: id
	 * path: getUserInfo
	 * method: getUserInfo
	 * param: id - {int} 用户id
	 */
	public function getuserinfo() {
		$uid =get_uid();
		if($uid){
			$Users=new Users();
			$data=$Users->getUserInfo($uid);
			//TODO can't be calculated by points here, the points are still consumed and only the experience value can be used to calculate the total consumption
			// membership level
				$data['data']->grade=$Users->getUserLevel($data['data']->integral);
			// Message Center
				$data['data']->unread_msg=$Users->getUserUnreadMsg($uid);
				//TODO user avatar URL processing
				//TODO order status processing

				}else{
				$data['code'] = 0;
				$data['msg'] = 'User not logged in';
				}
				return json($data);

				}
				/**
				* post: Determine whether the user account or nickname already exists
				* path: check_user_account
				* param: account user account (email/mobile phone/nickname)
	 */
	public function check_user_account($account){
		$Users=new Users();
		$res = $Users->check_account($account);
		if($res == false){
			$data['code'] = 1;
			$data['msg'] = 'Unoccupied';
			}else{
			$data['code'] = 0;
			$data['msg'] = 'Occupied';
		}
		return json($data);
	}


    //upload avatar
    public function upload(){
		/*
		$file = request()->file('image');
		$info = $file->move(UPLOAD_PATH);
		if($info){
            $data['status'] = 1;
			$data['info'] = $info->getSaveName();
			return json($data);
        }else{
        	$data['status'] = 0;
			$data['info'] = $file->getError();
			return json($data);
        }
		*/
		$up = new Upload();
		$back = $up->upload();
		$back['info'] = $back['data'];
		unset($back['data']);
		return json($back);
    }

	//Send code verification code
	//$id message ID id
	//$param sending address
	//$type sending method: sms/email SMS or email
	public function send_verify($id, $param, $type){
	$verify_send_time = session('verify_send_time');
	if(isset($verify_send_time) && time()<$verify_send_time+60){
	$data['code'] = 0;
	$data['msg'] = 'The verification code is sent too frequently, please send it later';
		}else{
			$verify = rand(100000,999999);
			
			if($type == 'sms'){
				$data = \app\common\service\Msg :: send_sms($id,$param,array('name'=>$param,'code'=>$verify));
			}elseif($type == 'email'){
				$data = \app\common\service\Msg :: send_email($id,$param,array('name'=>$param,'verify'=>$verify));
			}
			if($data['code'] == 1){
				session('verify',$verify);
				session('verify_expire',time()+300);
				session('verify_send_time',time());
			}
		}
		return json($data);
	}

	//User site message list
	public function msg_list($page,$pagesize=10){
		$uid = get_uid();
		if($uid > 0){
			$User = new Users();
			
			$data['code'] = 1;
			$data['info'] = $User->get_msg($uid,$page,$pagesize);
		}else{
			$data['code'] = 0;
			$data['msg'] = 'not logged in';
		}
		return json($data);
	}
	//View messages
	public function read_msg($id){
		$uid = get_uid();
		if($uid > 0){
			$User = new Users();
			
			$data['code'] = 1;
			$data['info'] = $User->read_msg($uid,$id);
		}else{
			$data['code'] = 0;
			$data['msg'] = 'not logged in';
		}
		return json($data);
	}
	// delete message
		public function del_msg($id){
			$uid = get_uid();
		if($uid > 0){
			$User = new Users();
			$res = $User->del_msg($uid,$id);
		if($res == false){
			$data['code'] = 0;
			$data['msg'] = 'Delete successful';
		}else{
			$data['code'] = 1;
			$data['msg'] = 'Delete failed';
		}
		}else{
			$data['code'] = 0;
			$data['msg'] = 'Not logged in';
		}
		return json($data);
	}

	//Recharge record
	public function paylog(){
		$uid =get_uid();
		$Users=new Users();
		return json($Users->userMoneyLog($uid,['status'=>1,'act'=>1]));

	}
	//Expenses record
	public function moneylog(){
		$uid =get_uid();
		$Users=new Users();
		return json($Users->userMoneyLog($uid));
	}
	//Points record
	public function integrallog(){
		$uid =get_uid();
		$Users=new Users();
		return json($Users->userIntegralLog($uid));
	}

	//Recharge $type1 WeChat
	public function wap_recharge($money,$pay_type = 'alipay'){
		$uid =get_uid();
		if(!$uid){
			return json(['code'=>0,'data'=>'','msg'=>'User is not logged in']);
		}
		$Users=new Users();
		$redata=$Users->recharge($uid,$money);
		if($redata['code'] != 1){
			return json($redata);
		}
		$data=$redata['data'];
		$Payment=new Payment();

		if ($pay_type == 'alipay') {
			$res =$Payment->ali_wap($data['uid'],'User recharge',$data['out_trade_no'],$data['money'],['method'=>'chongzhi','param '=>[]],url('wap/usercenter/usercenter','','',true));
			} else {
			$openid = Db::name('oauth_user')->where(['from' => 'weixin', 'uid' => $uid])->value('openid');
			$res = $Payment->wx_pub($openid, $uid, 'User recharge', $data['out_trade_no'],$data['money'], ['method'=>'chongzhi','param'= >[]],'User recharge');
		}
		return json($res);
	}

	/*--------------------------------B2B2C----------------------------------------------------*/

    //Graphical digital verification code
    public function img_verify()
    {
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->codeSet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $verify->length = 4;
        $verify->useNoise = false;
        $verify->fontSize = 14;
        return $verify->entry();
    }

    //member sign in
    public function login($account, $password,$verify) {
        $verifyd = new Verify();
        if($verifyd->check($verify) !== true){
            return json(['code'=>0,'msg'=>'Image verification code error']);
        }

        if ($account != '' || $password != '') {
            $Users=new Users();
            $user =$Users->login($account,$password);
            if ($user == false)
            {
                $data['code'] = 0;
                $data['msg'] = 'Incorrect account or password';
                return json($data);
            } else {
                if($user['status']!=1){
                    $data['code'] = 0;
                    $data['msg'] = 'This account has been disabled';
                    return json($data);
                }
                $Users->autoLogin($user);
                unset($user['password']);
                $data['code'] = 1;
                $data['data'] = $user;
                $data['msg'] = 'Login successful';
                \app\common\model\Cart::getInstance()->doLoginHandle($user['id']);//Shopping cart login integration
                return json($data);
            }
        } else {
            $data['code'] = 0;
            $data['msg'] = 'Parameter error';
            return json($data);
        }
    }

    //Sign Up
    public function register($account,$mobile,$password,$repassword,$verify) {

        $verifyd = new Verify();
//        if($verifyd->check($verify) !== true){
//            return json(['code'=>0,'msg'=>'image verification code error']);
// }

        if($password!=$repassword){
            return json(['status' => 0, 'msg' => 'The password entered twice is incorrect', 'data' => '']);
        }

        if ($account != '' || $password != '' || $mobile!='') {
            $Users=new Users();
            if($Users->check_account($account)){
                return json(['code'=>0,'msg'=>'nickname has been occupied']);
            }
            if($Users->check_mobile($mobile)){
                return json(['code'=>0,'msg'=>'mobile phone number is occupied']);
            }

            $res = $Users->reg($account,$password,$mobile);
            if ($res)
            {
                $data['code'] = 1;
               $data['msg'] = 'Registration successful';
            } else {
                $data['code'] = 0;
                $data['msg'] = 'Registration failed';
            }
        } else {
            $data['code'] = 0;
            $data['msg'] = 'Parameter error';
        }

        return json($data);

    }

    //sign out
    public function logout(){
        $Users=new Users();
        return json($Users->logout());
    }

    //retrieve password
    public function getback_password($phone,$password,$verify){

       /* $verify_expire = session('verify_expire');
        if(!isset($verify_expire) || time()>$verify_expire){
            $data['code'] = 0;
            $data['msg'] = 'Verification code expired';
            return json($data);
        }
        $s_verify= session('verify_'.$phone);
        if($s_verify != $verify){
            $data['code'] = 0;
            $data['msg'] = 'Mobile verification code error';
            return json($data);
        }*/

        $Users=new Users();
        $res = $Users->edit_pwd($phone,$password);
        if ($res) {
            $data['code'] = 1;
            $data['msg'] = 'Password changed successfully';
            session('verify_'.$phone,null);
        } else {
            $data['code'] = 0;
            $data['msg'] = 'Password change failed';
        }
        return json($data);

    }

    //upload image
    public function upload_img(){
        $up = new Upload();
        $back = $up->upload();
        return json($back);
    }

    //Mobile phone verification (retrieve password)
    public function send_password_verify($phone){

        if(!isMobile($phone)){
            return json(['code'=>0,'msg'=>'mobile phone number is invalid']);
        }
        $verify_send_time = session('verify_send_time');
        if(isset($verify_send_time) && time()<$verify_send_time+60){
            $data['code'] = 0;
            $data['msg'] = 'The verification code is sent too frequently, please send it later';
            return json($data);
        }else{
            // Determine if the phone number exists
            $Users = new Users();

            if($Users->check_mobile($phone)){
                $verify = rand(100000,999999);
                $data = \app\common\service\Msg :: send_sms(1,$phone,array('name'=>$phone,'code'=>$verify,'content'=>''));
                if($data['code'] == 1){
                    session('verify_'.$phone,$verify);
                    session('verify_expire',time()+300);
                    session('verify_send_time',time());

                }
                return json($data);
            }
            return json(['code'=>0,'msg'=>'account does not exist']);
        }
    }


    //member information
    public function userinfo(){
        $uid = get_uid();
        if($uid > 0) {
            $Users = new Users();
            $memberinfo = $Users->getUser($uid);
            unset($memberinfo['password']);
            $data = ['code' => 1, 'data' => $memberinfo, 'msg' => ''];
        }else{
            $data = ['code' => -1, 'data' => '', 'msg' => 'Not logged in, log in first'];
        }
        return json($data);
    }


    // Merchant application
    public function shop_create_apply(){
        //User needs to log in first
        $uid= get_uid();
        $Users =  new Users();
        $user=$Users->getUser($uid);
        $post=input('post.');
        $post['uid']=$uid;
        $post['account']=$user->account;
        $post['shop_status']=1;
        $WarehouseShopModel = new Store();
        $res = $WarehouseShopModel->insertOne($post);
        return json($res);
    }


    //Modify data
  /*  public function update_info(){
        $uid = get_uid();
        if($uid > 0){
            $param = input('post.');
            unset($param['money']);
            unset($param['integral']);
            unset($param['password']);
            unset($param['group_id']);
            unset($param['id']);
            unset($param['mobile']);
            unset($param['account']);
            $Users=new Users();
            $res = $Users->update_info($uid,$param);
            if ($res)
            {
                $data['code'] = 1;
                $data['msg'] = 'Modified successfully';

            } else {
                $data['code'] = 0;
                $data['msg'] = 'Modification failed';
            }
        }else{
            $data['code'] = -1;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
    }*/

    //modify data
    public function update_info($head_img='',$nickname='',$sex='',$age='',$email=''){
        $uid = get_uid();
        if($uid > 0){
            if($head_img=='' && $nickname=='' && $sex=='' && $age=='' && $email==''){
                $data['code'] = 1;
                $data['msg'] = 'No modification information';
            }else {
                $Users = new Users();
                if ($head_img) {
                    $param['head_img'] = $head_img;
                }
                if ($nickname) {
                    $param['nickname'] = $nickname;
                }
                if ($sex) {
                    $param['sex'] = $sex;
                }
                if ($age) {
                    $param['age'] = $age;
                }
                if ($email) {
                    $param['email'] = $email;
                }
                $res = $Users->update_info($uid, $param);

                if ($res) {
                    $data['code'] = 1;
                   $data['msg'] = 'Modified successfully';

                } else {
                    $data['code'] = 0;
                    $data['msg'] = 'Modification failed';
                }
            }
        }else{
            $data['code'] = -1;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
    }

    //change Password
        public function update_password($new_pwd,$renew_pwd,$verify){
            $uid = get_uid();
            if($uid > 0){
                $verifyd = new Verify();
                if($verifyd->check($verify) !== true){
                    return json(['code'=>0,'msg'=>'image verification code error']);
                }
    
                if($renew_pwd!=$new_pwd){
                    return json(['status' => 0, 'msg' => 'The password entered twice is incorrect', 'data' => '']);
            }

            $Users=new Users();
            $res = $Users->update_pwd($uid,$new_pwd);
            if ($res)
            {
                $data['code'] = 1;
               $data['msg'] = 'Password changed successfully';
            } else {
                $data['code'] = 0;
                $data['msg'] = 'Password change failed';
            }
        }else{
            $data['code'] = 0;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
    }


    //Change payment password
    public function update_zhifu($new_pwd,$renew_pwd,$verify){
        $uid = get_uid();
        if($uid > 0){
            $verifyd = new Verify();
            if($verifyd->check($verify) !== true){
                return json(['code'=>0,'msg'=>'image verification code error']);
            }

            if($renew_pwd!=$new_pwd){
                return json(['status' => 0, 'msg' => 'The payment password entered twice is incorrect', 'data' => '']);
            }

            $Users=new Users();
            $res = $Users->update_zhifu($uid,$new_pwd);
            if ($res)
            {
                $data['code'] = 1;
              $data['msg'] = 'Password changed successfully';
            } else {
                $data['code'] = 0;
                $data['msg'] = 'Password change failed';
            }
        }else{
            $data['code'] = 0;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
    }


    //Send reset phone verification
        public function send_verify1(){
            $uid=get_uid();
            $phone = Db::name('member')->where(['id'=>$uid])->value('mobile');
    
            $verify_send_time = session('verify_send_time');
            if(isset($verify_send_time) && time()<$verify_send_time+60){
                $data['code'] = 0;
                $data['msg'] = 'The verification code is sent too frequently, please send it later';
            return json($data);
        }else{
            $verify = rand(100000,999999);
            $data = \app\common\service\Msg :: send_sms(1,$phone,array('name'=>$phone,'code'=>$verify,'content'=>''));
            if($data['code'] == 1){
                session('verify_'.$phone,$verify);
                session('verify_expire',time()+300);
                session('verify_send_time',time());
            }
            return json($data);
        }
    }


    //Modify phone number
    public function edit_mobile($phone,$verify){
        $uid = get_uid();
        if($uid > 0){
            /* $verify_expire = session('verify_expire');
             if(!isset($verify_expire) || time()>$verify_expire){
                 $data['code'] = 0;
                 $data['msg'] = 'Verification code expired';
                 return json($data);
             }
             $oldphone = Db::name('member')->where(['id'=>$uid])->value('mobile');
             $s_verify= session('verify_'.$oldphone);
             if($s_verify != $verify){
                 $data['code'] = 0;
                 $data['msg'] = 'Mobile verification code error';
                 return json($data);
             }*/
            $param['mobile']=$phone;
            $Users=new Users();
            $res = $Users->update_info($uid,$param);
            if ($res)
            {
                $data['code'] = 1;
                $data['msg'] = 'Mobile phone number modified successfully';

            } else {
                $data['code'] = 0;
                $data['msg'] = 'Mobile phone number modification failed';
            }
        }else{
            $data['code'] = -1;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
    }













}