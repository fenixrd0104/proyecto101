<?php

namespace app\merchant\controller;
use app\merchant\model\ArticleModel;
use app\merchant\model\ArticleCateModel;
use think\facade\Db;
use think\exception\ValidateException;

class Article extends Base
{

    /**
     * [index article list]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function index(){

        $key = input('keyWords');
        $map = [];
        if($key&&$key!==""){
            $map = [
                ['title', 'like', "%".$key."%"],
            ];
        }
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count = Db::name('article')->where($map)->count();//Calculate the total pages
        $lists = Db::name('article')
             ->alias('a')
             ->join('article_cate ac ','a.cate_id = ac.id')
             ->where($map)
             ->field('a.title,a.photo,a.id,a.views,a.status,a.create_time,a.update_time,a.is_tui,ac.name')
             ->page($Nowpage, $limits)
             ->select();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$lists]]);
    }
    /**
     * [add_article add article]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function addArticle()
    {
        if(request()->isPost()){
            $param = input('post.');

            try {
                $this->validate($param,'ArticleValidate');
            } catch (ValidateException $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $article = new ArticleModel();
            $flag = $article->insertArticle($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $cate = new ArticleCateModel();
        return json(['code'=>1,'data'=>['cate'=>$cate->getAllCate()],'msg'=>'']);


    }
    /**
     * [edit_article edit article]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function editArticle()
    {
        $article = new ArticleModel();
        if(request()->isPost()){
            $param = input('post.');
            try {
                $this->validate($param,'ArticleValidate');
            } catch (ValidateException $e) {
                // validation failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $flag = $article->updateArticle($param);
            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        $id = input('param.id');
        $cate = new ArticleCateModel();
        return json(['code'=>1,'data'=>['cate'=>$cate->getAllCate(),'info'=>$article->getOneArticle($id)],'msg'=>'']);
    }
    /**
     * [del_article delete article]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function delArticle()
    {
        $id = input('param.id');
        $flag = Db::name('article')
              ->where('id', $id)
              ->delete();
        if($flag){
            return json(['code' => 1, 'data' => '', 'msg' => 'deletion successful']);
        }else{
            return json(['code' => 0, 'data' => '', 'msg' => 'deletion failed']);
        }

    }
    /**
     * [article_state article state]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function stateArticle()
    {
        $id=input('param.id');
        $status = Db::name('article')->where(array('id'=>$id))->value('status');//Judging the current status
        if($status==1)
        {
            $flag = Db::name('article')->where(array('id'=>$id))->update(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('article')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }
    
    }
    public function tuiArticle()
    {
        $id=input('param.id');
        $status = Db::name('article')->where(array('id'=>$id))->value('is_tui');//Judging the current status
        if($status==1)
        {
            $flag = Db::name('article')->where(array('id'=>$id))->update(['is_tui'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('article')->where(array('id'=>$id))->update(['is_tui'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }

    }



    //*********************************************Classification management*********************************************//

    /**
 * [index_cate category list]
 * @return [type] [description]
 * @author [Tian Jianlong] [864491238@qq.com]
 */
    public function indexCate(){
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count = Db::name('article_cate')->count();
        $list = Db::name('article_cate')
            ->order('orderby asc,id desc')->page($Nowpage,$limits)
            ->select();

        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list]]);

    }


    /**
	 * [add_cate add category]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function addCate()
    {
        if(request()->isAjax()){
            $param = input('post.');
            $ArticleCateModel = new ArticleCateModel();
            return json($ArticleCateModel->insertCate($param));
        }

    }


    /**
    * [edit_cate edit category]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */

     public function edit(){
         if (request()->isAjax()) {

             $ArticleCateModel =  new ArticleCateModel();
             $param = input('post.');
             return json($ArticleCateModel->editCate($param));
         }
         $where['id'] = input('param.id');
         $list = Db::name('article_cate')->where($where)->field('id,name,orderby,status')->find();
         return json(['code' => 1, 'data' => $list]);

     }



    /**
     * [del_cate delete category]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function delCate()
    {
        if (request()->isAjax()) {
            $id = input('param.id');
            //If there are articles under the category, do not delete them
            if(Db::name('article')->where(['cate_id'=>$id])->count()){
                return json(['code'=>1,'data'=>[],'msg'=>'There are articles under the category that cannot be deleted']);
            }
            $flag = Db::name('article_cate')
                  ->where('id', $id)
                  ->delete();
            if($flag){
                return json(['code' => 1, 'data' => '', 'msg' => 'deletion successful']);
            }else{
                return json(['code' => 0, 'data' => '', 'msg' => 'deletion failed']);
            }

        }
    }


    /**
     * [cate_state category state]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */
    public function cateState()
    {
        $id=input('param.id');
        $status = Db::name('article_cate')->where(array('id'=>$id))->value('status');//Judging the current status
        if($status==1)
        {
            $flag = Db::name('article_cate')->where(array('id'=>$id))->update(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }
        else
        {
            $flag = Db::name('article_cate')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }

    }  

}