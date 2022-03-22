<?php


namespace app\admin\controller;

use think\facade\Db;
class Bar extends Base
{
    //list
    public function index(){
        $data=Db::name('industry')->where('status',1)->select()->toArray();
//        $arr=[];
//        foreach ($data as $k=>$v){
//           if($v['parent_id']==0){
//               foreach($data as $kk=>$vv){
//                   if ($vv['parent_id']==$v['id']){
//                       $v['children'][]=$vv;
//                   }
//               }
//               $arr[]=$v;
//           }
//        }
        return json(['code'=>1,'data'=>$data,'msg'=>'']);

    }
    //edit
    public function edit_bar(){
        if(request()->isPost()){
            $data=input('post.');
            if(empty($data['parent_id'])){
                $data['parent_id']=0;
            }
            $data['update_time']=time();
            $state=Db::name('industry')->where('id',$data['id'])->strict(false)->update($data);
            if($state){
				return json(['code'=>1,'data'=>'','msg'=>'update successful']);
            }else{
                return json(['code'=>0,'data'=>'','msg'=>'update failed']);
            }
        }
        $id=input('id');
        $data=Db::name('industry')->where('id',$id)->find();
        if($data){
            return json(['code'=>1,'data'=>$data,'msg'=>'']);
        }else{
            return json(['code'=>0,'data'=>'','msg'=>'Parameter error']);
        }

    }
    //Add to
    public function add_bar(){
        if(request()->isPost()){
            $data=input('post.');
            if(empty($data['parent_id'])){
                $data['parent_id']=0;
            }
            $data['create_time']=time();
            $data['update_time']=time();
            $state=Db::name('industry')->strict(false)->save($data);
            if($state){
				return json(['code'=>1,'data'=>'','msg'=>'Added successfully']);
            }else{
                return json(['code'=>0,'data'=>'','msg'=>'Add failed']);
            }
        }
    }

    //delete
    public function del_bar(){
        $id=input('id');
        $state=Db::name('industry')->where('id',$id)->delete();
        if($state){
			return json(['code'=>1,'data'=>'','msg'=>'deletion successful']);
        }else{
            return json(['code'=>0,'data'=>'','msg'=>'deletion failed']);
        }
    }

    //condition
    public function edit_state(){
        $id=input('id');
        $status=Db::name('industry')->where('id',$id)->value('status');
        if($status==0){
            Db::name('industry')->where('id',$id)->update(['status'=>1]);
        }else{
            Db::name('industry')->where('id',$id)->update(['status'=>0]);
        }

    }
    //Classification category
    public function cate($parent_id=0){
        if (!isset($parent_id) && empty($parent_id)){
            return json(['code'=>0,'data'=>'','msg'=>'Parameter error']);
        }
        $data=Db::name('industry')->where(['parent_id'=>$parent_id,'status'=>1])->field('id,gname,gimage,parent_id')->select()->toArray();
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }


//    public function getCategory($pid = 0, $is_all = 1)
//    {
//        if ($is_all) {
//            $arr = Db::name('bar')->where('status',1)->select()->toArray();
//            return $this->sonTree($arr, $pid);
//        } else {
//            return Db::name('bar')->where(['parent_id'=>0,'status'=>1])->field('id,gname,gimage')->select()->toArray();
//        }
//    }
//    public function sonTree($arr, $pid = 0)
//    {
//        static $Tree = array();
//        foreach ($arr as $k=>$v) {
//            if ($v['parent_id']== $pid) {
//                $Tree[] = $v;
//                $this->sonTree($arr, $v['id']);
//            }
//        }
//        return $Tree;
//}
    //edit sort
    public function edit_sort(){
        if(request()->isPost()){
            $params=input('post.');
            $db=Db::name('industry');
            $db->where('id',$params['id'])->update(['sort'=>$params['sort']]);
            return json(['code'=>1,'data'=>'','msg'=>'update completed']);
        }
    }
}








