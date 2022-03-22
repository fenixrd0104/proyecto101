<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class AdModel extends Model
{
    protected $name = 'ad';

    /**
     * Get list information based on conditions
     * @param $where
     * @param $Nowpage
     * @param $limits
     */
    public function getAdAll($map, $Nowpage, $limits)
    {
        return $this->field('think_ad.*,name,width,height')->join('think_ad_position', 'think_ad.ad_position_id = think_ad_position.id')->where($map)->page($Nowpage,$limits)->order('orderby desc')->select();     
    }

    /**
     * Insert information
     * @param $param
     */
    public function insertAd($param)
    {
        try{
            $result = $this->validate('AdValidate')->allowField(true)->save($param);
            if(false === $result){       
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Add ad successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edit information
     * @param $param
     */
    public function editAd($param)
    {
        try{

            $result = $this->validate('AdValidate')->allowField(true)->save($param, ['id' => $param['id']]);

            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'Edit ad success'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Get a message by id
     * @param $id
     */
    public function getOneAd($id)
    {
        return $this->where('id', $id)->find();
    }


    /**
     * delete message
     * @param $id
     */
    public function delAd($id)
    {
        try{
            $map['closed']=1;
            $this->save($map, ['id' => $id]);
            return ['code' => 1, 'data' => '', 'msg' => 'Ad deleted successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}