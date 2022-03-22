<?php
namespace app\api\controller;

use think\facade\Db;

class Index extends Base
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">Ten years of sharpening a sword - for API High-performance framework for development and design</span></p><span style="font-size:22px;">[ V5.0 version by <a href="http://www.qiniu.com" target="qiniu">Seven Niuyun</a> Exclusive Sponsored Release ]</span></div><script type="text/javascript" src="http://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="http://ad.topthink.com/Public/static/client.js"></script><thinkad id="ad_bd568ce7058a1091"></thinkad>';
    }

    /**
     *front page
     */
    public function index_mid(){
        $top = Db::name('goods')->where([['is_new',"=",1],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]])->limit(6)->select();
        $mid = Db::name('goods')->where([['is_hot',"=",1],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]])->limit(3)->select();
        $data = [
            'top'=>$top,
            'mid'=>$mid,
            'slide'=>get_adinfo(35,$type='list'),
        ];
        return $this->returnJson($data);
    }

    /**
     * Category at the bottom of the home page
     */
    public function index_cat(){
        $type = [
           // ['id'=>0,'name'=>"Guess you like it"],
            ['id'=>2,'name'=>"Preferred Product"],
            ['id'=>1,'name'=>"VIP product"],
            ['id'=>100,'name'=>"E area"],
            ['id'=>3,'name'=>"Special Offer"],
            ['id'=>4,'name'=>"direct purchase"],
            ['id'=>5,'name'=>"High-end"],
        ];
        $address=[
            ['value'=>'Taiwan','name'=>'China Taiwan'],
            ['value'=>'Mainland','name'=>'Mainland China'],
            ['value'=>'Singapore','name'=>'Singapore'],
        ];
        $data['type']=$type;
        $data['address']=$address;
        return $this->returnJson($data);
    }

    public function index_buttom(){
        $Nowpage = input('page') ? input('page'):1;
        $limits = input('limit',15);
        $cat_id = input('cat_id',0);
        $type=input('type');
        
        if($cat_id==0){
            $map=[['is_recommend','=',1],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]];
            if($type){
                $map[] = ['spec_key_type','like', "%".$type."%"];
            }
            //$list = Db::name('goods')->where([['top_cate','=',1],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]])->order('is_recommend desc')->page($Nowpage,$limits)->select();
            $list = Db::name('goods')->where($map)->page($Nowpage,$limits)->order('sort asc,id desc')->select();
        }else{
            if($cat_id==100||$cat_id==2){
                if($cat_id==2){
                    $where=[['top_cate','=',$cat_id],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]];
                    $where[] = ['fenlei','=',1];
                }else{
                    $where[] = ['fenlei','=',2];
                    $where[] = ['top_cate','=',2];
                }
                if($type){
                    $where[] = ['spec_key_type','like', "%".$type."%"];
                }
                $list = Db::name('goods')->where($where)->order('sort asc,id desc')->page($Nowpage,$limits)->select();
            }else{
                $where1=[['top_cate','=',$cat_id],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]];
                if($type){
                    $where1[] = ['spec_key_type','like', "%".$type."%"];
                }
                $list = Db::name('goods')->where($where1)->order('sort asc,id desc')->page($Nowpage,$limits)->select();
            }

        }
        return $this->returnJson($list);
    }

    public function goods_search(){
        $Nowpage = input('page') ? input('page'):1;
        $limits = input('limit',15);
        $keywords = input('keywords');
        $list = Db::name('goods')->where([['goods_name','like','%'.$keywords.'%'],['is_delete','=',0],['status','=',1],['is_on_sale','=',1]])->page($Nowpage,$limits)->select();
        return $this->returnJson($list);
    }



    public function we_search(){
        $Nowpage = input('page') ? input('page'):1;
        $limits = input('limit',15);
        $keywords = input('keywords');
        $list = Db::name('article')->where([['title','like','%'.$keywords.'%']])->page($Nowpage,$limits)->select();
        return $this->returnJson($list);
    }

   /**
     * Generic return data method
     * @param $data
     * @return \think\response\Json
     */
    protected function returnJson($data)
    {
        return $data ? json(['status' => 1, 'msg' => 'Get data successfully', 'data' => $data]) : json(['status' => 0, 'info' => 'no more data', 'data' => '']);
    }
}
