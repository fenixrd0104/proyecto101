<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class RegionModel extends Model
{
     protected $name = 'region';  
     public function treeList(){
     	$list=$this->field('id,name,level')->where(array('parent_id'=>0))->order('sort asc,id asc')->select();
     	foreach ($list as $k=>$v){
     		if($this->where(array('parent_id'=>$v->id))->count()){
     			$list[$k]['child']=$this->field('id,name,level')->where(array('parent_id'=>$v->id))->order('sort asc')->select();
     			$list[$k]['count']=$this->field('id,name,level')->where(array('parent_id'=>$v->id))->order('sort asc')->count();
     		}else{
     			$list[$k]['child']='';
     		}
     	}
     	return $list;
     }
     /**
      *通过id获取地区 
      */
     public function getOneRegion($id){
     	 return $this->find($id);
     }
     /**
     * Get the number of regions by condition
     * @param $pid
     * @param $name
     */
          public function getReionCount($pid,$name){
          return $this->where(array('parent_id'=>$pid,'name'=>$name))->count();
          }
          /**
           * Get region
           */
          public function getArea($id){
          $row=$this->find($id);
          $name='';
          if($row){
          $name=$row['name'];
          $cname=$this->getArea($row['parent_id']);
          if($cname){
          $cname=$cname.'->';
          }
          $name=$cname.$name;
          return $name;
          }else{
          return $name;
          }
          }
          /**
           * Get a list of sub-regions
      */
     public function getsubList($id){
     	 return $this->field('id,name,level,parent_id,"" as child')->where(array('parent_id'=>$id))->select();
     }
}