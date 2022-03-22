<?php
namespace app\merchant\controller;
use app\BaseController;
use think\facade\Session;
use think\facade\Db;
use think\facade\Request;
class Base extends BaseController
{
    public $isAdminGroup=0;
    public $isAdmin=1;
    public $shopId =0;
    public $mid =0;
    public $nickname ='';
    public function initialize()
    {
//        dump(session('mid'));
//        dump(session('reusername'));
        //session('mid', '1004'); //User ID
        //session('nickname', 'zhaoyu'); //username
        //session('shop_id', '1'); //username
		Request::filter(['strip_tags','htmlspecialchars']);
        if(!Session::get('mid')||!Session::get('nickname')){
            die(json_encode(['code'=>-1,'data'=>[],'msg'=>'Please log in first']));
        }

        $this->shopId=session('shop_id');
//        $this->shopId=1000;
        $this->mid=session('mid');
//        $this->mid=1004;
        $this->nickname=session('nickname');
//        $this->nickname='zhaoyu';

        $config = cache('db_config_data');
        if(!$config){
            $config = $this->configLists();
            cache('db_config_data',$config);
        }
        config($config,'config');
        if(config('config.web_site_close') == 0 && Session::get('merchant.mid') !=1 ){
            die(json_encode(['code'=>0,'data'=>[],'msg'=>'The site has been closed, please visit ~']));
        }
        if(config('config.admin_allow_ip') && Session::get('merchant.mid') !=1 ){
            if(in_array(request()->ip(),explode('#',config('config.admin_allow_ip'))))){
                die(json_encode(['code'=>0,'data'=>[],'msg'=>'403: Access forbidden']));
            }
        }
    }

    /**
     * Get the list of configurations in the database
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