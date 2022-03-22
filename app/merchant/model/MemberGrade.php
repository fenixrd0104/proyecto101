<?php
namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class MemberGrade extends Model
{
	protected $autoWriteTimestamp = true;
	protected $name = 'member_grade';
	/**
	 * level added
	 * @param array $param
	 */
	 public function addGrade($param){
	 $result = $this->allowField(true)->save($param);
	 if($result){
	 return array('status'=>1,'msg'=>'Added successfully');
	 }else{
	 return array('status'=>0,'msg'=>'Add failed');
	 }
	 }
	 Be
	 Public function updataGrade($param){
	 $result = $this->validate('UserGradeValidate')->isUpdate(true)->allowField(true)->save($param);
	 if($result){
	 return array('status'=>1,'msg'=>'update successful');
		}else{
			return array('status'=>0,'msg'=>$this->getError());
		}
	}
}