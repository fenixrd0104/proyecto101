<?php

namespace app\admin\controller;
use app\admin\model\ShopListsModel;
use app\admin\model\ShopOrderListsModel;
use app\admin\model\ShopOrderModel;
use app\admin\model\SpecGoodsModel;
use think\facade\Db;

class Barcode extends Base
{

    public $field1=[
        'goods_name'=>[
			'title'=>'product name',
            'demo'=>'New fashion suit childrens cotton and linen short sleeves',
            'type'=>'text',
            'checked'=>0
        ],
        'unit'=>[
            'title'=>'commodity unit',
            'demo'=>'unit: piece',
            'type'=>'text',
            'checked'=>0
        ],
        'cat_id'=>[
            'title'=>'category name',
            'demo'=>'spring childrens clothing',
            'type'=>'text',
            'checked'=>0
        ],
        'market_price'=>[
            'title'=>'market price',
            'demo'=>'168.68',
            'type'=>'text',
            'checked'=>0
        ],
        'shop_price'=>[
            'title'=>'Our price',
            'demo'=>'138.68',
            'type'=>'text',
            'checked'=>0
        ],
		'goods_sn'=>[
            'title'=>'item number',
            'demo'=>'/static/admin/src/style/res/sn.png',
            'type'=>'img',
            'checked'=>0
        ],
        'sku'=>[
            'title'=>'product model',
            'demo'=>'/static/admin/src/style/res/sku.png',
            'type'=>'img',
            'checked'=>0
        ],
        'spec_name'=>[
            'title'=>'product name',
            'demo'=>'color: red model: X',
            'type'=>'text',
            'checked'=>0
        ],
    ];

    public $field2=[
		'shop_name'=>[
            'title'=>'shop name',
            'demo'=>'shop name: head office',
            'type'=>'text',
            'checked'=>0
        ],
        'order_id'=>[
            'title'=>'Order ID',
            'demo'=>'Order number: 123456789',
            'type'=>'text',
            'checked'=>0
        ],
        'cashier_account'=>[
            'title'=>'Cashier',
            'demo'=>'Cashier: Zhang San',
            'type'=>'text',
            'checked'=>0
        ],
        'sale_account'=>[
            'title'=>'salesman',
            'demo'=>'Salesman: Li Si',
            'type'=>'text',
            'checked'=>0
        ],
		'num'=>[
            'title'=>'sales quantity',
            'demo'=>'sales quantity: 3',
            'type'=>'text',
            'checked'=>0
        ],
        'integral_money'=>[
            'title'=>'Use points',
            'demo'=>'Use points: 38',
            'type'=>'text',
            'checked'=>0
        ],
        'user_money'=>[
            'title'=>'Use balance',
            'demo'=>'Use balance: 42',
            'type'=>'text',
            'checked'=>0
        ],
        'total_amount'=>[
            'title'=>'total amount',
            'demo'=>'Total price: 168.8',
            'type'=>'text',
            'checked'=>0
        ],
		'discount'=>[
            'title'=>'discount amount',
            'demo'=>'Discount amount: 168.8',
            'type'=>'text',
            'checked'=>0
        ],
        'order_amount'=>[
            'title'=>'Amount payable',
            'demo'=>'Amount payable: 55.55',
            'type'=>'text',
            'checked'=>0
        ],
        'pay_name'=>[
            'title'=>'payment method',
            'demo'=>'Payment method: Alipay',
            'type'=>'text',
            'checked'=>0
        ],
        'create_time'=>[
            'title'=>'order time',
            'demo'=>'order time: Alipay',
            'type'=>'text',
            'checked'=>0
        ],
		'goods'=>[
            'title'=>'product list',
            'demo'=>'Product list<br>Product name A red_M * 1<br />Product name B, red_M * 1<br />',
            'type'=>'lists',
            'checked'=>0
        ],

    ];
  //get list
    public function getCont(){
        $id = input('id',1);
        if(request()->isPost()){
            $data=input('post.content',[]);
            $str = json_encode($data);
            if(Db::name('barcode')->where(['id'=>$id])->update(['content'=>$str])){
                return json(['code'=>1,'data'=>[],'msg'=>'Successfully modified']);
            };
        }
        //configure
        $content = Db::name('barcode')->where(['id'=>$id])->value('content');
        $con = json_decode($content,true);
        if(isset($con['sub'])){
            foreach ($con['sub'] as $k => $v){
                if(key_exists($k,$this->{'field'.$id})){
                    $this->{'field'.$id}[$k]['checked']=1;
                }
            }
        }
        return json(['code'=>1,'data'=>['field'=>$this->{'field'.$id},'content'=>$con],'msg'=>'']);
    }

    public function barCode($spec_id){
        $content = Db::name('barcode')->where(['id'=>1])->value('content');
        $con = json_decode($content,true);
        //Find products and inventory 
        $spec =  Db::name('spec_goods')->where(['spec_id'=>$spec_id])->find();
        if(!$spec){
           return json(['code'=>0,'data'=>[],'msg'=>'Product model does not exist']);
        }
        $goods = Db::name('goods')->where(['id'=>$spec['goods_id']])->find();
       if(!$goods){
           return json(['code'=>0,'data'=>[],'msg'=>'non-existent product']);
       }
      $info = array_merge($goods,$spec);
      if(isset($con['sub'])){
            foreach ($con['sub'] as $k => $v){
                if(key_exists($k,$info)){
                    $con['sub'][$k]['value']=$info[$k];
                }
            }
      }
      return json(['code'=>1,'data'=>$con,'msg'=>'']);
    }

    public function cashierOrder(){
        $order_id=input('order_id',0);
        $shop_id = $this->shopId;
        if(!$shop_id){
            $shop_id=config('config.shop_default_manage');
        }
        $ShopListsModel = new ShopListsModel();
        $ShopOrderListsModel =  new ShopOrderListsModel();
        $shop =$ShopListsModel->getOneSubshop($shop_id);
        if(!$shop){
            return json(['code'=>0,'data'=>[],'msg'=>'The store does not exist']);
        }
        $ShopOrderModel = new ShopOrderModel();
        $info = $ShopOrderModel->where(['order_id'=>$order_id,'shop_id'=>$shop_id])->find()->toArray();
        $info['shop_name']=$shop['name'];
        $lists = $ShopOrderListsModel->where(['order_id'=>$order_id])->select();
        $content = Db::name('barcode')->where(['id'=>2])->value('content');
        $con = json_decode($content,true);
        if(isset($con['sub'])){
            foreach ($con['sub'] as $k => $v){
                if($k =='goods'){
                   foreach ($lists as $v){
                       $con['sub'][$k]['value'][]=$v->goods_name.' '.$v->spec_key_name.' quantity'.$v->goods_num;
                   }
                    $con['sub'][$k]['type']='lists';
                }
                if(key_exists($k,$info)){
                    $con['sub'][$k]['value']=$this->field2[$k]['title'].':'.$info[$k];
                }
            }
        }
        return json(['code'=>1,'data'=>$con,'msg'=>'']);
    }
}
