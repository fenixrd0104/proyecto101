<?php
namespace app\api\controller;
use think\cache;
use think\controller;
use think\Db;
use app\common\model\OauthLogin;
use anerg\OAuth2\OAuth;
use \think\Request;
class Oalogin extends Base{
	/**
	 * Third-party login address
	 * @param OauthLogin $model
	 * @param $type type (qq, weixin, wx_qrcode)
	 */
	 public function login($type = 'weixin'){
	 empty($type) && $this->error('parameter error');
	 if(isset($_SERVER['HTTP_REFERER'])){
	 session('login_http_referer',$_SERVER["HTTP_REFERER"]);
	 }
		$model=new OauthLogin();
		try {
		$model->OauthInit($type);
		}catch (\Exception $e){
		echo $e->getMessage();
	 }
	 }
	 /**
	 * Authorization callback address
	 * @param $type type (qq, weixin, wx_qrcode)
	 */
		public function callback($type = null){
		(empty($type))&&$this->error('parameter error');
		$model=new OauthLogin();
		$user=$model->callback($type);
		if($user['token']){
		$this->loginHandle($user['user_info'], $type, $user['token']);
		}else{
		$this->success('Login failed!',$this->_get_login_redirect());
		}
	}
	/**
	 * login
	 * @param $user_info
	 * @param $type
	 * @param $token
	 */
	private function loginHandle($user_info, $type, $token){
		$model=db('oauth_user');
		$type=strtolower($type);
		$ip=request()->ip();
		$row=$model->where(array("from"=>$type,"openid"=>$user_info['openid']))->find();
		$config=cache('db_config_data');
		$user_info['nick'] = preg_replace('~\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]~', '', $user_info['nick']);
		$nember= array(
			'nickname' => $user_info['nick'],
			'head_img' => $user_info['avatar'],
			'update_time' =>time(),
			'last_login_ip' => $ip,
			'group_id'=>$config['default_user_group'],
			'login_num' =>'1',
			'status'=>'1',
			'closed'=>'0'
		);
		if(isset($user_info['gender']))$nember['sex']=$user_info['gender'];
		if($row){
			$find_user=db('member')->where(array("id"=>$row['uid']))->find();
			if($find_user){
				if($find_user['closed']=='1'){
					$this->error('You may have been blacklisted, please contact the webmaster!');exit;
					}elseif($find_user['status']=='0'){
					$nember['login_num']=intval($find_user['login_num'])+1;
					if(db('member')->where('id',$row['uid'])->update($nember)){
					$this->sessionCreate($find_user);
					$this->redirect($this->_get_login_redirect());
					}else{
					$this->error("Login failed",$this->_get_login_redirect());
					}
				}else{
					$this->sessionCreate($find_user);
					$this->redirect($this->_get_login_redirect());
				}
			}
			$model->where(array("from"=>$type,"openid"=>$user_info['openid']))->delete();
		}else{

		}
		$oauthUser= array(
				'from' => $type,
				'name' => $user_info['nick'],
				//'access_token' => $token['access_token'],
				//'expires_date' => (int)(time()+7*24*3600),
				'openid' => $user_info['openid'],
				'status'=>0
		);
		$nember['create_time']=time();
		$user=['uid'=>'','type'=>''];
		if(!empty($user_info['unionid'])){
			$user['uid']=db('oauth_user')->where(array('unionid'=>$user_info['unionid']))->value('uid');
			$user['type']=0;
			$oauthUser['unionid']=$user_info['unionid'];
		}
		if(!$user['uid']){
			if(!Db::name('member')->insert($nember)){
				$this->error("failed to login",$this->_get_login_redirect());
			}
			$user['uid']=Db::name('member')->getLastInsID();
			$user['type']=1;
		} 
		$oauthUser['uid']=$user['uid'];

		if(db('oauth_user')->insert($oauthUser)){
			$nember['id']=$oauthUser['uid'];
			$this->sessionCreate($nember);
			$this->redirect($this->_get_login_redirect());
		}else{
			if($user['type']){
				db('member')->delete($oauthUser['uid']);
			}
			$this->error("failed to login",$this->_get_login_redirect());
		}
	}
	/**
	 * 
	* @Title: sessionCreate
	* @param 
	 */
	private function sessionCreate($user){
		$auth = array(
				'uid' => $user['id'],
				'username' => $user['nickname'],
				'last_login_time' => NOW_TIME,
		);
		session('user_auth', $auth);
		session('user_auth_sign', data_auth_sign($auth));
	}
	private function _get_login_redirect(){
		if(empty(session('login_http_referer'))){
			return url('/wap/index/index','',true,true);
		}else{
			return session('login_http_referer');
		}
	}
}