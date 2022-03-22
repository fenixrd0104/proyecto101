<?php

namespace app\api\controller;


use app\common\model\Store;
use app\common\service\Payment;
use app\common\service\Keywords;
use app\common\service\Users;
use app\common\service\Upload;
use think\Controller;
use think\facade\Db;
use think\Url;
use org\Verify;
use app\BaseController;
use app\common\service\Upload as UploadService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;
use think\facade\Request;
use app\common\model\Member;
use app\common\model\MemberWalletLogModel;
/**
 * swagger: User Center+
 */
class User extends BaseController{



    private $country_code=[
        86
        ,852
        ,886
        ,855
        ,66
        ,65
        ,60
        ,95
        ,84
        ,63
        ,853
        ,856
    ];
    private $country_info=[];

    public function __construct()
    {
		Request::filter(['strip_tags','htmlspecialchars']);
        $config = cache('db_config_data');
        if(!$config){
            $config = $this->configLists();
            cache('db_config_data',$config);
        }
        config($config,'config');
        $this->country_info= new \ArrayObject([
            '86' => 'China',
            '852' => 'Hong Kong',
            '886' => 'Taiwan',
            '855' => 'Cambodia',
            '66' => 'Thailand',
            '65' => 'Singapore',
            '60' => 'Malaysia',
            '95' => 'Myanmar',
            '84' => 'Vietnam',
            '63' => 'Philippines',
            '853' => 'Macau',
            '856' => 'Laos',
        ]);

    }
    //Return country code
    public function get_country_code(){
        $data= [] ;
        foreach ( $this->country_info as $k => $v){
            $data[]=[
                'id'=>$k,
                'name'=>$v,
            ];
        }
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }

