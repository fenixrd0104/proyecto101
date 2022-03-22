<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class Assignment extends Model
{
    protected $name = 'assignment';
    
    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;


    /**
     * Get user list information based on search criteria
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getArticleByWhere($map, $Nowpage, $limits)
    {

        return $this->field('article.*,name')->join('article_cate', 'article.cate_id = article_cate.id')->where($map)->page($Nowpage, $limits) ->order('id desc')->select();
    }
    
    
    /**
     * [insertArticle add article]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function insertArticle($param)
    {
        try{
            $result = $this->save($param);
            if(false === $result){             
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
            return ['code' => 1, 'data' => '', 'msg' => 'task added successfully'];
            }
        }catch(PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [updateArticle edit article]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function updateArticle($param)
    {
        try{
            $result = $this->where(['id' => $param['id']])->update($param);
            if(false === $result){          
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                return ['code' => 1, 'data' => '', 'msg' => '文章编辑成功'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }



    /**
     * [getOneArticle gets a piece of information based on the article id]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function getOneArticle($id)
    {
        return $this->where('id', $id)->find();
    }



    /**
     * [delArticle delete article]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function delArticle($id)
    {
        try{
            $this->where('id', $id)->delete();
            return ['code' => 1, 'data' => '', 'msg' => 'The article was deleted successfully'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }







}