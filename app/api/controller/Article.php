<?php

namespace app\api\controller;
use think\Controller;
use think\facade\Db;

		/**
		* swagger: articles
		*/
		class Article
		{
		/**
		* get: article list
		* path: list
		* method: list
		* param: rows - {int} current page
		* param: pagessize - {int} display data per page
	 */
	public function list($rows, $pagesize)
    {
        if($rows != '' || $pagesize != ''){	
			$info = Db::name('article')->order('orderby asc,id desc')->limit($rows . ',' . $pagesize)->field('title,photo,create_time')->select();
			if ($info) {
				$data['code'] = 200;
				$data['datas'] = $info;
				$data['msg'] = 'Get the article list successfully';
				return json($data);
				} else {
				$data['code'] = 404;
				$data['msg'] = 'Failed to get article list';
				return json($data);
				}
				}else{
				$data['code'] = 400;
				$data['msg'] = 'Parameter error';
			return json($data);
		}
    }
    //Information list
    public function information($page = 1, $num = 10){
    	$info = Db::name('article')->where('cate_id',1)->order('orderby asc,id desc')->field('id,title,remark,photo,create_time')->page($page, $num)->select();
    	return json(['status'=>1,'data'=>$info,'msg'=>'']);
    }

    public function information_detail($id){
		Db::name('article')->where(['id'=>$id,'cate_id'=>1])->inc('views')->update(); 
    	$info = Db::name('article')->where(['id'=>$id,'cate_id'=>1])->field('title,remark,photo,create_time,content')->find();
    	return json(['status'=>1,'data'=>$info,'msg'=>'']);
    }

    public function article_detail($id){
        Db::name('article')->where(['id'=>$id])->inc('views')->update();
        $info = Db::name('article')->where(['id'=>$id])->find();
        $taskinfo = Db::name('assignment')->where(['jl_type'=>7,'status'=>1])->find();
        if($taskinfo){
            $tasktime = $taskinfo['read_time'];
        }else{
            $tasktime = -1;
        }
        return json(['status'=>1,'data'=>$info,'tasktime'=>$tasktime,'msg'=>'']);
    }
    
}