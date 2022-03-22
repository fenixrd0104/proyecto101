<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class ShopListsModel extends Model
{

    protected $name = 'shop_lists';
    protected $autoWriteTimestamp = true;
    public function forUser()
    {
        return $this->hasOne('UserModel', 'id', 'uid');
    }

    /**
     * 根据搜索条件获取用户列表信息
     */
    public function getListByWhere($map, $Nowpage, $limits)
    {
        return $this
            ->where($map)
            ->withAttr('uid', function($value, $data) {
                return Db::name('member')->where('id',$value)->value('nickname');
            })
            ->page($Nowpage, $limits)
            ->order('id desc')
            ->select();
    }

    /**
     * 根据搜索条件获取所有的用户数量
     * @param $where
     */
    public function getAllCount($where)
    {
        return $this->where($where)->count();
    }

    /**
     * 插入分店
     * @param $param
     */
    public function insertShop($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){            
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'分店【'.$param['name'].'】添加成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '添加分店成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function editSubshop($param,$map)
    {
        try{
            $result =  $this->update($param, $map);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                writelog(session('uid'),session('username'),'分店【'.$param['name'].'】编辑成功',1);
                return ['code' => 1, 'data' => '', 'msg' => '编辑分店成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getOneSubshop($id)
    {
        return $this->where('id', $id)->find();
    }

    public function delSubShop($id)
    {
        try{

            $this->where('id', $id)->delete();

            writelog(session('uid'),session('username'),'用户【'.session('username').'】删除分店成功(ID='.$id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => '删除分店成功'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getKeyVal($map){
        return $this->where($map)->column('name','id');
    }



}