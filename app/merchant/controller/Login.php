<?php
namespace app\merchant\controller;
use app\BaseController;
use think\exception\ValidateException;
use think\facade\Db;
use org\Verify;
use think\facade\Session;
use app\common\model\Address;
class Login extends BaseController{

    /**
     * Login operation
     * @return
     */
    public function doLogin(){
        $account = input("param.username");
        $password = input("param.password");
        $code = input("param.code");
        try {
            $this->validate(compact('account', 'password','code'), 'UserValidate');
        }catch(ValidateException $e) {
            // validation failed output error message
            return json(['code' => 0, 'data' => '', 'msg' => $e->getError()]);
        }
        $verify = new Verify();
        if (!$code) {
            return json(['code' => -4, 'url' => '', 'msg' => 'Please enter the verification code']);
        }
        if (!$verify->check($code)) {
            // return json(['code' => -4, 'url' => '', 'msg' => 'Verification code error']);
        }
        $hasUser = Db::name('member')->where('mobile', $account)->whereOr('email',$account)->find();
        if(empty($hasUser)){
            return json(['code' => -1, 'url' => '', 'msg' => 'user does not exist']);
        }
        if(md5(md5($password) . config('app.auth_key')) != $hasUser['password']){

            return json(['code' => -2, 'url' => '', 'msg' => 'Account or password error']);
        }
        if(1 != $hasUser['status']){

            return json(['code' => -6, 'url' => '', 'msg' => 'This account is disabled']);
        }
        if($hasUser['group_id'] <= 1 && $hasUser['shop_id']==0){

            return json(['code' => -8, 'url' => '', 'msg' => 'Ordinary users, please apply for a merchant first']);
        }
        $token =  md5($hasUser['nickname'] . time());
//        return 11111;

        //Obtain
        Session::set('mid', $hasUser['id']); //User ID
        Session::set('nickname', $hasUser['nickname']); //Username
        Session::set('reusername', $hasUser['reusername']); //User avatar
        Session::set('head_img', $hasUser['head_img']); //User avatar
        Session::set('group_id', $hasUser['group_id']); //User group
        Session::set('shop_id', $hasUser['shop_id']); //User group
        //update admin status
        $param = [
            'login_num' => $hasUser['login_num'] + 1,
            'last_login_time' => time(),
            'last_login_ip' => request()->ip(),
            'token' => $token
        ];
        Db::name('member')->where('id', $hasUser['id'])->update($param);
        return json(['code' => 1, 'data' => url('index/index'), 'msg' => 'Login successful!']);
    }


    /**
     * verification code
     * @return
     */
    public function checkVerify(){
        $verify = new Verify();
        $verify->imageH = 32;
        $verify->imageW = 100;
        $verify->codeSet = '0123456789';
        $verify->length = 4;
        $verify->useNoise = false;
        $verify->fontSize = 14;
        return $verify->entry();
    }

    /**
     * sign out
     * @return
     */
    public function loginOut(){
        Session::clear();
        return json(['code'=>1,'data'=>[],'msg'=>'exit successfully']);
    }
    /**
     * Obtain the three-level linkage of provinces, cities and counties
     * @return \think\response\Json
     */
    public function getRegionTree(){
        $AddressModel = new Address();
        $addr =$AddressModel->getRegionTree();
        return json(['code'=>1,'data'=>$addr,'msg'=>'']);
    }
}
