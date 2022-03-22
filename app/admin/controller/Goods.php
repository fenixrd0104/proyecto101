<?php

namespace app\admin\controller;

use app\admin\model\GoodsSpecItem;
use app\admin\model\GoodsSupplierModel;
use app\admin\model\SpecGoodsModel;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use app\admin\model\Goods as GoodsModel;
use app\admin\model\GoodsType;
use app\admin\model\GoodsAttribute;
use app\admin\model\GoodsSpec;
use app\admin\model\GoodsCategory;
use app\admin\model\GoodsBrand;
use think\Request;

class Goods extends Base
{
    protected $request;
    protected $backUrl;

    public function __construct(Request $request,App $app)
    {
        parent::__construct($app);
        header("Content-type:text/html;charset=utf-8");
        $this->request = $request;
    }

    /**
     *menu classification
     */
    public function indexCate(){
        $Nowpage = input('get.page') ? input('get.page'):1;
        $limits = input('get.limit',15);
        $count=Db::name('goods_catetype')->where('status','<>',2)->count();
        $list=Db::name('goods_catetype')->where('status','<>',2)->order('orderby asc')->page($Nowpage,$limits)
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
            // return json(['code'=>1,'data'=>$param]);
            $param['create_time']=time();
            Db::name('goods_catetype')->save($param);
            return json(['code'=>1,'data'=>[],'msg'=>'Successful operation']);
        }

    }
     /**
     * [edit_cate edit category]
     * @return [type] [description]
     * @author [Tian Jianlong] [864491238@qq.com]
     */

     public function edit(){
         if (request()->isAjax()) {
             $param = input('post.');
             $param['update_time']=time();
             Db::name('goods_catetype')->save($param);
             return json(['code'=>1,'data'=>[],'msg'=>'Edited successfully']);
         }
         $where['id'] = input('param.id');
         $list = Db::name('goods_catetype')->where($where)->field('id,name,status')->find();
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
            $find = Db::name('goods_type')->where('pid',$id)->find();
            if($find){
			return json(['code' => 0, 'data' => '', 'msg' => 'This menu is used by commodity types and cannot be deleted']);
            }
            $flag = Db::name('goods_catetype')
                  ->where('id', $id)
                  ->update(['status'=>2]);
            if($flag){
                return json(['code' => 1, 'data' => '', 'msg' => 'Delete successful']);
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
        $status = Db::name('goods_catetype')->where(array('id'=>$id))->value('status');//Judge the current status
        if($status==1)
        {
            $flag = Db::name('goods_catetype')->where(array('id'=>$id))->update(['status'=>0]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'enabled']);
        }
        else
        {
            $flag = Db::name('goods_catetype')->where(array('id'=>$id))->update(['status'=>1]);
            return json(['code' => 1, 'data' => $flag['data'], 'msg' => 'banned']);
        }

    }  


    /**
    * Commodity management
     */
    #list
    public function index()
    {
        $data = input('get.');
        //Assemble filter conditions
        $map = [];
        $map[]=['is_delete','=',0];
        $map[]=['status','=',1];

//        //Store
//        if($this->shopId){
//            $map['shop_id']=$this->shopId;
//        }
        //Sort condition
        $order = 'id-0';
        $GoodsCategory = new GoodsCategory();
        if ($data) {
            // if (isset($data['cat_id']) && !empty($data['cat_id'])) {

            //     $catSon = $GoodsCategory->getCategorySon($data['cat_id']);
            //     $cats = [];
            //     foreach ($catSon as $v) {
            //         $cats[] = $v->id;
            //     }
            //     $cats[] = $data['cat_id'];
            //     $map[] = ['cat_id','in', $cats];
            // }
            if (isset($data['top_cate']) && !empty($data['top_cate'])) {
                $map[] = ['top_cate','=',$data['top_cate']];
            }
            if (isset($data['caidan_id']) && !empty($data['caidan_id'])) {
                $map[] = ['caidan_id','=',$data['caidan_id']];
            }

            if (isset($data['brand_id']) && !empty($data['brand_id'])) {
                $map[] = ['brand_id','=',$data['brand_id']];
            }
            if (isset($data['is_on_sale']) && $data['is_on_sale'] != '') {
                $map[] = ['is_on_sale','=',$data['is_on_sale']];
            }
            if (isset($data['recom_type']) && !empty($data['recom_type'])) {
                $map[] = [$data['recom_type'],'=',1];
            }
            if (isset($data['keywords']) && !empty($data['keywords'])) {
                $map[] = ['goods_name','like', '%' . $data['keywords'] . '%'];
            }
            //Sort condition
            if (isset($data['order']) && !empty($data['order'])) {
                $order = $data['order'];
            }
        }
			//sort
        $order = explode('-', $order);
        $orderStr = $order[1] == 1 ? "$order[0] asc" : "$order[0] desc";


        $limits = input('get.limit',15);
        $list = GoodsModel::order($orderStr)->where($map)->paginate($limits, false, ['query' => $data]);
        //$catetype = Db::name('goods_catetype')->where('status',0)->order('orderby asc')->select();
        $catetype = [];
        $brandList = GoodsBrand::order('order asc')->select();
        $lists = $list->toArray();
        $shop_list=Db::name('shop_lists')->column('name','id');
        foreach ($lists['data'] as $key =>&$values){
                $values['shop_name']=$shop_list[$values['shop_id']];
                $goods_type_pid = Db::name('goods_type')->where('id',$values['goods_type'])->field('pid,name')->find();
                $values['goods_type'] = $goods_type_pid['name'];
                $caidan = Db::name('goods_catetype')->where('id',$values['caidan_id'])->value('name');
                $values['caidan_name'] = $caidan;
        }
        return json(['code'=>1,'data'=>['lists' => $lists['data'],'count'=>$lists['total'], 'catetype' => $catetype, 'brandList' => $brandList, 'data' => $data, 'order_name' => $order[0], 'order_sort' => $order[1],'cate'=>$GoodsCategory->getKeyVal()],'msg'=>'']);

    }
    #Increase
    public function addGoods(GoodsCategory $category, GoodsModel $goods)
    {

        if ($this->request->isPost()) {
            $data = input('post.');
			$data['goods_content'] = input('post.goods_content','',null);
            $data['shop_id']=$this->shopId;
            //Store
            // if(!$this->shopId){
            //     $data['shop_id']=config('config.shop_default_manage');
            // }

            $data['shop_id'] = $this->shopId;
            try {
               $this->validate($data,'GoodsValidate');
            } catch (ValidateException $e) {
               //Authentication failed output error message
               return json(['code' => 0, 'msg' => $e->getError()]);
            }
            // return json(['code' => 0, 'data'=>$data['item'],'msg' => '编辑成功']);

            if($data['caidan_id'] == 1 || $data['caidan_id'] == 2){
                if($data['shop_price'] < config('config.jl_goodsprice'.$data['caidan_id'])){
							return json(['code' => 0, 'msg' => 'The price of this type of goods must be greater than or equal to'.config('config.jl_goodsprice'.$data['caidan_id'])]);
                }
                if(isset($data['item'])){
                    foreach ($data['item'] as $k => $v) {
                        if($v['shop_price'] < config('config.jl_goodsprice'.$data['caidan_id'])){
                            return json(['code' => 0, 'msg' => 'The price of each specification of this type of product must be greater than or equal to'.config('config.jl_goodsprice'.$data['caidan_id'])]);
                        }
                    }
                }
            }

            if(isset($data['item'])){
                //Edit product region keywords
                $spec_key_type='';
                foreach ($data['item'] as $k => $v) {
                    //TODOO:add field
                    $keyss=Db::name('goods_spec_item')->where('id',$k)->value('item');
                    if(empty($spec_key_type)){
                        $spec_key_type.=$keyss;
                    }else{
                        $spec_key_type.='|'.$keyss;
                    }
                }
                Db::name('goods')->where('id', $data['id'])->update(['spec_key_type' => $spec_key_type]);
            }
            $goods->data($data, true);
            $goods->save();
            return json(['code' => 1, 'msg' => 'Added successfully']);
        }
        $typeList = GoodsType::select();
        $categoryList = $category->getCategorySon(0,0);
        $brandList     = GoodsBrand::order('order asc, id desc')->select();
        $fid           = Db::name('freight_template')->field('id, name')->select();
        $caidan = Db::name('goods_catetype')->where('status',0)->order('orderby asc')->select();
        //supplier
       $GoodsSupplierModel = new GoodsSupplierModel();
       $supplierLists =  $GoodsSupplierModel->getKeyVal();
        return json(['code'=>1,'data'=>['typeList' => $typeList,'caidan'=>$caidan, 'categoryList' => $categoryList, 'brandList' => $brandList, 'fid' => $fid,'supplierLists'=>$supplierLists],'msg'=>'']);
    }
    #edit
    public function editGoods(GoodsCategory $category, GoodsModel $goods, $id)
    {
        $map['id']=$id;
        if($this->shopId){
            $map['shop_id']=$this->shopId;
        }
        if ($this->request->isPost()) {

            $data = input('post.');
			$data['goods_content'] = input('post.goods_content','',null);
			
            try {
                $this->validate($data,'GoodsValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            if($data['caidan_id'] == 1 || $data['caidan_id'] == 2){
                if($data['shop_price'] < config('config.jl_goodsprice'.$data['caidan_id'])){
                    return json(['code' => 0, 'msg' => 'The price of this type of product must be greater than or equal to'.config('config.jl_goodsprice'.$data['caidan_id'])]);
                }
                if(isset($data['item'])){
                    foreach ($data['item'] as $k => $v) {
                        if($v['shop_price'] < config('config.jl_goodsprice'.$data['caidan_id'])){
                            return json(['code' => 0, 'msg' => 'The price of each specification of this type of product must be greater than or equal to'.config('config.jl_goodsprice'.$data['caidan_id'])]);
                        }
                    }
                }
            }
            if(isset($data['item'])){
                //Edit product region keywords
                $spec_key_type='';
                foreach ($data['item'] as $k => $v) {
                    //TODOO:add field
                    $keyss=Db::name('goods_spec_item')->where('id',$k)->value('item');
                    if(empty($spec_key_type)){
                        $spec_key_type.=$keyss;
                    }else{
                        $spec_key_type.='|'.$keyss;
                    }
                }
                Db::name('goods')->where('id', $data['id'])->update(['spec_key_type' => $spec_key_type]);
            }
            $goods =  $goods::where($map)->find();
            try {
                $goods->save($data);
            } catch (\Exception $e) {
               // Authentication failed output error message
               return json(['code' => 0, 'msg' => $e->getMessage()]);
            }

            return json(['code' => 1, 'msg' => 'Edited successfully']);
        }
        $info = $goods::where($map)->find();
        if (!$info) {
            $this->error('No such product found');
        }
        //分类 cat_id_1 cat_id_2 cat_id_3
        //找cat_idthe family tree
        $info->cat_id_path = $category->where('id', $info->cat_id)->value('parent_id_path');
        $info->cat_id_path = '_' . $info->cat_id_path .'_';
        $info->extend_cat_id_path = $category->where('id', $info->extend_cat_id)->value('parent_id_path');
        $info->extend_cat_id_path = '_' . $info->extend_cat_id_path .'_';
        //image gallery
        $info->goods_images = Db::name('goods_images')->where('goods_id', $id)->order('id asc')->column('image_url');
        $caidan = Db::name('goods_catetype')->where('status',0)->select();
        $typeList = GoodsType::select();
        $categoryList  = $category->getCategorySon(0,0);
        $brandList     = GoodsBrand::order('order asc, id desc')->select();
        $fid           = Db::name('freight_template')->field('id, name')->select();

        //supplier
        $GoodsSupplierModel = new GoodsSupplierModel();
        $supplierLists =  $GoodsSupplierModel->getKeyVal();
        return json(['code'=>1,'data'=>['info' => $info, 'typeList' => $typeList, 'caidan'=>$caidan,'categoryList' => $categoryList, 'brandList' => $brandList, 'fid' => $fid,'supplierLists'=>$supplierLists],'msg'=>'']);
    }
    #View product barcode
    public function getBartList($goods_id){
       $goods_sn =  Db::name('goods')->where(['id'=>$goods_id])->value('goods_sn');
       $lists = Db::name('spec_goods')->field('spec_sku,spec_id,spec_name')->where(['goods_id'=>$goods_id])->select();
       $data = [];
       foreach ($lists as $v){
           $data[]=[
               'sn'=>$goods_sn,
               'sku'=>$v['spec_sku'],
               'spec_id'=>$v['spec_id'],
               'spec_name'=>$v['spec_name'],
           ];
       }
       return json(['code'=>0,'data'=>$data,'msg'=>'']);
    }

    //Get inventory of all specifications of an item
    public function goodsStockCount($goods_id){
        $spec_ids = Db::name('spec_goods')->where(['goods_id'=>$goods_id])->column('spec_name','spec_id');

        $data=[];
        foreach ($spec_ids as $k => $v){
            $data[$k]['spec_id']=$k;
            $data[$k]['name']=$v;
            $data[$k]['stock']=Db::name('goods_stock')->where(['goods_id'=>$goods_id,'spec_id'=>$k])->sum('stock');
        }
      return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    //Get the inventory of each store for a product specification
    public function goodsSpecForShop($spec_id){
       $data = Db::name('goods_stock')->field('think_shop_lists.name as shop_name,stock')->join('think_shop_lists','think_shop_lists.id=think_goods_stock.shop_id','left')->where(['spec_id'=>$spec_id])->select();
       return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }


    #delete
    public function delgoods($id)
    {
        Db::name('goods')->where('id',$id)->update(['status'=>0,'is_delete'=>1]);
        //$goods->destroy($id);
        return json(['code' => 1, 'msg' => 'successfully deleted']);
    }
    /**
     * Product Types
     */
    #list
    public function goodsType()
    {
        $page=input('param.page',1);
        $pid=input('param.pid');
        $limit=input('param.limit',10);
        $keywords=input('param.keywords','');
        
        $map=[];
        if($pid&&$pid!==""){
            $map[]=['t.pid','=',$pid];
        }
        if($keywords){
            $map[] = ['t.name','like','%'.$keywords.'%'];
        }
        //$catetype=Db::name('goods_catetype')->where('status',0)->field('id,name')->order('orderby asc')->select();
//        return json($catetype);
        $list = Db::name('goods_type')->alias('t')->join('goods_catetype ct','t.pid=ct.id')->where($map)->order('sort asc')->field('t.*,ct.name as names,ct.top_cate')->page($page,$limit)->select();
        $count = Db::name('goods_type')->alias('t')->join('goods_catetype ct','t.pid=ct.id')->where($map)->count();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list],'msg'=>'']);
    }
    public function goodsTypes()
    {
        $pid=input('param.pid');
        $map=[];
        if($pid&&$pid!==""){
            $map[]=['t.pid','=',$pid];
        }
        $list = Db::name('goods_type')->alias('t')->join('goods_catetype ct','t.pid=ct.id')->where($map)->order('sort asc')->field('t.*,ct.name as names')->select();
        $count = Db::name('goods_type')->count();
        return json(['code'=>1,'data'=>['count'=>$count,'lists'=>$list],'msg'=>'']);
    }
    #Add to
    public function addGoodsType(GoodsType $type)
    {
        if ($this->request->isPost()) {
            if (!$name = input('post.name')) {
                return json(['code' => 0, 'msg' => 'Type name is not allowed to be empty']);
            }
            $type->name = $name;
            $type->pid = input('post.pid');
            $type->image = input('post.image');
            $type->save();
            return json(['code' => 1,'data'=>[], 'msg' => 'Added successfully']);
        }

    }
    public function editSortType(){
        if(request()->post()){
            $param=input('post.');
            $res=Db::name('goods_type')->save($param);
            if($res){
				return json(['code' => 1,'data'=>[], 'msg' => 'operation successful']);
            }else{
                return json(['code' => 0,'data'=>[], 'msg' => 'operation failed']);
            }
        }
    }
    #edit
    public function editGoodsType(GoodsType $type, $id)
    {
        if ($this->request->isPost()) {
            if (!$name = input('post.name')) {
                return json(['code' => 0, 'msg' => 'Type name is not allowed to be empty']);
            }
            $res = $type::find($id);
            $res->name = $name;
            $res->pid = input('post.pid');
            $res->image = input('post.image');
            $res->save();
            return json(['code' => 1, 'msg' => 'Edited successfully']);
        }
        $info = $type::find($id);
        if (!$info) {
            $this->error('no such type');
        }
        return view('', ['info' => $info]);
    }
    #delete
    public function delGoodsType(GoodsType $type, $id)
    {

        if (GoodsAttribute::where('type_id', $id)->find()) {
			return json(['code' => 0, 'msg' => 'There are attribute items under this product type, which cannot be deleted']);
        }
        if (GoodsSpec::where('type_id', $id)->find()) {
            return json(['code' => 0, 'msg' => 'There is a specification item under this product type, which cannot be deleted']);
        }
        $type->destroy($id);
        return json(['code' => 1, 'msg' => 'deletion successful']);
    }

    /**
     * Product attributes
     */
    #list
    public function goodsAttribute($type_id = 0)
    {
        $keywords = input('keywords');
        $map = [];
        if($keywords){
            $map[] = ['name','like','%'.$keywords.'%'];
        }
        if ($type_id){
            $list     = GoodsAttribute::where('type_id', $type_id)->where($map)->order('order asc, id desc')->paginate(10);
        } else {
            $list     = GoodsAttribute::order('order asc, id desc')->where($map)->paginate(10);
        }
        $typeList = GoodsType::select();
			$input_type = array(0 => 'manual input', 1 => 'select from the list', 2 => 'multi-line text box');

        foreach ($list as $v){
            $v->type_name=$v->goodsType()->value('name');
            $goods_type_pid = Db::name('goods_type')->where('id',$v->type_id)->value('pid');
            $caidan = Db::name('goods_catetype')->where('id',$goods_type_pid)->field('id,name,top_cate')->find();
            $v->caidan_id = $caidan['id'];
            $v->caidan_name = $caidan['name'];
            $v->top_cate = $caidan['top_cate'];
        }
        $res =$list->toArray();
        return json(['code'=>1,'data'=>['count' => $res['total'],'lists'=>$res['data'], 'typeList' => $typeList, 'type_id' => $type_id, 'input_type' =>  new \ArrayObject($input_type)],'msg'=>'']);

    }
    #Add to
    public function addGoodsAttribute(GoodsAttribute $attribute, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');

            try {
                $this->validate($data,'GoodsAttributeValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $attribute->data($data);
            $attribute->save();
            return json(['code' => 1, 'msg' => 'Added successfully']);
        }
        $typeList = GoodsType::select();
        $input_type = ["0" => 'Manual input', "1" => 'Select from the list', "2" => 'Multi-line text box'];

        return json(['code'=>1,'data'=>['typeList' => $typeList, 'type_id' => $type_id],'input_type' => new \ArrayObject($input_type),'msg'=>'']);
    }
    #edit
    public function editGoodsAttribute(GoodsAttribute $attribute,  $id, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
                $this->validate($data,'GoodsAttributeValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }

            $type = $attribute::find($id);
            $type->save($data);
            return json(['code' => 1, 'msg' => 'Edited successfully']);
        }
        $info = $attribute::find($id);
        if (!$info) {
            $this->error('Check no such property');
        }
        if($info['input_type'] == 1){
            $info['values']=explode('|',$info['values']);
        }
        $typeList = GoodsType::select();
        return json(['code'=>1,'data'=>['info' => $info, 'typeList' => $typeList],'msg'=>'']);

    }
    #delete
    public function delGoodsAttribute(GoodsAttribute $attribute, $id)
    {
        $attribute->destroy($id);
        return json(['code' => 1, 'msg' => 'successfully deleted']);
    }

    /**
     * Product specifications
     */
    #list
    public function goodsSpec($type_id = 0)
    {
        $keywords = input('keywords');
        $map=[];
        if($keywords){
            $map[] = ['name','like','%'.$keywords.'%'];
        }
        if ($type_id){
            $list     = GoodsSpec::where('type_id', $type_id)->where($map)->order('order asc, id desc')->paginate(10);
        } else {
            $list     = GoodsSpec::order('order asc, id desc')->where($map)->paginate(10);
        }
        $typeList = GoodsType::select();
        foreach ($list as $v){
            $v->values=$v->goodsSpecItem()->order('id asc')->select();
            //$v->type_name=$v->goodsType()->value('name');
            $goods_type_pid = Db::name('goods_type')->where('id',$v['type_id'])->field('pid,name')->find();
            $v->type_name = $goods_type_pid['name'];

            $caidan = Db::name('goods_catetype')->where('id',$goods_type_pid['pid'])->value('name');
            $v->caidan_name = $caidan;
    }
        $res = $list->toArray();
        return json(['code'=>1,'data'=>['count'=>$res['total'],'lists' => $res['data'], 'typeList' => $typeList, 'type_id' => $type_id],'msg'=>'']);

    }


    #Add to
    public function addGoodsSpec(GoodsSpec $spec, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
                $this->validate($data,'GoodsSpecValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $items = $data['items'];
            unset($data['items']);
            $spec->data($data);
            $spec->save();
            $lastID = $spec->id;
            $spec->afterSave($lastID, $items);
            return json(['code' => 1, 'msg' => 'Added successfully']);
        }
        $typeList = GoodsType::select();
        return json(['code'=>1,'data'=>['typeList' => $typeList, 'type_id' => $type_id],'msg'=>'']);

    }
    #edit
    public function editGoodsSpec(GoodsSpec $spec,  $id, $type_id = 0)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
                $this->validate($data,'GoodsSpecValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            $items = $data['items'];
            unset($data['items']);
            $spec = $spec->find($id);
            $spec->save($data);
            $spec->afterEdit($id, $items);
            return json(['code' => 1, 'msg' => 'Edited successfully']);
        }
        $info = $spec::find($id);
        if (!$info) {
            $this->error('Check out this specification');
        }
        $goods_type_pid = Db::name('goods_type')->where('id',$info->type_id)->value('pid');
        //dump($goods_type_pid);exit;
        $goods_type_pid = Db::name('goods_catetype')->where('id',$goods_type_pid)->field('id,top_cate')->find();
        $info->caidan_id = $goods_type_pid['id'];
        $info->top_cate = $goods_type_pid['top_cate'];

        $typeList = GoodsType::select();
        $info->goodsSpecItem=$info->goodsSpecItem()->order('id asc')->select();
        return json(['code'=>1,'data'=>['typeList' => $typeList, 'info' => $info],'msg'=>'']);
    }
    #delete
    public function delGoodsSpec(GoodsSpec $spec, $id)
    {
        $goods = Db::name('goods')->where('status',1)->column('id');
        $goods = array_values($goods);

        $spec_goods = Db::name('spec_goods')->where('goods_id','in',$goods)->column('spec_key');
        $spec_goods = array_values($spec_goods);
        $spec_goods_val = [];
        foreach ($spec_goods as $k => $v) {
            $value = explode('_', $v);
            foreach ($value as $kk => $vv) {
                array_push($spec_goods_val, $vv);
            }
        }
        $goods_spec_item = Db::name('goods_spec_item')->where('spec_id',$id)->column('id');

        if(array_intersect($goods_spec_item,$spec_goods_val)){
            return json(['code'=>0,'data'=>[],'msg'=>'Specifications that the product is using cannot be deleted']);
        }
        // $tag = Db::name('goods')->where('status',1)->where(['spec_type'=>$id])->count();
        // if($tag){
        //     return json(['code'=>0,'data'=>[],'msg'=>'Specifications that the product is using cannot be deleted']);
        // }
        
        if (GoodsSpecItem::destroy(['spec_id'=> $id])) {
            $spec->destroy($id);
			return json(['code' => 1, 'msg' => 'deletion successful']);
        }else{
            return json(['code' => 0, 'msg' => 'There is a specification item under the product specification, which cannot be deleted']);
        }

    }
    /**
     * product brand
     */
    #list
    public function goodsBrand()
    {
        $list     = GoodsBrand::order('order asc, id desc')->paginate(10);
        $res=$list->toArray();
        return json(['code'=>1,'data'=>['count'=>$res['total'],'lists' => $res['data']],'msg'=>'']);

    }
    #Add to
    public function addGoodsBrand(GoodsCategory $category, GoodsBrand $brand)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
                $this->validate($data,'GoodsBrandValidate');
            } catch (ValidateException $e) {
                // Authentication failed output error message
                return json(['code' => 0, 'msg' => $e->getError()]);
            }
            unset($data['file']);
            $brand->data($data);
            $brand->save();
            return json(['code' => 1, 'msg' => 'Added successfully']);
        }
        $categoryList = $category->getCategorySon(0,0);
        return json(['code'=>1,'data'=>['categoryList' => $categoryList],'msg'=>'']);
    }
    #edit
    public function editGoodsBrand(GoodsCategory $category, GoodsBrand $brand, $id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
               $this->validate($data,'GoodsBrandValidate');
            } catch (ValidateException $e) {
               // Authentication failed output error message
               return json(['code' => 0, 'msg' => $e->getError()]);
            }
            unset($data['file']);
            $brand = $brand::find($id);
            $brand->save($data);
            return json(['code' => 1, 'msg' => 'Edited successfully']);
        }
        $info = $brand::find($id);
        if (!$info) {
            $this->error('Check no such brand');
        }
        $categoryList = $category->getCategorySon(0,0);
        return json(['code'=>1,'data'=>['categoryList' => $categoryList, 'info' => $info],'msg'=>'']);
    }
    #delete
    public function delGoodsBrand(GoodsBrand $brand, $id)
    {
        $goods = Db::name('goods')->where('status',1)->where('brand_id',$id)->find();
        if($goods){
            return json(['code' => 0, 'msg' => 'A product is using this brand and cannot be deleted']);
        }
        $brand->destroy($id);
        return json(['code' => 1, 'msg' => 'successfully deleted']);
    }

    /**
     * Categories
     */
    #list
    public function goodsCategory(GoodsCategory $category)
    {
        $list = $category->getCategorySon();

        return json(['code'=>1,'data'=>$list,'msg'=>'']);
    }
    #Add to
    public function addGoodsCategory(GoodsCategory $category)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
               $this->validate($data,'GoodsCategoryValidate');
            } catch (ValidateException $e) {
               // Authentication failed output error message
               return json(['code' => 0, 'msg' => $e->getError()]);
            }            
            $category->saveCategory($data);
            return json(['code' => 1, 'msg' => 'Added successfully']);
        }
        $categoryList = $category->getCategorySon(0,0);
        $typeList = GoodsType::select();
        return json(['code'=>1,'data'=>['categoryList' => $categoryList, 'typeList' => $typeList],'msg'=>'']);
    }
    #edit
    public function editGoodsCategory(GoodsCategory $category, $id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            try {
               $this->validate($data,'GoodsCategoryValidate');
            } catch (ValidateException $e) {
               // Authentication failed output error message
               return json(['code' => 0, 'msg' => $e->getError()]);
            }
		if ($id == $data['parent_id_1'] || $id == $data['parent_id_2']) {
                return json(['code' => 0, 'msg' => 'You cannot be your own top-level category']);
            }
            $category->saveCategory($data);
            return json(['code' => 1, 'msg' => 'edited successfully']);
        }
        $info = $category::find($id);
        if (!$info) {
            $this->error('No such category found');
        }

        #family tree
        $parent_id_path = explode('_', $info->parent_id_path);
        $info->topParentId = $info->level > 1 ? $parent_id_path[1] : 0;
        $info->secParentId = $info->level > 2 ? $parent_id_path[2] : 0;
        $categoryList = $category->getCategorySon(0,0);
        #bind property
        $info->spec_list = null;
        if ($info->spec_id_str) {
            $info->spec_list = GoodsSpec::select(explode(',', $info->spec_id_str));
            //var_dump($info->spec_list);die;
        }
        $typeList = GoodsType::select();
        return json(['code'=>1,'data'=>['categoryList' => $categoryList, 'info' => $info, 'typeList' => $typeList],'msg'=>'']);
    }
		#delete
    public function delGoodsCategory(GoodsCategory $category, $id)
    {
        if ($category::where('parent_id', $id)->find()) {
            return json(['code' => 0, 'msg' => 'This product category has sub-categories and cannot be deleted']);
        }
        if (GoodsModel::where('cat_id', $id)->find()) {
            return json(['code' => 0, 'msg' => 'There is a product under this product category and cannot be deleted']);
        }

        $category->destroy($id);
        return json(['code' => 1, 'msg' => 'deletion successful']);
    }

    public function goodsCategoryStatus(GoodsCategory $category,$id){
       $info =  $category->find($id);
       if($info->is_show == 1){
           $info->is_show = 0;
       }else{
           $info->is_show = 1;
       }
       if($info->save()){
			return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
       }
    }
    public function goodsCategorySort(GoodsCategory $category,$id,$sort){
        $info = $category->find($id);
        $info->order = $sort;
        if($info->save()){
            return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
        }
    }

    //Get products by category
    public function getCoodsByCategory($cid,$supplier_id=0)
    {
        $map['status']=1;
        $map['cat_id|extend_cat_id'] = $cid;
        if($supplier_id){
            $map['supplier_id'] = $supplier_id;
        }
        $GoodsModel = new GoodsModel();
        $res =  $GoodsModel->field('id,goods_sn,goods_name,spec_type,cost_price')->where($map)->paginate(8);
        foreach ($res as $v){
            $v['spec']=Db::name('spec_goods')->field('spec_id,spec_key,spec_name,spec_sku,goods_id,cost_price')->where(['goods_id'=>$v['id']])->select();
        }
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['count'=>$lists['total'],'lists'=>$lists['data']],'msg'=>'']);

    }

    //get classification tree
    public function getCategoryTree(GoodsCategory $category){
        $list = $category->getCategoryTree1(1);
        return json(['code'=>1,'data'=>$list,'msg'=>'']);
    }

    public function searchByGoodsNameOrGoodsSn($keywords,$supplier_id=0){
        //
        if($supplier_id){
            $map[] = ['supplier_id','=',$supplier_id];
        }
        $map[] = ['goods_name|goods_sn','like','%'.$keywords.'%'];
        $GoodsModel = new GoodsModel();
        $res =  $GoodsModel->field('id,goods_sn,goods_name,spec_type,cost_price')->where($map)->paginate(8);
        foreach ($res as $v){
            $v['spec']=Db::name('spec_goods')->field('spec_id,spec_key,spec_name,spec_sku,goods_id,cost_price')->where(['goods_id'=>$v['id']])->select();
        }
        $lists = $res->toArray();
        return json(['code'=>1,'data'=>['count'=>$lists['total'],'lists'=>$lists['data']],'msg'=>'']);
    }

    public function searchByGoodsSku($spec_sku,$supplier_id=0){
        $SpecGoodsModel = new SpecGoodsModel();
        $info =  $SpecGoodsModel->field('spec_id,spec_key,spec_name,spec_sku,goods_id,cost_price')->where('spec_sku',$spec_sku)->find();
        if($info){
            if($supplier_id){
                $map[] = ['supplier_id','=',$supplier_id];
            }
            $map[] = ['id','=',$info->goods_id];
            $GoodsModel = new GoodsModel();
            $res  =  $GoodsModel->field('id,goods_sn,goods_name,spec_type,cost_price')->where($map)->find();
            if($res){
                $res->spec=$info;
                return json(['code'=>1,'data'=>$res,'msg'=>'']);
            }else{
			return json(['code'=>0,'data'=>[],'msg'=>'The product information was not found']);
            }
        }else{
            return json(['code'=>0,'data'=>[],'msg'=>'The barcode information was not found']);
        }
    }



    /**
     * ajax get specs and attributes based on type_id
     */
    public function ajaxGetAttr(GoodsType $type, $type_id, $goods_id = 0)
    {
        $info      = $type->find($type_id);
        if(!$info){
            return json(['code'=>0,'data'=>[],'msg'=>'Specification not found']);
        }
        $items     = array();
        $items_pic = array();
        if ($goods_id) {
            $keys = Db::name('spec_goods')->where('goods_id', $goods_id)->column('spec_key');
            if ($keys) {
                foreach ($keys as $k => $v) {
                    $items = array_merge(explode('_', $v), $items);
                }

                $items = array_unique($items);
            }
            //find items pictures
            foreach ($items as $k => $v) {
                $items_pic[$v] = Db::name('spec_image')->where('goods_id', $goods_id)->where('spec_item_id', $v)->value('src');
            }
        }
        //Specification Template
        $spec    = $info->goodsSpec;
        $specTpl = '<tr class="long-td"><td colspan="2" style="text-align:left"><b>Product specifications</b></td></tr>';
        foreach ($spec as $k => $v) {
            $specTpl .= '<tr class="long-td "><td>'. $v->name .'：</td><td>';
            foreach ($v->GoodsSpecItem as $k2 => $v2) {
                $choosed = '';
                $pic     = '';
                if (in_array($v2->id, $items)) {
                    $choosed = 'choosed';
                    $pic     = $items_pic[$v2->id];
                }
                $pic = $pic ? $pic : '';
                $specTpl .= '<button class="btn '.$choosed.'" type="button" onclick="choosed(this);"   spec_id = "'.$v->id.'" item_id = "'.$v2->id.'">'.$v2->item.'</button><label for="image'.$k2.'"><span class="spec_img" id="item_pic_'.$v2->id.'" item_id = "'.$v2->id.'" pic="'.$pic.'">
                </span><input class="updata_img" type="text" value="'.$pic.'" name="item_img['.$v2->id.']"></label>';
            }
            $specTpl .= '</td></tr>';
        }
        //property template
        $attribute = $info->goodsAttribute;
        $attr = array();
        if ($goods_id) {
            $attr = Db::name('goods_attr')->where('goods_id', $goods_id)->column('attr_value', 'attr_id');
        }
        $attributeTpl = '<tr class="long-td"><td colspan="2" style="text-align:left"><b>Product attributes</b></td></tr>';
        foreach ($attribute as $k => $v) {
            $val = '';
            if (isset($attr[$v->id])) {
                $val = $attr[$v->id];
            }
            switch ($v->input_type) {
                case 0:
                    $attributeTpl .= '<tr class="long-td secondfloor"><td>'.$v->name.'</td><td><input type="text" class="form-control" name="attr['.$v->id.']" value="'.$val.'"></td></tr>';
                    break;
                case 1:
                    $values = explode('|', $v->values);
                    $attributeTpl .= '<tr class="long-td secondfloor"><td>'.$v->name.'</td><td><select class="form-control col-md-3 chosen-select" style="width: 200px; margin-right: 10px;" name="attr['.$v->id.']"><option value="">无</option>';
                    foreach ($values as $value) {
                        $selected = '';
                        if ($val == $value) {
                            $selected = 'selected="selected"';
                        }
                        $attributeTpl .= '<option value="'.$value.'" '.$selected.'>'.$value.'</option>';
                    }
                    $attributeTpl .= '</select></td></tr>';
                    break;
                case 2:
                    $attributeTpl .= '<tr class="long-td secondfloor"><td>'.$v->name.'</td><td><textarea  type="text" class="form-control" name="attr['.$v->id.']" >'.$val.'</textarea></td></tr>';
                    break;
            }
        }
        $data = ['specTpl' => $specTpl, 'attributeTpl' => $attributeTpl];
        return json(['code' => 1, 'data' => $data]);
    }
    /**
     * ajax get table data
     */
    public function ajaxGetSpecInput(GoodsModel $goods, Array $spec_arr = array(), $goods_id = 0)
    {
        return json(['code' => 1, 'data' => $goods->getSpecInput($goods_id, $spec_arr)]);
    }


    /**
     * ajax get category
     */
    public function ajaxGetCategory($pid, GoodsCategory $category)
    {
        $list = $category->getCategorySon($pid, 0);
        return $list ? json(['code' => 1, 'data' => $list,'msg'=>'']) : json(['code' => 0,'data'=>[], '' => 'There are no subcategories in this category']);
    }

    /**
     * ajax change information
     */
    public function changeInfo($id, $val, $attr)
    {
        $change = ['is_recommend','is_new','is_hot','is_love','is_on_sale','sort','status'];
        if(!in_array($attr,$change)){
		return json(['code'=>0,'data'=>[],'msg'=>'This property cannot be modified']);
        }
        Db::name('goods')->where('id', $id)->update([$attr=>$val]);
        return json(['code' => 1, 'msg' => 'edited successfully']);
    }
    /**
     * Common Change Form Information
     */
    public function ajaxGetGoodsSpec(GoodsSpec $spec, $id)
    {
        $list = $spec->where('type_id', $id)->order("order asc")->select();
        return $list ? json(['code' => 1, 'data' => $list]) : json(['code' => 0,'data'=>[], 'msg' => 'There are no specifications for this type']);
    }
    /**
     * Generic change sorting method
     */
    public function changesort($id, $sort, $model)
    {
        Db::name($model)->where('id', $id)->update(['order'=>$sort]);
        return json(['code' => 1, 'msg' => 'Change the order successfully']);
    }






    public function commentlist()
    {
        $page=input('get.page')?input('get.page'):1;
        $limit=input('get.limit',10);
        $list = Db::name('goods_comment')->alias('com')
            ->field('com.uid,com.infoId,com.orderId,con.*')
            ->join('goods_comment_content con', 'com.id = con.commentId', 'left')->order('con.id desc')->page($page,$limit)->select();
        $extend = [];
        foreach ($list as $k => $v) {
            $extend[$k]['goods_name'] = Db::name('goods')->where('id', $v['infoId'])->value('goods_name');
            $extend[$k]['user_name']  = Db::name('member')->where('id', $v['uid'])->value('nickname');
            $extend[$k]['type']       = $v['type'] == 1 ?'review' : 'comment';
            $extend[$k]['c_time']     = date('Y-m-d H:i:s', $v['c_time']);
            $extend[$k]['orderId']=$v['orderId'];//排序
            $extend[$k]['rank']       = $v['rank'];
            $extend[$k]['content']    = $v['content'];
            $extend[$k]['id']         = $v['id'];
        }
        return json(['code'=>1,'data'=>['list' => $list, 'extend' => $extend],'msg'=>'']);
    }

    public function add_reply($id)
    {
        if ($this->request->isPost()) {
            $data = input('post.');
            if (!$data['reply_content']) {
			return json(['code' => 0, 'msg' => 'The reply content cannot be empty']);
            }
            $update['reply_content'] = $data['reply_content'];
            $update['reply_time'] = time();
            $update['reply_uid'] = session('uid');
            Db::name('goods_comment_content')->where('id', $id)->update($update);
            return json(['code' => 1,'data'=>'','msg' => 'response successful']);
        }
        $reply_content = Db::name('goods_comment_content')->where('id', $id)->value('reply_content');
        return json(['code'=>1,'data'=>['id' => $id, 'reply_content' => $reply_content],'msg'=>'']);
    }

    public function delcomment($id)
    {
        Db::name('goods_comment_content')->where('id', $id)->delete();
        return json(['code' => 1,'data'=>'','msg' => 'deletion successful']);
    }

    //Category menu type
    public function catetype(){
        $id = input('id');
        $map = [];
        if($id){
            $map[] = ['top_cate','=',$id];
        }
        $catetype = Db::name('goods_catetype')->order('orderby asc')->where('status',0)->where($map)->select();
        return json(['code' => 1,'data'=>$catetype,'msg' => '']);
    }

    public function getTypeList(){
        $id =  input('select_id');
        $typeList = Db::name('GoodsType')->where('pid',$id)->select();
        return json(['code'=>1,'data'=>$typeList]);
    }

}
