<?php

namespace app\merchant\model;
use Qiniu\json_decode;
use Wechat\WechatMenu;
use think\facade\Cache;
use think\Model;
use think\facade\Db;

class WxinMenu extends Model
{

     protected $name = 'wxin_menu';
     protected $tokenurl = 'https://api.weixin.qq.com/cgi-bin/token';
     protected $menuurl = 'https://api.weixin.qq.com/cgi-bin/menu/create';
     /**
     * Added WeChat auto-reply
           * @param array $param
           */
          public function addmenu($param){
          try{
          if($param['pid']){
          if($this->where(array('pid'=>$param['pid']))->count()>=5){
          return array('code'=>0,'msg'=>'The secondary menu cannot exceed 5');
          }
          }else{
          if($this->where(array('pid'=>0))->count()>=3){
          return array('code'=>0,'msg'=>'First-level menu cannot exceed 3');
          }
          }
          $result = $this->save($param);
          if($result){
          return array('code'=>1,'msg'=>'Added successfully');
     		}else{
     			return array('code'=>0,'msg'=>$this->getError());
     		}
     	}catch( PDOException $e){
     		return array('code'=>0,'msg'=>$e->getMessage());
     	}
     }
     public function menulist(){
     	  $list=$this->where(array('pid'=>'0'))->order(array('sort'=>'desc','id'=>'asc'))->select();
     	  foreach ($list as $k=>$v){
     	  	  $list[$k]['child']=$this->where(array('pid'=>$v['id']))->order(array('sort'=>'desc','id'=>'asc'))->select();
     	  }
     	  return $list;
     }  
     /**
      * Category selection menu
      */
     public function optionlist($where){
     return $this->field('id,pid,name')->where($where)->order(array('sort'=>'desc','id'=>'desc'))->select ();
     }
     /**
      * Find WeChat menu
      */
     public function rowWxin($id){
     return $this->find($id);
     }
     /**
      *Update menu
      */
     public function editMenu($param){
     try{
     $row=$this->find($param['id'])->toArray();
     $param['pid']=$param['pid']?$param['pid']:0;
     $count=$this->where(array('pid'=>$param['pid']))->count();
     if($param['pid']){
     if(!$row['pid']) $count++;
     if($count > 5){
     return array('code'=>0,'msg'=>'The secondary menu cannot exceed 5');
     			}
     		}else{
     			if($row['pid']) $count++;
     			if($count > 3){
     				return array('code'=>0,'msg'=>'First-level menu cannot exceed 3');
		}
			}
				$result = $this->where(['id'=>$param['id']])->update($param);
			if($result){
		return array('code'=>1,'msg'=>'update successful');
     		}else{
     			return array('code'=>0,'msg'=>$this->getError());
     		}
     	}catch( PDOException $e){
     		return array('code'=>0,'msg'=>$e->getMessage());
     	}
     }
     /**
      * get access_token
      */
     public function getToken() {
     	$model=db('auth_config');
     	$config=$model->where(array('type'=>'weixin'))->value('config');
     	if($config){
     		$config=unserialize($config);
     		$url=$this->tokenurl;
     		$params=array(
     		   'grant_type'=>'client_credential',
     		   'appid'=>$config['app_key'],
     		   'secret'=>$config['app_secret']
     		);
     		$url.='?'.http_build_query($params);
     		$json=$this->https_request($url);
     		$arr= json_decode($json, true);
     		if(isset($arr['errcode'])){
     			return false;
     		}
     		return $arr["access_token"];
     	}else{
     		return false;
     	}
     }
     /**
      * Create WeChat menu
      * @return mixed {"errcode":0,"errmsg":"ok"} {"errcode":40018,"errmsg":"invalid button name size"}
      */
     public function createWxinMenu(){
     	 $menu=$this->menuButtonObj();
	     $getTakon=$this->getToken();
     	 if(!$getTakon){
     	 	return false;
     	 }
     	 $data=json_encode(array('button'=>$menu),JSON_UNESCAPED_UNICODE);
     	 $url=$this->menuurl.'?access_token='.$getTakon;
     	 $json=$this->https_request($url,$data);
     	 return json_decode($json);
     }
     /**
     * @Title: munuButtonObj
     * @Description: menu list
     * @param
     * @return
      */
     public function menuButtonObj(){
     $list=$this->where(array('pid'=>0))->order('sort desc,id asc')->select();
     if(count($list)>3){
     throw \exception('First-level menu cannot exceed three');
     }
     if (empty($list)) {
     throw \exception('Please add at least one custom menu');
     }
     $menu=array();
     foreach ($list as $k=>$v){
     $sublist=$this->where(array('pid'=>$v['id']))->order('sort desc,id asc')->select();
     if($sublist){
     $menu[]=array('sub_button'=>$this->rebutton($sublist),'name'=>$v['name']);
     }else{
     if($v['type']=='2'){
     $menu[]=array('type'=>'view','name'=>$v['name'],'url'=>$v['key_value']);
     }else{
     $menu[]=array('type'=>'click','name'=>$v['name'],'key'=>$v['key_value']);
     }
     }
     }
     return $menu;
     }
     /**
      * Secondary menu data
      */
     public function rebutton($list){
     	$data=array();
     	foreach ($list as $k=>$v){
     		switch ($v['type']){
     			case '1':
     				$data[]=array('type'=>'click','name'=>$v['name'],'key'=>$v['key_value']);
     				break;
     			case '2':
     				$data[]=array('type'=>'view','name'=>$v['name'],'url'=>$v['key_value']);
     				break;
     		}
     	}
     	return $data;
     }
     /**
     * @Title: menugenerate
    * @Description: 3rd party menu creation
         * @param
          */
         public function menugenerate(){
         $data=$this->menuButtonObj();
         $menu_list=array('button'=>$data);
    
    
         $conf =config('config');;
         $WechatMenu=new WechatMenu([
         'token' => $conf['wx_token'], // Fill in the key you set
         'appid' =>$conf['wx_appid'], // Fill in the app id of the advanced calling function, please check it in the background of WeChat development mode
         'appsecret'=>$conf['wx_appSecret'] // Fill in the key for advanced calling function
         ]);
         if ($WechatMenu->createMenu($menu_list)) {
         return array('code'=>1,'msg'=>'created successfully, waiting to take effect','data' => '');
         }else{
         return array('code'=>0,'msg'=>'Creation failed! Error code'.$WechatMenu->errCode.'Error prompt'.$WechatMenu->errMsg,'data' => '');
         }
         }
         /**
          * https get or post, when data=null, it is a get request, and data is the parameter of post
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