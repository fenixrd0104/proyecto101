<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class AuctionGoods extends Model
{
    
    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;

    /**
     * Get user list information based on search criteria
     * @author [
     */
    public function getAuctionGoodsWhere($map, $Nowpage, $limits)
    {

        return $this->where($map)->page($Nowpage, $limits)->order('id desc')->select();
    }
    
    
    /**

     */
    public function insertAuctionGoods($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){             
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Added successfully'];
            }
        }catch(\PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * 
     */
    public function updateAuctionGoods($param)
    {
        try{
            $result = $this->where(['id' => $param['id']])->update($param);
            if(false === $result){          
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Successful operation'];
            }
        }catch(\PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**

     */
    public function getOneAuctionGoods($id)
    {
        return $this->where('id', $id)->find();
    }

}