    //Send registration verification code
    public function send_reg_verify($country_code,$phone,$check_type='mobile',$lag=1){

        / / Determine whether the opening range is not
        if(!in_array($country_code,$this->country_code)){
            return json(['code'=>0,'msg'=>'This number is not open']);
        }
        // Determine if the mobile account exists
        $Users = new Users();
        // if($Users->check_account($phone)){
        // return json(['code'=>0,'msg'=>'account has been registered']);
        // }
        if($check_type=='mobile'){
            $phone = str_replace(" ",'',$phone);
            if($Users->check_mobile($phone)){
                return json(['code'=>0,'msg'=>'The phone number has been registered']);
            }
        }else{
            if(!isEmail($phone)){
                return json(['code'=>0,'msg'=>'EMAIL error']);
            }
            if($Users->check_email($phone)){
                return json(['code'=>0,'msg'=>'The mailbox has been registered']);
            }
        }
        $verify_info = get_phone_verify($phone,'reg_');

        if(isset($verify_info) && time()<$verify_info['verify_send_time']+60){
            return json(['code'=>0,'data'=>[],'msg'=>'The verification code is sent too frequently']);
        }else{
            $verify = rand(100000,999999);
            if($check_type=='mobile'){
                $info = \app\common\service\Msg::send_sms(1,$country_code.$phone,array('name'=>$country_code.$phone,'code'=>$verify));
            }else{
               $info = \app\common\service\Msg::send_email(0,$phone,array('title'=>'TARGET Mall','content'=> 'Hello, your verification code is:' . $ verify . ', valid for 5 minutes, do not leak to others.'));
            }
            if($info['code']){
                $verify_info = [
                    'verify' => $verify,
                    'country_code' => $country_code,
                    'verify_expire' => time()+300,
                    'verify_send_time' => time()
                ];
                set_phone_verify($phone,$verify_info,'reg_');
                return json(['code'=>1,'data'=>[],'msg'=>'successfully sent']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>$info['msg']]);
            }
        }
    }


    //User registration
    public function register($account='',$phone,$password,$jy_password,$verify,$tma,$check_type='mobile',$lag=1) {

        //verify
        $verify_info = recheck_phone_verify($phone,$verify,'reg_');
        if(!$verify_info['code']){
            return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
        }
        if(!$password){
            return json(['code' => 0, 'msg' => 'Please enter the login password', 'data' => '']);
        }
        if(!$jy_password){
            return json(['code' => 0, 'msg' => 'Please enter the transaction password', 'data' => '']);
        }
        if($tma){
            $info = Db::name('member')->field('id,account,referid,tma,tnum,tdnum,status,tjuser')->where(['tma'=>$tma])-> find();
            if(!$info){
                return json(['code' => 0, 'msg' => 'Recommender does not exist', 'data' => '']);
            }

        }else{
            return json(['code' => 0, 'msg' => 'Please enter the referrer code', 'data' => '']);
        }

        if ($phone!='') {
            $Users=new Users();

            if($check_type=='mobile'){
                $phone = str_replace(" ",'',$phone);
                if($Users->check_mobile($phone)){
                    return json(['code'=>0,'msg'=>'The phone number has been registered']);
                }
            }else{
                if($Users->check_email($phone)){
                    return json(['code'=>0,'msg'=>'The mailbox has been registered']);
                }
            }

            $country_code = (get_phone_verify($phone,'reg_'))['country_code'];
            del_phone_verify($phone,$verify,'reg_');
            $res = $Users->reg($account,$password,$jy_password,$info['id'],$info['account'],$phone,$country_code,$check_type,$info['tjuser']);

            if ($res){
                $data['code'] = 1;
               $data['msg'] = 'Registration successful';
                $data['data']['app_downurl'] = config('config.app_downurl');
                //Increase the number of direct pushes after successful registration
                Db::name('member')->where(['id'=>$info['id']])->update(['tnum'=>$info['tnum']+1,'tdnum '=>$info['tdnum']+1]);

                //Increase the number of team registrations
                $tuid = $info['referid'];
                while ($tuid){
                    $smember = Db::name('member')->field('referid,tnum,tdnum')->where(['id'=>$tuid])->find();
                    if(!$smember){
                        break;
                    }
                    Db::name('member')->where(['id'=>$tuid])->update(['tdnum'=>$smember['tdnum']+1]);
                    $tuid = $smember['referid'];
                }

                //The daily task is judged to be completed. Recommended
                $pminfo = Db::name('assignment')->where(['jl_type'=>3,'status'=>1,'type'=>1])->find();
                if($pminfo){
                    $users = new Users();
                    $users->daytask($pminfo['id'],$info['id']);
                }


            } else {
                $data['code'] = 0;
                $data['msg'] = 'registration failed';
            }
        } else {
            $data['code'] = 0;
            $data['msg'] = 'Parameter error';
        }
        return json($data);
    }



    //modify phone number verification
        public function send_login_verify($country_code,$phone,$check_type='mobile',$lag=1){
    
            / / Determine whether the opening range is not
            if(!in_array($country_code,$this->country_code)){
                return json(['code'=>0,'msg'=>'This number is not open']);
            }
    
            $uid =get_uid();
            if($uid){
    
                $verify_info = get_phone_verify($phone,'edit_');
    
                if(isset($verify_info) && time()<$verify_info['verify_send_time']+60){
                    return json(['code'=>0,'data'=>[],'msg'=>'The verification code is sent too frequently']);
                }else{
                    $member = Db::name('member')->where('id',$uid)->find();
                    $verify = rand(100000,999999);
                    if($check_type=='mobile'){
                        if($member['mobile'] == $phone){
                            return json(['code' => 0, 'data' => [], 'msg' => 'can't match the old phone number']);
                        }
                        if(Db::name('member')->where('mobile',$phone)->find()){
                            return json(['code' => 0, 'data' => [], 'msg' => 'The phone number has been registered']);
                    }
                    $info = \app\common\service\Msg :: send_sms(1,$country_code.$phone,array('name'=>$country_code.$phone,'code'=>$verify));
                }else{
                    if($member['email'] == $phone){
                        return json(['code' => 0, 'data' => [], 'msg' => 'Cant match the old mailbox']);
                    }
                    if(Db::name('member')->where('email',$phone)->find()){
                        return json(['code' => 0, 'data' => [], 'msg' => 'The mailbox has already been registered']);
                    }
                    $info = \app\common\service\Msg :: send_email(0,$phone,array('title'=>'TARGETmall','content'=> 'Hi, your verification code is:' . $verify . '，Valid for 5 minutes, do not leak to others.'));
                }
                if($info['code']){
                    $verify_info = [
                        'verify'  => $verify,
                        'country_code'  => $country_code,
                        'verify_expire'  => time()+300,
                        'verify_send_time'  => time()
                    ];
                    set_phone_verify($phone,$verify_info,'edit_');
                   return json(['code'=>1,'data'=>[],'msg'=>'successfully sent']);
                }else{
                    return json(['code'=>0,'data'=>[],'msg'=>$info['msg']]);
                }
            }
        }else{
            return json(['code' => -1, 'data' => [], 'msg' => 'Please log in first']);
        }
    }

    //Verify the old phone number
    public function oldphone_verify($phone,$verify,$check_type){
        $member = Db::name('member')->where(["$check_type"=>$phone])->find();
        if(!$member){
            if($check_type=='mobile'){
                return json(['code'=>0,'data'=>[],'msg'=>'The phone number does not exist']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'The mailbox does not exist']);
            }
        }

        //verify
        $verify_info = check_phone_verify($phone,$verify,'withDraw_');
        if(!$verify_info['code']){
            return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
        }

        $data['code'] = 1;
        $data['msg'] = 'Verification succeeded';
        session('verify_'.$phone,null);
        
        return json($data);
    }

    //modify phone number
    public function editmobile($phone,$verify,$check_type='mobile'){
        $uid =get_uid();
        if($uid){
            $member = Db::name('member')->where('id',$uid)->find();
            if(!$member){
                return json(['code' => 0, 'data' => [], 'msg' => 'The account does not exist']);
            }
            if($check_type == 'mobile') {
                if ($phone == '' || $verify == '') {
                    return json(['code' => 0, 'data' => [], 'msg' => 'Please fill in the phone number/verification code correctly']);
                }
                if(Db::name('member')->where('mobile',$phone)->find()){
                    return json(['code' => 0, 'data' => [], 'msg' => 'The phone number has been registered']);
                }
                //验证
                $verify_info = recheck_phone_verify($phone, $verify, 'reg_');
                if (!$verify_info['code']) {
                    return json(['code' => 0, 'data' => [], 'msg' => $verify_info['msg']]);
                }
                $res = Db::name('member')->where('id',$uid)->update(['country_code'=>$verify_info['country_code'],'mobile'=>$phone]);
            }else{
                $verify_info = recheck_phone_verify($phone, $verify, 'reg_');
                if ($phone == '' || $verify == '') {
                    return json(['code' => 0, 'data' => [], 'msg' => 'Please fill in the correct email/verification code']);
                }
                if(Db::name('member')->where('email',$phone)->find()){
                    return json(['code' => 0, 'data' => [], 'msg' => 'The mailbox has been registered']);
                }
                $res = Db::name('member')->where('id',$uid)->update(['country_code'=>$verify_info['country_code'],'email'=>$phone]);
            }
            
            if($res){
               return json(['code' => 1, 'data' => [], 'msg' => 'change successfully']);
            }else{
                return json(['code' => 0, 'data' => [], 'msg' => 'modification failed']);
            }

        }else{
            return json(['code' => -1, 'data' => [], 'msg' => 'Please log in first']);
        }
    }

    /**
     * member sign in
     * App normal login or registration
     * type 0 verification code login 1 password login 2 email login
     * */
    public function login($check_type='mobile',$phone,$verify,$lag=1) {

        /* check first */
        if($check_type=='mobile') {
            if ($phone == '' || $verify == '') {
                return json(['code' => 0, 'data' => [], 'msg' => 'Please fill in the phone number/verification code correctly']);
            }
            //verify
            $verify_info = recheck_phone_verify($phone, $verify, 'login_');
            if (!$verify_info['code']) {
                return json(['code' => 0, 'data' => [], 'msg' => $verify_info['msg']]);
            }
        }else if($check_type=='password'){
            if ($phone == '' || $verify == '') {
                return json(['code' => 0, 'data' => [], 'msg' => 'Please fill in the login account and password correctly']);
            }
        }else{
            if ($phone == '' || $verify == '') {
                $verify_info = recheck_phone_verify($phone, $verify, 'login_');
                return json(['code' => 0, 'data' => [], 'msg' => 'Please fill in the correct email/verification code']);
            }
        }

        $Users=new Users();
        //The mobile phone number or email exists, log in directly
        if($Users->check_mobile($phone) || $Users->check_email($phone)){

            if($check_type=='mobile'){
                $user = $Users->phone_login($phone);
            }else if($check_type=='password'){

                if(!Db::name('member')->where(['mobile'=>$phone])->whereOr(['email'=>$phone])->value('password')){
                    return json(['code' => 0, 'data' => [], 'msg' => 'login phone number or email does not exist']);
                }
                $user = $Users->login($phone,$verify);
            }else{
                $user = $Users->email_login($phone);
            }
        }else{
            $data['code'] = 0;
            $data['msg'] = 'The account does not exist, please register first';
            return json($data);
        }
        //Login failed
        if ($user == false) {
            return json(['code' => 0, 'data' => [], 'msg' => 'The login account or password is incorrect, please log in again']);
        } else {
            if ($user['status'] != 1) {
                return json(['code' => 0, 'data' => [], 'msg' => 'This account has been disabled']);
            }
            $token = $Users->setAppLoginToken();
            $user['token'] = $token;
            $Users->autoLogin($user);

            unset($user['password']);
            return json(['code' => 1, 'data' => $user,'msg' => 'login successful']);
        }
    }


    //Send SMS/Email after login
    public function send_verify($check_type){
        $uid = get_uid();
        if(!$uid){
            return json(['code' => -1, 'data' => [], 'msg' => 'Please log in first']);
        }
        $info = Db::name('member')->find($uid);
        $param =$info[$check_type];

        if(!$param){
            if($check_type=='mobile'){
                return json(['code'=>103,'data'=>[],'msg'=>'Please bind the phone number']);
            }else{
                return json(['code'=>102,'data'=>[],'msg'=>'Please bind your email address']);
            }
        }
        //get verification code
        $verify_info = get_phone_verify($param,'verify_');
        if(isset($verify_info) && time()<$verify_info['verify_send_time']+60){
            return json(['code'=>0,'data'=>[],'msg'=>'The verification code is sent too frequently']);
        }else{
            $verify = rand(100000,999999);
            if($check_type=='mobile'){
               $res = \app\common\service\Msg::send_sms(1,$info['country_code'].$info['mobile'],array('name'=>$info['country_code'].$info ['mobile'],'code'=>$verify));
            }elseif($check_type == 'email'){
                $res = \app\common\service\Msg::send_email(0,$info['email'],array('title'=>'TARGET Mall','content'=>'Hello, your verification code is: ' . $verify . ', valid for 5 minutes, do not leak to others.'));
            }else{
                return json(['code'=>0,'data'=>[],'msg'=> 'Bad request interface']);
            }

            if($res['code']){
                $verify_info = [
                    'verify'  => $verify,
                    'verify_expire'  => time()+300,
                    'verify_send_time'  => time()
                ];
                set_phone_verify($param,$verify_info,'verify_');
                return json(['code'=>1,'data'=>[],'msg'=>'Sent successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
            }
        }
    }

    //Retrieve password and send SMS/Email
    public function send_zhaohui_verify($phone,$check_type='mobile',$lag=1){
        $info = Db::name('member')->where(["$check_type"=>$phone])->find();
        $param =$info[$check_type];
        if(!$param){
            if($check_type=='mobile'){
                return json(['code'=>0,'data'=>[],'msg'=>'The phone number does not exist']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'The mailbox does not exist']);
            }
        }

        //get verification code
        $verify_info = get_phone_verify($param,'verify_');
        if(isset($verify_info) && time()<$verify_info['verify_send_time']+60){
            return json(['code'=>0,'data'=>[],'msg'=>'The verification code is sent too frequently']);
        }else{
            $verify = rand(100000,999999);
            if($check_type=='mobile'){
                $res = \app\common\service\Msg :: send_sms(1,$info['country_code'].$info['mobile'],array('name'=>$info['country_code'].$info ['mobile'],'code'=>$verify));
            }elseif($check_type == 'email'){
                $res = \app\common\service\Msg :: send_email(0,$info['email'],array('title'=>'TARGET Mall','content'=> 'Hello, your verification code is: ' . $verify . ', valid for 5 minutes, do not leak to others.'));
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'Bad request interface']);
            }

            if($res['code']){
                $verify_info = [
                    'verify'  => $verify,
                    'verify_expire'  => time()+300,
                    'verify_send_time'  => time()
                ];
                set_phone_verify($param,$verify_info,'verify_');
                return json(['code'=>1,'data'=>[],'msg'=>'Sent successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>$res['msg']]);
            }
        }
    }

    //Bind mobile phone number or email to send verification code
    public function send_bang_verifys($phone,$country_code,$check_type='mobile'){
        $uid = get_uid();
        if(!$uid){
            return json(['code' => -1, 'data' => [], 'msg' => 'Please log in first']);
        }
        $info = Db::name('member')->find($uid);
        / / Determine whether the opening range is not
        $country_code = $info['country_code'];
        // Determine if the mobile account exists
        $Users = new Users();
        if($check_type=='mobile'){
            if($Users->check_mobile($phone)){
                return json(['code'=>0,'msg'=>'The phone number has been registered']);
            }
        }else{
            if(!isEmail($phone)){
                return json(['code'=>0,'msg'=>'EMAIL error']);
            }
            if($Users->check_email($phone)){
                return json(['code'=>0,'msg'=>'The mailbox has been registered']);
            }
        }
        $verify_info = get_phone_verify($phone,'verify_');

        if(isset($verify_info) && time()<$verify_info['verify_send_time']+60){
            return json(['code'=>0,'data'=>[],'msg'=>'Verification codes are sent too frequently']);
        }else{
            $verify = rand(100000,999999);
            if($check_type=='mobile'){
                $info = \app\common\service\Msg :: send_sms(1,$country_code.$phone,array('name'=>$country_code.$phone,'code'=>$verify));
            }else{
               $info = \app\common\service\Msg :: send_email(0,$phone,array('title'=>'TARGET Mall','content'=> 'Hello, your verification code is:' . $ verify . ', valid for 5 minutes, do not leak to others.'));
                           }

            if($info['code']){
                $verify_info = [
                    'verify'  => $verify,
                    'country_code'  => $country_code,
                    'verify_expire'  => time()+300,
                    'verify_send_time'  => time()
                ];
                set_phone_verify($phone,$verify_info,'bind_');
                return json(['code'=>1,'data'=>[],'msg'=>'Sent successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>$info['msg']]);
            }
        }
    }

    //Bind phone number or email
    public function send_bind_mobile($phone,$verify,$check_type='mobile'){
        $uid = get_uid();
        if(!$uid){
            return json(['code' => -1, 'data' => [], 'msg' => 'Please log in first']);
        }

        //verify
        $verify_info = recheck_phone_verify($phone, $verify, 'bind_');
        if (!$verify_info['code']) {
            return json(['code' => 0, 'data' => [], 'msg' => $verify_info['msg']]);
        }
        $find = Db::name('member')->where('id',$uid)->value($check_type);
        if($find){
            return json(['code'=>0,'data'=>[],'msg'=>'has been bound']);
        }
        $info = Db::name('member')->where(["$check_type"=>$phone])->find();
        $param =$info[$check_type];
        if($param){
            if($check_type=='mobile'){
                return json(['code'=>0,'data'=>[],'msg'=>'The phone number already exists']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'The mailbox already exists']);
            }
        }
        if($check_type == 'mobile'){
            $res = Db::name('member')->where('id',$uid)->update(['country_code'=>$verify_info['country_code'],'mobile'=>$phone]);
        }else{
            $res = Db::name('member')->where('id',$uid)->update(['email'=>$phone]);
        }

        if($res){
            session('bind_'.$phone,null);
            return json(['code'=>1,'data'=>[],'msg'=>'binding successfully']);
        }else{
            return json(['code'=>0,'msg'=>'binding failed']);
        }
        
    }

    public function user_index(){
        $uid = get_uid();
        $member = Db::name('member')->where('id',$uid)->find();
        $total = $member['pool_hatch']+ round($member['money']*config('config.cbnk_price')/config('config.usdt_price'),4);
        $map = [];
        $map[] = ['type','in','7,8,9,10,11,12,13,14,17,134,135,201,203,204,205,206,208'];
        $map[] = ['uid','=',$uid];
        $income = Db::name('member_wallet_log')->where($map)->sum("number");
        $map[] = ['create_time',">",strtotime(date("Y-m-d 00:00:00"))];
        $today_income = Db::name('member_wallet_log')->where($map)->sum("number");
        if($today_income<=0){
            $today_income = 0.00;
        }
        $data = [
            'total'=>$total,
            'income'=>$income,
            'today_income'=>$today_income,
        ];
        return json(['status' => 1,'data'=>$data, 'msg' => 'Get data successfully']);
    }
	/**
	 * get: id
	 * path: getUserInfo
	 * method: getUserInfo
	 * param: id - {int} userid
	 */
	public function getuserinfo() {
		$uid =get_uid();
		if($uid){
			$Users=new Users();
			$data=$Users->getUserInfo($uid);
            if(isset($data['status']) && $data['status'] == -1){
                return json($data);
            }
			//TODO can't be calculated by points here, the points are still consumed and only the experience value can be used to calculate the total consumption
			// membership level
$data['data']->grade=$Users->getUserLevel($data['data']->dl_level);
			// Message Center
			$data['data']->unread_msg=$Users->getUserUnreadMsg($uid);
//			$data['data']['money']=$this->save_wan($data['data']['money']);
//			$data['data']['pool_consumption']=$this->save_wan($data['data']['pool_consumption']);
//			$data['data']['encourage']=$this->save_wan($data['data']['encourage']);
//			$data['data']['pool_hatch']=$this->save_wan($data['data']['pool_hatch']);
//			$data['data']['integral']=$this->save_wan($data['data']['integral']);

            $url = config('config.web_site_url') .'/wap/index/register.html?code='.$data['data']['tma'];
            if(!$data['data']['tma_qrcode']){ 
                //$url = $http_type.$_SERVER['SERVER_NAME'].'/wap/index/register.html?invite_id='.$memberinfo['tma'];
                $qrCode = new QrCode($url);
                $qrCode->setSize(150);
                // Set advanced options
                $qrCode->setWriterByName('png');
                $qrCode->setMargin(5);
                $qrCode->setEncoding('UTF-8');
                $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());
                $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
                $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
                //$qrCode->setLogoSize(150, 200);
                $qrCode->setRoundBlockSize(true);
                $qrCode->setValidateResult(false);
                $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

                // Directly output the QR code
                //header('Content-Type: '.$qrCode->getContentType());
                //return '<img src="data:image/png;base64,'.base64_encode($qrCode->writeString()).'" />';
                //$qrCode->writeString();
                // Save it to a file
                if(!is_dir('./uploads/qrcode/temp')){
                    mkdir('./uploads/qrcode/temp',0755, true);
                }
                $qr_url = 'uploads/qrcode/temp';
                $qr_name = md5('qrcode'.time().rand(100,999)).'.png';
                $qrCode->writeFile($qr_url . '/'. $qr_name);
                $uploadService = new UploadService($qr_url);
                $res = $uploadService->ercodeOss($qr_url . '/'. $qr_name,$qr_name);
                if($res['code'] == 1){
                    Db::name('member')->where('id', $uid)->update(['tma_qrcode'=>$res['data']['url']]);
                    $memberinfo['tma_qrcode'] = $res['data']['url'];
                }

            }
			//TODO user avatar URL processing ah
			//TODO order status processing
			$data['fenxiang_url'] = config('config.fenxiang_url');
			
			}else{
			$data['code'] = 0;
			$data['msg'] = 'User not logged in';
		}
		return json($data);

	}
	public function save_wan($number='0'){
        return $number>=10000 ? round($number/10000,2) .'万' : round($number,2);
    }
    //Referral code
    public function tma_qrcode(){
        $uid =get_uid();
        if($uid){
            $memberinfo = Db::name('member')->where('id',$uid)->find();
            $url = config('config.web_site_url').'/wap/index/register.html?tma='.$memberinfo['tma'];
            if(!$memberinfo['tma_qrcode']){ 
                //$url = $http_type.$_SERVER['SERVER_NAME'].'/wap/index/register.html?invite_id='.$memberinfo['tma'];
                $qrCode = new QrCode($url);
                $qrCode->setSize(300);
                // Set advanced options
                $qrCode->setWriterByName('png');
                $qrCode->setMargin(10);
                $qrCode->setEncoding('UTF-8');
                $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
                $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
                $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
                //$qrCode->setLogoSize(150, 200);
                $qrCode->setRoundBlockSize(true);
                $qrCode->setValidateResult(false);
                $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

                // Directly output the QR code
                //header('Content-Type: '.$qrCode->getContentType());
                //return '<img src="data:image/png;base64,'.base64_encode($qrCode->writeString()).'" />';
                //$qrCode->writeString();
                // Save it to a file
                $qr_url = 'uploads/qrcode';
                if(!is_dir($qr_url)){ mkdir($qr_url,0755, true);}
                $qr_name = md5('qrcode'.time().rand(100,999)).'.png';
                $qrCode->writeFile($qr_url . '/'. $qr_name);
               
                Db::name('member')->where('id', $uid)->update(['tma_qrcode'=>config('config.web_site_url').'/'.$qr_url . '/'. $qr_name]);
                $memberinfo['tma_qrcode'] = config('config.web_site_url').'/'.$qr_url . '/'. $qr_name;

            }
            $data = ['code'=>1,'data'=>['tma_qrcode'=>$memberinfo['tma_qrcode'],'url'=>$url],'msg'=>'search successful'];
        }else{
            $data['code'] = 0;
            $data['msg'] = 'not logged in';
        }
        return json($data);
    }

    //upload picture
    public function upload(){
        $uploadService = new Upload();
        $info = $uploadService->upload('file','User upload');
        if($info['code']){
           $info['info'] = $info['data']['url'];
           $info['msg'] = 'Uploaded successfully';
           unset($info['data']);
        }
       return json($info);
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

    //Coupon ratio
    public function voucher($num=0){
        $uid =get_uid();
        if($uid > 0){
            $member = Db::name('member')->where('id',$uid)->find();
            
            switch ($member['dl_level']) {
                case 1:
                    $need_num = bcmul(config('config.daifuprice1'),$num,2);
                    break;
                case 2:
                    $need_num = bcmul(config('config.daifuprice2'),$num,2);
                    break;
                case 3:
                    $need_num = bcmul(config('config.daifuprice3'),$num,2);
                    break;
                default:
                    $need_num = bcmul(config('config.daifu_price'),$num,2);
                    break;
            }
           return json(['status'=>1,'data'=>['cbnk'=>$need_num],'msg'=>'operation successful']);
                   }else{
           $data['code'] = 0;
           $data['msg'] = 'Not logged in';
		}
		return json($data);
    }
    
    // buy voucher
        public function buy_voucher($num=0,$password){
            if($num <= 0){
                return json(['code'=>0,'msg'=>'Please enter a valid purchase quantity']);
            }
    
            $uid =get_uid();
            if($uid > 0){
    
                $jypwd=base64_encode(md5($password,true));
                $pay_pwd=Db::name('member')->where('id',$uid)->value('pay_password');
                if(!$pay_pwd){
                    return json(['code'=>0,'data'=>[],'msg'=>'Please set the transaction password first']);
                }
                if($jypwd != $pay_pwd){
                    return json(['code'=>0,'data'=>[],'msg'=>'transaction password is incorrect']);
            }

            $member = Db::name('member')->where('id',$uid)->find();
            switch ($member['dl_level']) {
                case 1:
                    $need_num = bcmul(config('config.daifuprice1'),$num,2);
                    break;
                case 2:
                    $need_num = bcmul(config('config.daifuprice2'),$num,2);
                    break;
                case 3:
                    $need_num = bcmul(config('config.daifuprice3'),$num,2);
                    break;
                default:
                    $need_num = bcmul(config('config.daifu_price'),$num,2);
                    break;
            }
            if($member['money'] < $need_num){
                return json(['code'=>0,'msg'=>'Your KRC is insufficient']);
            }
            // start transaction
            Db::startTrans();
            try {
                Db::name('member')->where('id',$uid)->dec('money',$need_num)->update();
                Db::name('member')->where('id',$uid)->inc('pool_sale',$num)->update();
                $MemberWalletLogModel = new MemberWalletLogModel;
                $MemberWalletLogModel->log($member['id'],$num,$member['pool_sale'],$member['pool_sale'] + $num,61,'Buy coupons increase',0);
                $MemberWalletLogModel->log($member['id'],$need_num,$member['money'],$member['money'] - $need_num,15,'purchase voucher KRC reduction',0);
                // commit the transaction
                Db::commit();
                return json(['status'=>1,'msg'=>'operation successful']);
            } catch (\Exception $e) {
                // rollback transaction
                Db::rollback();
                return json(['status'=>1,'msg'=>'operation successful']);
            }
        }else{
			$data['code'] = 0;
			$data['msg'] = 'not logged in';
		}
		return json($data);

    }

    //gold exchange ratio
        public function exchange_bl($num=0,$type=0){
            // USDT to KRC
            /*
                $type = 1 USDT to KRC 2 KRC to USDT
            */
            if($num <= 0){
                return json(['code'=>0,'data'=>['num'=>0],'msg'=>'Please enter a valid exchange amount']);
            }
            $uid =get_uid();
            if($uid > 0){
                //0: Disable mutual transfer 1: usdt can be transferred to KRC2: KRC can be transferred to usdt 3: can be transferred to each other
                if(config('config.shop_huzhuan') == 0){
                    return json(['code'=>0,'data'=>['num'=>0],'msg'=>'interchange is closed']);
            }

            if($type ==1){
                $shouxu = 0;
                $num  = round($num * config('config.usdt_price') / config('config.cbnk_price'),4);
            }else if($type == 2){
                $sxmoney = round(config('config.hu_sxmoney')*$num*0.01,4);
                $shengmoney = $num - $sxmoney;
                $num  = round($shengmoney * config('config.cbnk_price') / config('config.usdt_price'),4);
                $shouxu = $sxmoney;
            }else{
               return json(['code'=>0,'data'=>['num'=>0],'msg'=>'parameter error']);
            }
            
            return json(['status'=>1,'data'=>['num'=>$num,'shouxu'=>$shouxu],'msg'=>'operation successful']);
            
		}else{
			$data['code'] = 0;
			$data['msg'] = 'not logged in';
		}
		return json($data);
    }

    //gold exchange
    public function money_exchange($num=0,$type=0){
        // USDT to KRC
        /*
            $type = 1 USDT to KRC 2 KRC to USDT
        */
        if($num <= 0){
            return json(['code'=>0,'msg'=>'Please enter a valid exchange amount']);
        }
        $uid =get_uid();
        if($uid > 0){
            $user = Db::name('member')->where('id',$uid)->find();
            //0: Disable mutual transfer 1: usdt can be transferred to KRC2: KRC can be transferred to usdt 3: can be transferred to each other
            if(config('config.shop_huzhuan') == 0){
                return json(['code'=>0,'msg'=>'interchange is closed']);
            }
            
            if($type ==1){
                if(config('config.shop_huzhuan') == 1 || config('config.shop_huzhuan') == 3){
                    if($user['pool_hatch'] < $num){
                       return json(['code'=>0,'msg'=>'Your USDT is insufficient']);
                    }

                    $daytime = strtotime(date('Y-m-d'));
                    $zongusdt = config('config.hu_usdtdui');
                   $duizong = Db::name('member_wallet_log')->where([['type','=',23],['create_time','>',$daytime]])->sum('number' );
                   if($duizong>=$zongusdt){
                       return json(['code'=>0,'msg'=>'USDT exchange KRC quota has been used up on that day, and exchange has been stopped']);
                   }else{
                       $yuyue = $zongusdt - $duizong;
                       if($yuyue<$num){
                           return json(['code'=>0,'msg'=>'USDT exchangeable KRC quota remaining on the day'.$yuyue]);
                       }
                   }

                    $nums  = round($num * config('config.usdt_price') / config('config.cbnk_price'),2);
                    Db::name('member')->where('id',$uid)->dec('pool_hatch',$num)->update();
                    Db::name('member')->where('id',$uid)->inc('money',$nums)->update();
                    $MemberWalletLogModel = new MemberWalletLogModel;
                    $MemberWalletLogModel->log($user['id'],$num,$user['pool_hatch'],$user['pool_hatch'] - $num,23,'convert to KRC',0);
                    $MemberWalletLogModel->log($user['id'],$nums,$user['money'],$user['money'] + $nums,4,'USDT to KRC',0);
                    return json(['code'=>1,'msg'=>'redemption successful']);
                }else{
                    return json(['code'=>0,'msg'=>'The inter-transfer has been closed']);
                }
                
            }else if($type == 2){
                if(config('config.shop_huzhuan') == 2 || config('config.shop_huzhuan') == 3){
                    if($user['money'] < $num){
                        return json(['code'=>0,'msg'=>'Your KRC is insufficient']);
                    }
                    //$nums  = round($num * config('config.cbnk_price') / config('config.usdt_price'),2);
                    $sxmoney = round(config('config.hu_sxmoney')*$num*0.01,4);
                    $shengmoney = $num - $sxmoney;
                    $nums  = round($shengmoney * config('config.cbnk_price') / config('config.usdt_price'),4);

                    Db::name('member')->where('id',$uid)->dec('money',$num)->update();
                    Db::name('member')->where('id',$uid)->inc('pool_hatch',$nums)->update();
                    $MemberWalletLogModel = new MemberWalletLogModel;
                   $MemberWalletLogModel->log($user['id'],$nums,$user['pool_hatch'],$user['pool_hatch'] + $nums,24,'KRC to USDT',0);
                    $MemberWalletLogModel->log($user['id'],$num,$user['money'],$user['money'] - $num,3,'convert to USDT',0);
                    return json(['code'=>1,'msg'=>'redemption successful']);
                }else{
                    return json(['code'=>0,'msg'=>'The inter-transfer has been closed']);
                }

            }else{
                return json(['code'=>0,'msg'=>'parameter error']);
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


		//sign out
        public function logout(){
            $Users=new Users();
            return json($Users->logout());
        }
    
        //retrieve password
        public function getback_password($phone,$password,$verify,$check_type='mobile'){
    
            $member = Db::name('member')->where(["$check_type"=>$phone])->find();
            if(!$member){
                if($check_type=='mobile'){
                    return json(['code'=>0,'data'=>[],'msg'=>'The phone number does not exist']);
                }else{
                    return json(['code'=>0,'data'=>[],'msg'=>'The mailbox does not exist']);
                }
            }
    
            //verify
            $verify_info = check_phone_verify($phone,$verify,'verify_');
            if(!$verify_info['code']){
                return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
            }
    
            $Users=new Users();
            $res = $Users->edit_pwds($member['id'],$password);
            if ($res) {
                $data['code'] = 1;
                $data['msg'] = 'Login password changed successfully';
                session('verify_'.$phone,null);
            } else {
                $data['code'] = 0;
                $data['msg'] = 'Login password change failed';
        }

        return json($data);
    }

    // //upload image
        // public function upload_img(){
        // $up = new Upload();
        // $back = $up->upload();
        // return json($back);
        // }
    
        //Mobile phone verification (retrieve password)
        public function send_password_verify($phone){
    
            /*if(!isMobile($phone)){
                return json(['code'=>0,'msg'=>'mobile phone number is invalid']);
            }*/
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
                $keywords=config('config.sensitive_word');
                $keywordsArr=explode(',',$keywords);

                if ($nickname) {
                    //Sensitive vocabulary verification
                    foreach ($keywordsArr as $k => $v){
                        if(strstr($nickname,$v)){
                            $data['code'] = 0;
                            $data['msg'] = 'There is a sensitive word'.$v;
                            return json($data);
                        }
                    }
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

    //add and modify the password
    public function login_password($pwd,$password,$repassword,$verify,$check_type='mobile'){
        $uid= get_uid();
        $y_pwd=Db::name('member')->where(['id'=>$uid])->value('password');
        if($y_pwd!=md5(md5($pwd).config('app.auth_key'))){
            return json(['code' => 0, 'data' => '', 'msg' =>'The old login password is incorrectly entered']);
        }
        if($password!=$repassword){
            return json(['code' => 0, 'data' => '', 'msg' => 'The login password is incorrect twice']);
        }
        $mobile = Db::name('member')->where(['id'=>$uid])->value("$check_type");
        //verify
        $verify_info = check_phone_verify($mobile,$verify,'withDraw_');
        if(!$verify_info['code']){
            return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
        }

        $Users=new Users();
        $res = $Users->edit_pwds($uid,$password);
        if ($res) {
            $data['code'] = 1;
           $data['msg'] = 'Login password set successfully';
            session('verify_'.$mobile,null);
        } else {
            $data['code'] = 0;
            $data['msg'] = 'Login password setting failed';
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


    //Modify payment password
    public function update_zhifu($password,$repassword,$verify){
        $uid = get_uid();
        if($uid){
            if($password!=$repassword){
                return json(['code' => 0, 'data' => '', 'msg' =>'Two transaction passwords entered incorrectly']);
            }
            $mobile = Db::name('member')->where(['id'=>$uid])->value('mobile');
            $old_pwd = Db::name('member')->where(['id'=>$uid])->value('pay_password');
            if(base64_encode(md5($password,true)) == $old_pwd){
                return json(['code' => 0, 'data' => '', 'msg' =>'can not be the same as the original password']);
            }
            //verify
            $verify_info = check_phone_verify($mobile,$verify,'withDraw_');
            if(!$verify_info['code']){
                return json(['code'=>0,'data'=>[],'msg'=>$verify_info['msg']]);
            }
            $Users=new Users();
            $res = $Users->jiaoyiedit_pwd($uid,$password);
            if ($res) {
                $data['code'] = 1;
                $data['msg'] = 'Transaction password set successfully';
                session('verify_'.$mobile,null);
            } else {
                $data['code'] = 0;
                $data['msg'] = 'Failed to set transaction password';
            }
        }else{
            $data['code'] = -1;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
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

    //my friends team
    public function user_friend($page = 1,$num = 10){
        $uid = get_uid();

        if($uid){
            $user = Db::name('member')->where('id',$uid)->find();

            $list = Db::name('member')->where(['referid'=>$user['id']])->page($page, $num)->field('id,account,nickname,head_img,group_id,dl_level,create_time,pm_level,ty_level,tnum,tdnum')->select()->toArray();

            $e = Db::name('member')->where([['referid','=',$user['id']],['ty_level','>',0]])->count();
            $ss = Db::name('member')->where([['referid','=',$user['id']],['dl_level','=',1]])->count();
            $sss = Db::name('member')->where([['referid','=',$user['id']],['dl_level','=',2]])->count();
            $ssss = Db::name('member')->where([['referid','=',$user['id']],['dl_level','=',3]])->count();

            foreach ($list as $k => $v) {
                $acc_q = substr($v['account'],0,3);
                $acc_h = substr($v['account'],7);
                $list[$k]['account'] = $acc_q.'****'.$acc_h;
                // membership level
                $Users=new Users();
                $list[$k]['grade']=$Users->getUserLevel($v['dl_level']);
            }
            $data =['code'=> 1,'data'=> $list,'tnum'=>$user['tnum'],'tdnum'=>$user['tdnum'],'e'=> $e,'ss'=>$ss,'sss'=>$sss,'ssss'=>$ssss,'msg' =>'Get the friend list successfully'];
        }else{
            $data['code'] = -1;
            $data['msg'] = 'Not logged in';
        }
        return json($data);
    }

   //my junior friends team
    public function tuser_friend($tuid, $page = 1, $num = 10){
        $uid = get_uid();
        if($uid){

            $user = Db::name('member')->field('id,referid,tnum,tdnum')->where('id',$tuid)->find();
            $ceng = 0;//Corresponding level
            $is = 0;//Whether it is under the member umbrella
            $zuid = $user['referid'];
            while ($zuid){
                $tuser = Db::name('member')->field('id,referid')->where('id',$zuid)->find();
                if($tuser){
                    $ceng = $ceng+1;
                    if($uid==$zuid){
                        $is = 1;
                        break;
                    }
                }else{
                    break;
                }
                $zuid = $tuser['referid'];
            }

            if($is==0){
               return json(['code'=>0,'data'=>[],'msg'=>'Error view team']);
            }else{
                if($ceng>6){
                    return json(['code'=>0,'data'=>[],'msg'=>'You can only view the team information of the lower six layers at most']);
                }
            }


            $list = Db::name('member')->where(['referid'=>$user['id']])->page($page, $num)->field('id,account,nickname,head_img,group_id,dl_level,create_time,pm_level,ty_level,tnum,tdnum')->select()->toArray();
            $e = Db::name('member')->where([['tjuser','like',"%-".$user['id']."-%"],['ty_level','>',0]])->count();
            $ss = Db::name('member')->where([['tjuser','like',"%-".$user['id']."-%"],['dl_level','=',1]])->count();
            $sss = Db::name('member')->where([['tjuser','like',"%-".$user['id']."-%"],['dl_level','=',2]])->count();
            $ssss = Db::name('member')->where([['tjuser','like',"%-".$user['id']."-%"],['dl_level','=',3]])->count();
            foreach ($list as $k => $v) {
                $acc_q = substr($v['account'],0,3);
                $acc_h = substr($v['account'],7);
                $list[$k]['account'] = $acc_q.'****'.$acc_h;
                // membership level
                $Users=new Users();
                $list[$k]['grade']=$Users->getUserLevel($v['dl_level']);
            }
           $data =['code'=> 1,'data'=> $list,'e'=>$e,'tnum'=>$user['tnum'],'tdnum'=>$user['tdnum '],'ss'=>$ss,'sss'=>$sss,'ssss'=>$ssss,'msg' =>'Get the friend list successfully'];
        }else{
            return json(['code'=>-1,'data'=>[],'msg'=>'not logged in']);
        }
        return json($data);
    }

    //app version
    public function banben(){
        $banben = config('config.app_edition');
        $fx_appurl = config('config.app_downurl');
        return json(['code'=>1,'banben'=>$banben,'fx_appurl'=>$fx_appurl,'msg'=>'']);

    }


    // Become an experience member
        public function tylevel(){
            $uid = get_uid();
            if($uid){
    
                $member = Db::name('member')->field('id,referid,account,head_img,dl_level,ty_level,money,tiyan_time')->where(['id'=>$uid])-> find();
                $money = config('config.krc_tiyan');
                if($member){
                    if(request()->isPost()){
                        if($member['dl_level']>0 || $member['ty_level']>0){
                            return json(['status'=>0,'data'=>[],'msg'=>'Currently you are already at a high level and cannot be upgraded again']);
                        }
                        $param=input('post.');
                        $jypwd=base64_encode(md5($param['pay_password'],true));
                        $pay_pwd=Db::name('member')->where('id',$uid)->value('pay_password');
                        if(empty($pay_pwd)){
                            return json(['status'=>0,'data'=>[],'msg'=>'No transaction password is set']);
                        }
                        if($jypwd!=$pay_pwd){
                            return json(['code'=>0,'data'=>[],'msg'=>'transaction password is incorrect']);
                        }
    
                        if($member['money']<$money){
                            return json(['code'=>0,'data'=>[],'msg'=>'The balance of KRC coins is insufficient, it is necessary to become an experience member'.$money.'KRC']);
                    }
                    // start things
                    Db::startTrans();
                    try {

                        //change KRC
                        Member::Onefield($member['id'], 'money', 'down', $money);
                        //add log
                        MemberWalletLogModel::log($member['id'], $money, $member['money'], $member['money'] - $money, 202, 'Upgrade to experience member', $uid);

                        //modify membership level
                        Db::name('member')->where(['id'=>$uid])->update(['ty_level'=>1,'tiyan_time'=>time(),'mian_time'=>time (),'is_mian'=>1]);

                        $User = new Users();
                        //Team experience level judgment
                        $User->dls_level($uid,2);

                        //Direct push experience member rewards
                        $tmembers = Db::name('member')->where(['id'=>$member['referid']])->find();
                        if($tmembers && $tmembers['ty_level']>0 && $tmembers['status']==1){
                            $tyzhitui = config('config.krc_ztjiang');
                            //change KRC
                            Member::Onefield($tmembers['id'], 'money', 'up',$tyzhitui );
                            //add log
                            MemberWalletLogModel::log($tmembers['id'], $tyzhitui, $tmembers['money'], $tmembers['money'] + $tyzhitui, 203, 'Direct Push Experience Membership Rewards', $uid);
                        }

                        //Experience Membership Team Rewards
                        $config2 = explode(',',config('config.krc_tydj2'));
                        $config3 = explode(',',config('config.krc_tydj3'));
                        $config4 = explode(',',config('config.krc_tydj4'));
                        $tanarr = array(1=>0,2=>$config2[2],3=>$config3[2],4=>$config4[2]);
                        $tanarrss = array(1=>0,2=>$config2[2],3=>$config3[2]-$config2[2],4=>$config4[2]-$config3[2]);
                        $pingarr = array(2=>array(1=>$config2[3],2=>$config2[4]),3=>array(1=>$config3[3],2=>$config3[4]),4=>array(1=>$config4[3],2=>$config4[4]));
                        $pingnum = array(2=>0,3=>0,4=>0); //Levels per level
                        $tuid = $member['referid'];
                        $tdengji = 1;   //team level
                        while ($tuid){
                            if($tdengji>=4 && $pingnum[4]>=2){
                                break;
                            }
                            $tdmember = Db::name('member')->where(['id'=>$tuid])->find();
                            if(!$tdmember){  break; }
                            if($tdmember['status']==1) {
                                if ($tdmember['ty_level'] > 1) {

                                    //Level reward
                                    $pingjimoney = 0;
                                    if($tdengji>1){
                                        foreach ($pingnum as $key=>$value){
                                            if($key>$tdengji || $tdmember['ty_level']<$key){
                                                break;
                                            }
                                            if($value<2){
                                                $pci = $value+1;
                                                $pp = round($tanarrss[$key] * $pingarr[$key][$pci]*0.01,2);
                                                $pingjimoney = $pingjimoney+$pp;
                                                $pingnum[$key] = $pci;

                                            }
                                        }
                                    }

                                    //Experience Team Rewards
                                    $krcmoney = 0;
                                    if($tdmember['ty_level']>$tdengji){
                                        $krcmoney = $tanarr[$tdmember['ty_level']] - $tanarr[$tdengji];
                                        $tdengji = $tdmember['ty_level'];
                                    }

                                    $zongtuan = $pingjimoney + $krcmoney;

                                    if ($zongtuan) {
                                        //change KRC
                                        Member::Onefield($tdmember['id'], 'money', 'up', $zongtuan);
                                        //add log
                                        MemberWalletLogModel::log($tdmember['id'], $zongtuan, $tdmember['money'], $tdmember['money'] + $zongtuan, 204, 'Experience Team Rewards', $uid);
                                    }else{
                                        break;
                                    }

                                }
                               /* else {
                                    if ($tdengji > 1 && $tdengji == $tdmember['ty_level'] && $ispingji == 1 && $pjnum <= 1) {
                                        $nums = $pjnum + 1;
                                        $pjmoney = round($pjkrc * $pingarr[$tdengji][$nums]*0.01,2);

                                        if ($pjmoney) {
                                           //change KRC
                                            Member::Onefield($tdmember['id'], 'money', 'up', $pjmoney);
                                            //add log
                                            MemberWalletLogModel::log($tdmember['id'], $pjmoney, $tdmember['money'], $tdmember['money'] + $pjmoney, 205, '体验团队平级奖励', $uid);
                                            if ($nums == 1) {
                                                $pjnum = 1;
                                            } else {
                                                $ispingji = 0;
                                                $pjkrc = 0;
                                                $pjnum = 0;
                                            }
                                        }

                                    }

                                }*/
                            }
                            $tuid = $tdmember['referid'];

                        }

                        Db::commit();
                        return json(['code'=>1,'data'=>[],'msg'=>'Congratulations on your successful experience membership']);

                    } catch (\Exception $e) {
                        // rollback transaction
                        Db::rollback();
                        return json(['code' => 0, 'data' => [], 'msg' => $e->getMessage()]);
                    }

                }else{
                    if($member['ty_level']>0 && $member['tiyan_time']==0){
                        $member['tiyan_time'] = Db::name('order')->where(['user_id'=>$uid,'top_cate'=>1,'pay_status'=>1])->order('order_id asc')->value('pay_time');
                    }
                    return json(['code'=>1,'data'=>$member,'money'=>$money,'msg'=>'']);
                }

            }else{
               return json(['code'=>0,'data'=>[],'msg'=>'error operation']);
            }

        }else{
            return json(['code'=>-1,'data'=>[],'msg'=>'not logged in']);
        }

    }



    /*
    //Manually update the number of direct pushers
    public function  gengzt(){

        Db::name('member')->where([['id','>',0]])->update(['tnum'=>0]);
        $memberlist = Db::name('member')->field('id')->order('id desc')->select()->toArray();
        foreach ($memberlist as  $info) {

            $znums = Db::name('member')->where(['referid'=>$info['id']])->count();
            Db::name('member')->where([['id','=',$info['id']]])->update(['tnum'=>$znums]);

        }

    }



     //Manually update team size
        public function  gengtuan(){

            Db::name('member')->where([['id','>',0]])->update(['tdnum'=>0]);
            $memberlist = Db::name('member')->field('id,referid')->order('id desc')->select()->toArray();
            foreach ($memberlist as  $info) {

                //Increase team registrations
                $tuid = $info['referid'];
                while ($tuid) {
                    $smember = Db::name('member')->field('referid,tnum,tdnum')->where(['id' => $tuid])->find();
                    if (!$smember) {
                        break;
                    }
                    Db::name('member')->where(['id' => $tuid])->update(['tdnum' => $smember['tdnum'] + 1]);
                    $tuid = $smember['referid'];
                }

            }

        }

        //Update Membership Chart
         public function  gengtuan(){

             $memberlist = Db::name('member')->field('id,referid,tjuser')->order('id asc')->select()->toArray();
             foreach ($memberlist as  $info) {

                 //Increase team registrations
                 $tuid = $info['referid'];
                 if($tuid) {
                     $smember = Db::name('member')->field('id,referid,tjuser')->where(['id' => $tuid])->find();
                     if ($smember) {
                         if($smember['tjuser']){
                             $tjuser = $smember['tjuser'].$smember['id'].'-';
                         }else{
                             $tjuser = '-'.$smember['id'].'-';
                         }
                         Db::name('member')->where(['id'=>$info['id']])->update(['tjuser'=>$tjuser]);
                     }

                 }
             }
         }
         */

}