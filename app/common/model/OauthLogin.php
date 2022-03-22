<?php
namespace app\common\model;
use think\Model;
use think\facade\Db;
use anerg\OAuth2\OAuth;
class OauthLogin extends Model
{
	protected $name = 'auth_config';
	protected $scopeValue=['qq'=>'get_user_info,add_share','weixin'=>'snsapi_userinfo','wx_qrcode'=>'snsapi_login'];
	/**
	 * Third-party login initialization
	 * @param $oauth_type type (qq, weixin, wx_qrcode)
	 * @return \think\response\Redirect jump address
	 */
	public function OauthInit($oauth_type,$site_type='mobile') {
		$oauth_type=strtolower($oauth_type);
	    $oainfo=$this->getConfigInfo($oauth_type);
		$callback=url('api/Oalogin/callback',['type'=>$oauth_type],true,true);
		$config = [
		  'app_key'    => $oainfo['app_key'],
		  'app_secret' => $oainfo['app_secret'],
		  'scope'      =>$this->scopeValue[$oauth_type],
		];
		$config['callback']=[
		   'default' => $callback,
		   'mobile'  => $callback
		];
		$OAuth  = OAuth::getInstance($config, $oauth_type);
		$OAuth->setDisplay($site_type); //This is optional. If it is not set to mobile, the redirected authorization page may not be suitable for mobile browser access.
		$url=$OAuth->getAuthorizeURL();
		if (!headers_sent()){
			header("refresh:0;url={$url}");
		} else {
			$str="<meta http-equiv='Refresh' content='0;URL={$url}'>";
		}
	}
	/**
	 * Callback to get user information
	 * $channel type (qq, weixin, wx_qrcode)
	 */
	public function callback($channel) {
		$data=[];
		$channel=strtolower($channel);
		$callback=url('api/Oalogin/callback',['type'=>$channel],true,true);
		$oainfo=$this->getConfigInfo($channel);
		$config = [
		  'app_key'    => $oainfo['app_key'],
		  'app_secret' => $oainfo['app_secret'],
		  'scope'      =>$this->scopeValue[$channel],
		];
		$config['callback']=[
		   'default' => $callback,
		   'mobile'  => $callback
		];
		$OAuth=OAuth::getInstance($config, $channel);
		$data['token']=$OAuth->getAccessToken();
		$data['user_info']= $OAuth->userinfo();
		return $data;
	}
	/**
	* Get configuration information
	*/
		private function getConfigInfo($type){
		if(!in_array($type, array('qq','weixin','wx_qrcode'))) {
		throw new \Exception('Type error',0);
		}
		$oainfo=$this->where(array('type'=>$type))->value('config');
		if(!$oainfo){
		throw new \Exception('There is no corresponding configuration information in the table',0);
		}
		$oainfo=unserialize($oainfo);
		return $oainfo;
	}
}
