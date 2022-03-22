<?php

namespace app\admin\controller;
use app\admin\model\RegionModel;
use think\facade\Db;
class Address extends Base
{
//   index method under address
    public function index(){
        $model=new RegionModel();
//        $limit=1;
//        $page=input('page',1);
        $keywords = input('keywords');
        $map = [];
        if($keywords){
          $map[] = ['name','like','%'.$keywords.'%'];
        }
        $count=Db::name('region')->where($map)->where(array('parent_id'=>'0'))->count();
//        $list=Db::name('region')->field('id,name,level,parent_id,"" as child')->where(array('parent_id'=>'0'))->page($page,$limit)->select();

        $list=Db::name('region')->where($map)->field('id,name,level,parent_id,"" as child')->where(array('parent_id'=>'0'))->paginate(1);
//        $list=Db::name('region')->field('id,name,level,parent_id,"" as child')->where(array('parent_id'=>'0'))->select();
        $result=$this->ObjtoArr($list)['data'];
        foreach($result as $k=>$v){
            $result[$k]['child']=$this->ObjtoArr($model->getsubList($v['id']));
            if ($result[$k]['child']){
                foreach($result[$k]['child'] as $kk=>$vv){
                    $result[$k]['child'][$kk]['child']=$this->ObjtoArr($model->getsubList($vv['id']));
                }
            }
        }

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$result],'msg'=>'']);
    }

    function solve_arr($arr){
      foreach ($arr as $v){
            $arr=$v;
        if (is_array($arr)){
            foreach ($arr as $k=>$v){
                $arr=$v;
            }
            $captain[0]=['id'=>$arr['id'],'name'=>$arr['name'],'level'=>$arr['level'],'parent_id'=>$arr['parent_id']];
            $arr=$this->solve_arr($arr['child']);
            $result=array_merge($captain,$arr);
        }else{
            $result=$arr;
        }
      }
        return $result;
    }

//Returns the list of addresses in the dropdown
    public function index_add(){
        if ($this->request->isPost()){
            $model=new RegionModel();
            $data['parent_id']=input('parent_id','0');
            $data['name']=input('name');
            if($model->getReionCount($data['parent_id'],$data['name'])>0){
                return json(array('status'=>0,'msg'=>'Region already exists'));
            }
            if($data['parent_id']=='0'){
                $data['level']=1;
            }else{
                $row=$model->getOneRegion($data['parent_id']);
                if(!$row){
                    return json(array('status'=>0,'msg'=>'add failed'));
                }
                $data['level']=$row->level+1;
            }
            $sc=Db::name('region')->insert($data);
            if($sc){
                return json(array('status'=>1,'msg'=>'Added successfully'));
            }else{
                return json(array('status'=>0,'msg'=>'add failed'));
            }
        }
        $model=new RegionModel();
        $lists=$model->treeList();
        return json(['code'=>1,'data'=>$lists,'msg'=>'']);
    }
   //Address down index_edit
    public function index_edit(){
        if ($this->request->isPost()){
            $model=Db::name('region');
            $id=input('id');
            $name=input('name');
            if($model->where('id',$id)->find()){
                if($model->where(array('id'=>$id))->update(array('name'=>$name))){
                    return json(array('status'=>1,'msg'=>'Saved successfully'));
                }else{
                    return json(array('status'=>0,'msg'=>'Failed to save'));
                }
            }

        }
        $model=new RegionModel();
        $id=input('id');
        $list=Db::name('region')->find($id);
        if ($list['parent_id']){
            $name=$model->getArea($list['parent_id']);
        }else{
            $name='without';
        }
        $list['pname']=$name;
        return json(['code'=>1,'data'=>$list,'msg'=>'']);
    }



   public function index_del(){
           $model=Db::name('region');
           $id=input('id');
           if($model->where(array('parent_id'=>$id))->count()>0){
               return json(array('status'=>0,'msg'=>'There are subordinate regions, cannot be deleted'));
           }
           if(Db::name('region')->where('id',$id)->delete()){
				return json(array('status'=>1,'msg'=>'deletion successful'));
           }else{
               return json(array('status'=>0,'msg'=>'deletion failed'));
           }
   }
   /**
    * object to array
    */
   public function ObjtoArr($obj){
   	if(!$obj){
   		return "";
   	}
   	$json=json_encode($obj);
   	return json_decode($json,true);
   }
}