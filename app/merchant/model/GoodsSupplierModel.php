<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class GoodsSupplierModel extends Model
{
    protected $name = 'goods_supplier';
    protected $pk = 'supplier_id';
    protected $autoWriteTimestamp = true;
    public function forShop()
    {
        return $this->hasOne('ShopListsModel', 'id', 'supplier_for_shop');
    }
    public function getByWhere($map, $Nowpage, $limits)
    {
        return $this->where($map)->page($Nowpage, $limits)->order('supplier_id desc')->select();
    }

    public function getAll($where)
    {
        return $this->where($where)->count();
    }

    public function insertOne($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){            
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
               writelog(session('uid'),session('username'),'supplier['.$param['supplier_name'].'] added successfully',1);
                return ['code' => 1, 'data' => '', 'msg' => 'Add supplier successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function editOne($param)
    {
        try{
            $result =  $this->where([$this->pk => $param[$this->pk]])->save($param);
            if(false === $result){            
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
              writelog(session('uid'),session('username'),'supplier['.$param['supplier_name'].']edited successfully',1);
                return ['code' => 1, 'data' => '', 'msg' => 'edit supplier successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function getOne($id)
    {
        return $this->where('supplier_id', $id)->find();
    }


    public function delOne($supplier_id)
    {
        try{

            $this->where('supplier_id', $supplier_id)->update(['supplier_status'=>2]);

            writelog(session('uid'),session('username'),'user['.session('username').'] delete supplier(ID='.$supplier_id.')',1);
            return ['code' => 1, 'data' => '', 'msg' => 'Delete supplier successfully'];

        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    public function getKeyVal(){
        return $lists = $this->where(['supplier_status'=>1])->column('supplier_name','supplier_id');
    }
}