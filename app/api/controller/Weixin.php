<?php
namespace app\api\controller;
use think\Db;
use think\cache;
use \think\Request;
use app\common\service\Payment;
class Weixin extends Base{
    private  $msg='';
    public $wx_config=[];
    public $receive;
    public function _initialize(){
    	parent::_initialize();
    	$this->msg=config('wx_default_reply');
        $this->wx_config= [
           'token' => config('wx_token'), // fill in the key you set
            'appid' => config('wx_appid'), // Fill in the app id of the advanced calling function, please check it in the background of WeChat development mode
            'appsecret' => config('wx_appSecret') // Fill in the key for advanced calling functions
        ]; 
    	$this->receive=new \Wechat\WechatReceive($this->wx_config);
    }
    public function index(){
        $Receive=$this->receive;
        $validres=$Receive->valid();
        $type = $Receive->getRev()->getRevType();
       //file_put_contents(RUNTIME_PATH.'aa.txt',var_export($postStr,true));
        switch($type) {
            //text message
            case $Receive::MSGTYPE_TEXT:
            	$this->receiveText($Receive->getRevContent());
                break;
            //event
            case $Receive::MSGTYPE_EVENT:
                $event=$Receive->getRevEvent();
                $TYPE=$event['event'];
                $this->receiveEvent($TYPE,$event);
                break;
            case $Receive::MSGTYPE_IMAGE:
                break;
            default:
                $Receive->text($this->msg)->reply();
        }

    }
    /**
     * receive event messages
     */
    private function receiveEvent($TYPE,$event){
    	$Receive=$this->receive;
    	$content='';
    	switch ($TYPE){
    		case "subscribe":
    			$content=config('wx_focus');
    			$openid=$Receive->getRevFrom();
    			if($openid){
    				//WeChat user information
    				$WechatUser =  new \Wechat\WechatUser($this->wx_config);
    				$userinfo = $WechatUser->getUserInfo($openid);
    				$this->addWxinUser($userinfo);
    				if(!empty($event['key'])){
    					$code = str_replace("qrscene_","", $event['key']);
    					$this->updateWxinCode($userinfo,$code);
    				}
    			}
    			break;
    		case "unsubscribe":
    			$content = "unsubscribe";
    			$openid=$Receive->getRevFrom();
    			$this->changeUserStatus($openid);
    			break;
    		case "SCAN":
    			$openid=$Receive->getRevFrom();
    		    if(!empty($event['key']) && $openid){
    		    	$userinfo=['openid'=>$openid];
    		    	$this->updateWxinCode($userinfo,$event['key']);
    			}
    			$content = $this->msg;
    			break;
    		case "CLICK":
    			$content=$this->replyInfo($event['key']);
    			break;
    		default:
    			$content = $this->msg;
    			break;
    	}
    	if(is_array($content)){
    		$Receive->news($content)->reply();
    	}else{
    		$Receive->text($content)->reply();
    	}
    }
    /**
     * Update WeChat code information
     */
    private function updateWxinCode($userinfo,$code){
    	$uid=db('oauth_user')->where('openid',$userinfo['openid'])->value('uid');
    	$time=time()-600;
    	if(!$row=db('live_wxin_code')->where(['code'=>$code,'create_time'=>['>',$time]])->find()){
    		return false;
    	}
    	if($row['uid']){
    		if($row['uid']==$uid){
    			return $uid;
    		}else{
    			return false;
    		}
    	}else{
    		if(db('live_wxin_code')->where(['code'=>$code,'create_time'=>['>',$time]])->setField('uid',$uid)){
    			return $uid;
    		}else{
    			return false;
    		}
    	}
    }
    /**
	 *Change user follow status
     */
    public function changeUserStatus($openid){
    	$model=db('oauth_user');
    	if($model->where('openid', $openid)->find()){
	    	$model->where('openid', $openid)->update(['status' =>'2']);
    	}
    }
    /**
	 * Add WeChat user information
     */
    private function addWxinUser($userinfo){
    	$model=db('oauth_user');
    	$ip=request()->ip();
    	$config=cache('db_config_data');
    	$userinfo['nickname'] = preg_replace('~\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]~', '', $userinfo['nickname']);
    	$ret=$model->where(array("from"=>'weixin',"openid"=>$userinfo['openid']))->find();
    	$nember=[
    		'nickname' => $userinfo['nickname'],
    		'head_img' => $userinfo['headimgurl'],
    		'update_time' =>time(),
    		'last_login_ip' => $ip,
    		'group_id'=>$config['default_user_group'],
    		'login_num' =>'1',
    		'status'=>'1',
    		'sex'=>$userinfo['sex'],
    		'closed'=>'0'
    	];
    	if($ret){
    		if($ret['status']!='1') $model->where('openid', $userinfo['openid'])->update(['status' =>'1']);
    		// Description has been followed
    		    $user=db('member')->find($ret['uid']);
    		    if($user){
    		    $nember['login_num']=$user['login_num']+1;
    		unset($nember['group_id']);
    		    //If it exists, update the user information table
    		    db('member')->where(['id'=>$ret['uid']])->update($nember);
    		    }else{
    		    $nember['create_time']=time();
    		    // no longer exists, create the association directly
    		    $mid=db('member')->insert($nember,false,true);
    		    //Updating the MID of the WeChat table
    			db('oauth_member')->where(['id'=>$ret['id']])->update(['uid'=>$mid]);
    		}
    	
    	}else{
    		$oauthUser= [
    			'from' => 'weixin',
    			'name' => $userinfo['nickname'],
    			'expires_date' => (int)(time()+7*24*3600),
    			'openid' => $userinfo['openid'],
    			'status'=>1
    		];
    		$nember['create_time']=time();
    		$user=array('uid'=>'');
    		if(isset($userinfo['unionid'])){
    			$user['uid']=db('oauth_user')->where(array('unionid'=>$userinfo['unionid']))->value('uid');
    			$oauthUser['unionid']=$userinfo['unionid'];
    		}
    		// Insert into the WeChat table first
    		    $wid=db('oauth_user')->insert($oauthUser,false,true);
    		    //Insert into the member table
    		    if($wid){
    		    if(!$user['uid']){
    		    $user['uid']=db('member')->insert($nember,false,true);
    		    }
    		    //Updating the MID of the WeChat table
    			db('oauth_user')->where(['id'=>$wid])->update(['uid'=>$user['uid']]);
    		}
    	}
    }
    /**
     * receive text messages
     */
    private function receiveText($content){
    	$model=db('wxin_autoreply');
    	$autoreply=$model->where(['status'=>0])->order('id asc')->select();
    	$value=null;
    	foreach ($autoreply as $v){
    		$keywords =explode('|',trim($v['key_word'],'|'));
    		foreach($keywords as $k){
    			if(strpos($content,$k) !== false){
    				$value=$v['content'];
    				break 2;
    			}
    		}
    	}
    	if($value){
    		$this->receive->text($value)->reply();
    	}else{
    		$this->receive->text($this->msg)->reply();
    	}
    }
    /**
     * menu reply
     */
    private function replyInfo($key){
    	$data=db('wxin_menu')->field('info.*,think_wxin_menu.type')->join('think_wxin_reply_info info','think_wxin_menu.id=info.wxin_menu_id')->where(array('key_value'=>$key))->find();
    	if($data['description']){
	    	$content = trim($data['description']);
    	}else{
    		$content = $this->msg;
    	} 
    	return $content;
    }
    /**
     * Temporary QR code generation
     */
    public function spreadCodePro(){
    	if(session('login_value') && session('outtime')>time()){
    	    $codeInfo=db('live_wxin_code')->where(['code'=>session('login_value')])->find();
    	    if($codeInfo['token'] && $codeInfo['code']){
	    	    return ['status'=>'1','token'=>$codeInfo['token'],'code'=>$codeInfo['code']];
    	    }
    	}
    	$WechatUser =  new \Wechat\WechatUser($this->wx_config);
    	$access_token=$WechatUser->getAccessToken();
    	$time=time();
    	$rand=$time.rand(1000, 9999);
    	$scene_id=substr($rand, 5);
    	$outtime=$time+600;
    	if($access_token){
    		$proTicketUrl="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token={$access_token}";
    		$arr='{"expire_seconds": '.$outtime.', "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
    		$ticketInfo=json_decode($this->https_request($proTicketUrl,$arr),true);
    		$ip=request()->ip();
    		db('live_wxin_code')->insert(['create_time'=>$time,'code'=>$scene_id,'token'=>$ticketInfo['ticket'],'ip'=>$ip]);
    		session('login_value', $scene_id);
    		session('outtime', $outtime);
    		return ['status'=>'1','token'=>$ticketInfo['ticket'],'code'=>$scene_id];
    	}else{
    		return ['status'=>'0','token'=>''];
    	}
    }
    /**
     * The external call interface returns the WeChat shared key
     */
    public function getJsSign($url=''){
    	if($url==''){
    		$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    	}
        $WechatScript=new \Wechat\WechatScript($this->wx_config);
        return  $WechatScript->getJsSign($url);
    }
    /**
     * curl ask
     */
    private function https_request($url, $data = null){
    	$curl = curl_init();
    	curl_setopt($curl, CURLOPT_URL, $url);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    	if (!empty($data)){
    		curl_setopt($curl, CURLOPT_POST, 1);
    		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    	}
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    	$output = curl_exec($curl);
    	curl_close($curl);
    	return $output;
    }
}
