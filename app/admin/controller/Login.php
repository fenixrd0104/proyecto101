<?php
namespace app\admin\controller;
use app\admin\model\UserType;
use app\BaseController;
use org\Verify;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Db;
use app\common\model\Address;
class Login extends BaseController
{
    /**
     * verification code
     * @return
     */
    public function checkVerify()
    {
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
     * login operation
     * @return
     */
    public function doLogin()
    {
        $username = input("post.username");
        $password = input("post.password");
        $code = input("post.code");

        try {
          $this->validate(compact('username', 'password','code'), 'AdminValidate');
        }catch (ValidateException $e) {
		// validation failed output error message
            return json(['code' => 0, 'data' => '', 'msg' => $e->getError()]);
        }

        $verify = new Verify();
        if (!$verify->check($code)) {
           return json(['code' => 0, 'data' => '', 'msg' => 'Verification code error']);
        }


        $hasUser = Db::name('admin')->where('username', $username)->find();
        if(empty($hasUser)){
            return json(['code' => 0, 'data' => '', 'msg' => 'Administrator does not exist']);
        }
        if(md5(md5($password) . Config::get('app.auth_key')) != $hasUser['password']){
            writelog($hasUser['id'],$username,'User ['.$username.'] Login failed: password error',2);
            return json(['code' => -2, 'url' => '', 'msg' => 'Account or password error']);
        }

        if(1 != $hasUser['status']){
            writelog($hasUser['id'],$username,'User ['.$username.'] Login failed: the account is disabled',2);
            return json(['code' => -6, 'url' => '', 'msg' => 'This account is disabled']);
        }
		//Get the role information of the administrator
        $user = new UserType();
        $info = $user->getRoleInfo($hasUser['id']);

        $token = md5($hasUser['username'] . time());
        session('shop_id', $hasUser['shop_id']); //User ID
        session('uid', $hasUser['id']); //User ID
        session('username', $hasUser['username']); //username
        session('portrait', $hasUser['portrait']); //User avatar
        session('token', $token); //User avatar
        session('role_title', $info['title']); //role name
        session('role_rule', $info['rules']); //role node
        session('role_name', $info['name']); //role permissions
        //update admin status
        $param = [
            'loginnum' => $hasUser['loginnum'] + 1,
            'last_login_ip' => request()->ip(),
            'last_login_time' => time(),
            'token' => $token
        ];
        Db::name('admin')->where('id', $hasUser['id'])->update($param);
        writelog($hasUser['id'],session('username'),'User ['.session('username').'] Login successfully',1);
        return json(['code' => 1, 'data' => url('index/index'), 'msg' => 'Login successful!']);
    }
    /**
	 * sign out
     * @return
     */
    public function loginOut()
    {
	session('uid',null);
        session(null);
       // cache('db_config_data',null);//Clear the website configuration information in the cache
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