<?php
namespace app\admin\controller;
use think\facade\Db;
use app\BaseController;
use think\facade\Request;

class Base extends BaseController
{
    public $isAdminGroup=0;
    public $isAdmin=1;
    public $shopId =0;
    public $userId =0;
    public $userName ='';
    public function initialize()
    {
		Request::filter(['strip_tags','htmlspecialchars']);
        if(!session('uid')||!session('username')){
            die(json_encode(['code'=>-1,'data'=>[],'msg'=>'please log in first']));
        }

        $this->shopId=session('shop_id');
        $this->userId=session('uid');
        $this->userName=session('username');

		//Verify if it is the same account
        $token = Db::name('admin')->where(['id'=>$this->userId])->value('token');

        if($token != session('token')){
         // die(json_encode(['code'=>-1,'data'=>[],'msg'=>'Sorry, the account is logged in elsewhere']));
        }
        $auth = new \com\Auth();
       // $module     = strtolower(request()->app());
        $controller = strtolower(request()->controller());
        $action     = strtolower(request()->action());
        //$url        = $module."/".$controller."/".$action;
        $url        = $controller."/".$action;
      //  echo $url;die;
		//Skip detection and homepage permissions
        if(session('uid')!=1){
            if(!in_array($url, ['index/getmenu','index/getinfo','cashier/test','index/index','index/indexpage','upload/upload','index/uploadface' ,'upload/um.html?editorid=editor'])){
                if(!$auth->check($url,session('uid'))){
                    die(json_encode(['code'=>0,'data'=>[],'msg'=>'Sorry, you don't have permission to operate']));
                }
            }
        }
        $config = cache('db_config_data');
        if(!$config){
            $config = $this->configLists();
            cache('db_config_data',$config);
        }
        config($config,'config');
        if(config('config.web_site_close') == 0 && session('uid') !=1 ){
            die(json_encode(['code'=>0,'data'=>[],'msg'=>'The site is closed, please visit later~']));
        }

        if(config('config.admin_allow_ip') && session('uid') !=1 ){
            if(in_array(request()->ip(),explode('#',config('config.admin_allow_ip')))){
                die(json_encode(['code'=>0,'data'=>[],'msg'=>'403:禁止访问']));
            }
        }
    }

    /**
	* Get a list of configurations in the database
     * @return array configuration array
     */
    public static function configLists(){
        $map  = array('status' => 1);
        $data = Db::name('Config')->where($map)->field('type,name,value')->select();

        $config = array();
        if($data){
            foreach ($data as $value) {
                $config[$value['name']] = self::parse($value['type'], $value['value']);
            }
        }
        return $config;
    }

    private static function parse($type, $value){
        switch ($type) {
            case 3: //parse array
                $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
                if(strpos($value,':')){
                    $value  = array();
                    foreach ($array as $val) {
                        list($k, $v) = explode(':', $val);
                        $value[$k]   = $v;
                    }
                }else{
                    $value =    $array;
                }
                break;
        }
        return $value;
    }
}