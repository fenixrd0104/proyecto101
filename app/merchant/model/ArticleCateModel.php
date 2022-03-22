<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class ArticleCateModel extends Model
{
    protected $name = 'article_cate';
    
    // Enable automatic writing of timestamps
    protected $autoWriteTimestamp = true;


    /**
     * [getAllCate get all categories]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getAllCate()
    {
        return $this->order('id asc')->select();
    }


    /**
     * [insertCate add category]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function insertCate($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'category added successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [editMenu edit category]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function editCate($param)
    {
        try{
            $result = $this->where(['id' => $param['id']])->update($param);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => 'category editing successful'];
            }
        }catch(PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneMenu gets a piece of information based on the category id]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getOneCate($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delMenu delete category]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function delCate($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'category deleted successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

}