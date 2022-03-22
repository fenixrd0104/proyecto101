<?php
/**
 * Created by PhpStorm.
 * User: qiyu
 * Date: 2017-09-29
 * Time: 14:06
 */
namespace app\merchant\model;
use app\common\model\IntegralLog;
use app\common\model\MoneyLog;
use app\common\model\TianyuanLog;
use think\Model;
use think\facade\Db;

class MemberModel extends Model
{
    protected $name = 'member';
    protected $autoWriteTimestamp = true; // Enable automatic write timestamp

    /**
     * Get user list information based on search criteria
     */
    public function getMemberByWhere($map, $Nowpage, $limits)
    {
        return $this->field('think_member.*,group_name')->join('think_member_group', 'think_member.group_id = think_member_group.id')
            ->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }

    /**
     * Get the number of all users based on search criteria
     * @param $where
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }


    /**
     * insert information
     */
    public function insertMember($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                //Add the number of recharge records to the record table
                if(isset($param['money']) && $param['money']>0){
                    MoneyLog::operate($this->id,$param['money'],2,1,'Administrator manual processing',session('uid'));
                }
                //add points
                if(isset($param['integral']) && $param['integral']>0){
                    IntegralLog::operate($this->id,$param['integral'],2,1,'Administrator manual processing',session('uid'));
                }
                //add points
                if(isset($param['tydou']) && $param['tydou']>0){
                    TianyuanLog::operate($this->id,$param['tydou'],2,'manually handled by the administrator',session('uid'));
                }
                return ['code' => 1, 'data' =>$this->id, 'msg' => 'Added successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edit information
     * @param $param
     */
    public function editMember($param)
    {
        try{
            $info=$this->find($param['id']);

            $money=$param['money']-$info->money;
            $integral=$param['integral']-$info->integral;
            $tydou=$param['tydou']-$info->tydou;
            $group_id=0;
            if($param['group_id']>$info->group_id){
                $group_id=$param['group_id'];
                $param['group_id']=$info->group_id;
            }
            $result =  $this->where( ['id' => $param['id']])->update($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                if($money != 0){
                   MoneyLog::operate($param['id'],$money,2,1,'Administrator manual processing',session('uid'));
                }
                if($integral != 0){
                    IntegralLog::operate($param['id'],$integral,2,1,'Administrator manual processing',session('uid'));
                }

                if($tydou != 0){
                    TianyuanLog::operate($param['id'],$tydou,2,'manually handled by the administrator',session('uid'));
                }
                if($group_id){
                    event('MemberGroupChange',['uid'=>$param['id'],'group_id'=>$group_id]);
                }

                return ['code' => 1, 'data' => '', 'msg' => 'edited successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Get role information based on administrator id
     * @param $id
     */
    public function getOneMember($id)
    {
        return $this->where('id', $id)->find();
    }

    public function delMember($id)
    {
        try{
            $map['closed']=1;
            $map['update_time']=time();
            $this->where(['id' => $id])->update($map);
            return ['code' => 1, 'data' => '', 'msg' => 'deletion successful'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


}