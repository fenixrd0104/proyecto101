<?php

namespace app\admin\controller;
use app\admin\model\LinkModel;
use think\facade\Db;
use think\exception\ValidateException;
class Link extends Base
{

    //Friendship link solution index
    public function index(){
	    $keywords=input('keywords','');
	    $page=input('page',1);
	    $limit=input('limit',10);
	    $count=Db::name('link')->count();
	    $lists=Db::name('link')->where('title','like',"%{$keywords}%")->order('sort','asc')->page($page,$limit)->select();
        return json(['code'=>1,'data'=>['info'=>$lists,'count'=>$count],'msg'=>'']);
    }


	public function index_list(){
        $auto_query = Db::name('link')->select();
        return json(['data' => $auto_query, 'msg' => 'List display']);
	}
	/**
	 * Add Affiliate Link Data
	 * @param Link $model
	 */
	public function add(){
        if ($this->request->isPost()) {
            $param = input('post.');
            try {
                $this->validate($param, 'LinkVaildate');
            } catch (ValidateException $e) {
			// validation failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
			// $result=$model->addData($param);
            $auto_query = Db::name('link')->insert($param);
            return json(['code' => 1, 'data' => $auto_query, 'msg' => 'Added successfully']);
        }
    }
	/**
	* update operation
	*/
	public function edit()
    {
        if ($this->request->isPost()) {
            $param=input('post.');

            $row = Db::name('link')->where('id',$param['id'])->update($param);
            if($row){
                return json(['code' => 1,'data' => $row,'msg' =>'update successful']);
            }else{
                return json(['code' => 0,'data' => $row,'msg' =>'update failed']);
            }
        }else{
            $id = input('id');
            $row = Db::name('link')->field('id,title,link,portrait,istarget,isshow,sort,create_time,update_time')->find($id);
            return json(['code' => 1, 'data' => $row, 'msg' => 'edit']);
        }
    }


		/**
		* Remove affiliate links
		*/
		public function del(){
		$id=input('id');
		// return json($id);
        $del = Db::name('link')->where('id',$id)->delete();
		if($del){
            return json(['code' => 1, 'data' => [], 'msg' => 'Delete successful']);
		}else{
            return json(['code' => 0, 'data' => [], 'msg' => 'deletion failed']);
		}
	}




	 


}