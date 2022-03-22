<?php

namespace app\api\controller;
use app\BaseController;
use think\facade\Db;
use think\facade\View;
use app\common\service\Upload as UploadService;
use think\facade\Request;
class Base extends BaseController
{
    public function initialize()
    {
		Request::filter(['strip_tags','htmlspecialchars']);
        $config = cache('db_config_data');
        if(!$config){
            $config = $this->configLists();
            cache('db_config_data',$config);
        }
        config($config,'config');
    }
		//Upload the image to Qiniu
    public function upload(){

		// if (!$user_id = get_uid()) {
		// return json(['status' => 0, 'msg' => 'Not logged in, please log in first']);
		// }
        error_reporting(0);
        # Compatible with processing without suffix after image compression
        foreach ($_FILES as $key => &$value) {
            # Determine if there is a suffix
            if ( strpos($value['name'],".") > 0 ) {
                continue ;
            }
            # Determine the mime type
            $bsufix = array_pop( explode('/',strtolower($value['type'])) ) ;
            if ( $bsufix == 'jpeg') {
                $value['name'] = $value['name'].'.jpg' ;
            }else{
                $value['name'] = $value['name'].'.'.$bsufix ;
            }
        }
        $uploadService = new UploadService();
        return json($uploadService->upload());
    }

		public function set_seo($title = 'KFC Mall', $keywords = 'KFC Mall', $description = 'KFC Mall')
		{
		return View::assign('seo', ['title' => $title, 'keywords' => $keywords, 'description' => $description]);
		}

    public function uid($lag=1){
        $uid=get_uid();
        if(!$uid){
            die(json_encode(['code'=>-1,'data'=>[],'msg'=>'user not logged in']));
        }else if($uid == -1){
            die(json_encode(['code'=>-1,'data'=>[],'msg'=>'user does not exist']));
        }
        return $uid;
    }
}