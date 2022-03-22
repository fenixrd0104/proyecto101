<?php
/**
 * Financial Statistics
 */

namespace app\merchant\controller;
use think\Exception;
use think\facade\Db;

class StatisticStransaction extends Base
{

    //Transaction overview
    public function jiaoyi(){

       $taday =strtotime(date("Y-m-d"));
       $zuotian =strtotime(date("Y-m-d",strtotime("-1 day")));
       $data = [];
        //Number of online payment orders
        $data['xianshang_dingdan']['zong'] = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0) ->count();
        // all day yesterday
        $data['xianshang_dingdan']['zuo'] = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0) ->where([['add_time','<',$taday],['add_time','>',$zuotian]])->count();

        // Number of people paying online
        $data['xianshang_fukuan']['zong']= Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0) ->group('user_id')->count();
        // all day yesterday
        $data['xianshang_fukuan']['zuo'] =Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0) ->where([['add_time','<',$taday],['add_time','>',$zuotian]])->group('user_id')->count();

        //Total number of online orders
        $data['xianshang_zongdingdan']['zong'] = Db::name('order')->where('shop_id',$this->shopId)->count();
        // all day yesterday
        $data['xianshang_zongdingdan']['zuo'] = Db::name('order')->where('shop_id',$this->shopId)->where([['add_time','<', $taday],['add_time','>',$zuotian]])->count();

        //Total number of online orders
        $data['xianshang_zongrenshu']['zong'] = Db::name('order')->where('shop_id',$this->shopId)->group('user_id')->count();
        // all day yesterday
        $data['xianshang_zongrenshu']['zuo'] = Db::name('order')->where('shop_id',$this->shopId)->where([['add_time','<',$taday],['add_time','>',$zuotian]])->group('user_id')->count();

        /**
         * -------------------------------------------------------------------------
         */
        //Number of offline payment orders
        $data['xianxia_dingdan']['zong'] = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1 )->count();
        // all day yesterday
        $data['xianxia_dingdan']['zuo'] = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1 )->where([['create_time','<',$taday],['create_time','>',$zuotian]])->count();

        //The number of offline payers
        $data['xianxia_fukuan']['zong'] = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1 )->group('user_id')->count();
        // all day yesterday
        $data['xianxia_fukuan']['zuo'] = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1 )->where([['create_time','<',$taday],['create_time','>',$zuotian]])->group('user_id')->count();

        //The total number of offline orders
        $data['xianxia_zongdingdan']['zong']= Db::name('shop_order')->where('shop_id',$this->shopId)->count();
        // all day yesterday
        $data['xianxia_zongdingdan']['zuo']= Db::name('shop_order')->where('shop_id',$this->shopId)->where([['create_time','<', $taday],['create_time','>',$zuotian]])->count();

        //Total number of offline orders
        $data['xianxia_zongrenshu']['zong'] = Db::name('shop_order')->where('shop_id',$this->shopId)->group('user_id')->count();
        // all day yesterday
        $data['xianxia_zongrenshu']['zuo'] = Db::name('shop_order')->where('shop_id',$this->shopId)->where([['create_time','<', $taday],['create_time','>',$zuotian]])->group('user_id')->count();


        //Total online payment amount today 24-hour aggregation
       $xianshang_24_price =  Db::name('order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(add_time,'%H') as h,sum(goods_price) as count")->where('add_time','>',$taday)->group("h") ->select();
        $xianshang_48_price =  Db::name('order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(add_time,'%H') as h,sum(goods_price) as count")->where([['add_time','<',$taday],['add_time','>',$zuotian]]) ->group("h") ->select();


        $xianshang_fukuanjine=[
            'category'=>[],
            'jintian'=>[],
            'zuotian'=>[]
        ];
        $xianshang_jintian=[];
        $xianshang_zuotian=[];
        foreach ($xianshang_24_price as $v){
            $xianshang_jintian[$v['h']]=$v['count'];
        }
        foreach ($xianshang_48_price as $v){
            $xianshang_zuotian[$v['h']]=$v['count'];
        }
        $xianshang_fukuanjine['category']=$xianshang_jintian+$xianshang_zuotian;
        ksort($xianshang_fukuanjine['category']);
        foreach ($xianshang_fukuanjine['category'] as $k => $v){
            if(key_exists($k,$xianshang_jintian)){
                $xianshang_fukuanjine['jintian'][]= $xianshang_jintian[$k];
            }else{
                $xianshang_fukuanjine['jintian'][]= 0;
            }
        }
        foreach ($xianshang_fukuanjine['category'] as $k => $v){
            if(key_exists($k,$xianshang_zuotian)){
                $xianshang_fukuanjine['zuotian'][]= $xianshang_zuotian[$k];
            }else{
                $xianshang_fukuanjine['zuotian'][]= 0;
            }
        }
        $xianshang_fukuanjine['category']=array_keys($xianshang_fukuanjine['category']);

        $data['xianshang_fukuanjine']=$xianshang_fukuanjine;


        //The total amount of offline payments yesterday aggregated in 24 hours
        $xianxia_24_price =  Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'%H') as h,sum(goods_price) as count")->where('create_time','>',$taday)->group("h") ->select();
        $xianxia_48_price =  Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'%H') as h,sum(goods_price) as count")->where('create_time','>',$taday)->where('create_time','<',$zuotian)->group("h") ->select();
        $xianxia_fukuanjine=[
            'category'=>[],
            'jintian'=>[],
            'zuotian'=>[]
        ];
        $xianxia_jintian=[];
        $xianxia_zuotian=[];
        foreach ($xianxia_24_price as $v){
            $xianxia_jintian[$v['h']]=$v['count'];
        }
        foreach ($xianxia_48_price as $v){
            $xianxia_zuotian[$v['h']]=$v['count'];
        }
        $xianxia_fukuanjine['category']=$xianxia_jintian+$xianxia_zuotian;
        ksort($xianxia_fukuanjine['category']);
        foreach ($xianxia_fukuanjine['category'] as $k => $v){
            if(key_exists($k,$xianxia_jintian)){
                $xianxia_fukuanjine['jintian'][]= $xianxia_jintian[$k];
            }else{
                $xianxia_fukuanjine['jintian'][]= 0;
            }
        }
        foreach ($xianxia_fukuanjine['category'] as $k => $v){
            if(key_exists($k,$xianxia_zuotian)){
                $xianxia_fukuanjine['zuotian'][]= $xianxia_zuotian[$k];
            }else{
                $xianxia_fukuanjine['zuotian'][]= 0;
            }
        }
        $xianxia_fukuanjine['category']=array_keys($xianxia_fukuanjine['category']);

        $data['xianxia_fukuanjine']=$xianxia_fukuanjine;
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    //todo::Not done Core indicator payment amount K line
    public function jiaoyifukuan_k($type=''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $arr=[
            'd',
            'w',
            'm',
        ];
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
              $from_u=['%Y%m%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Ym'];
                break;
        }
        $res = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}')  '{$from_u[1]}',sum(money) as sum ")->where('create_time','>',$start)->where('create_time','<',$end)->group("{$from_u[1]}")->select();
        $data=[
            'category'=>[],
            'value'=>[]
        ];
        foreach ($res as $v){
            $data['category'][]=$v[$from_u[1]];
            $data['value'][]=$v['sum'];
        }

        $data=[
            'xianshang'=>[
                    'category'=>['2019-06-25','2019-06-28','2019-07-01','2019-07-04','2019-07-07','2019-07-10','2019-07-13','2019-07-16','2019-07-19','2019-07-22','2019-07-25'],
                    'bishu'=>[0,0,0,0,0,0,0,0,0,0,0],
                    'jine'=>[0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00],
            ],
            'xianxia'=>[
                'category'=>['2019-06-25','2019-06-28','2019-07-01','2019-07-04','2019-07-07','2019-07-10','2019-07-13','2019-07-16','2019-07-19','2019-07-22','2019-07-25'],
                'bishu'=>[0,0,0,0,0,0,0,0,0,0,0],
                'jine'=>[0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00],
            ],
        ];
        return json(['code'=>1,'data'=>$data,'msg'=>'']);

    }
    //todo::No exchange data transaction customer
    public function jiaoyichengjiao(){
        $data=[
            'jine'=>['new'=>'0.00','old'=>'0.00'],
            'renshu'=>['new'=>0,'old'=>0],
            'benzhou'=>['bili'=>"0.0",'duibi'=>"0.0",'type'=>'icon-up'],
            'benyue'=>['bili'=>"0.0",'duibi'=>"0.0",'type'=>'icon-down'],
        ];
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }


    // commodity statistics
    public function shangpingtongji(){
        //Number of commodities (species)
        $shangpinzongshu = Db::name('goods')->where('shop_id',$this->shopId)->count();
        //Number of items sold online (pieces)
        $xianshang_yishou =Db::name('order_goods')->where('shop_id',$this->shopId)->sum('goods_num');
        //Number of items sold offline (pieces)
        $xianxia_yishou= Db::name('shop_order')->where('shop_id',$this->shopId)->sum('num');
        //Amount of goods sold online (yuan)
        $xianshang_jine = Db::name('order')->where('shop_id',$this->shopId)->sum('goods_price');
        //Amount of goods sold offline (yuan)
        $xianxia_jine =Db::name('shop_order')->where('shop_id',$this->shopId)->sum('goods_price');
        //Total inventory (pieces)
        $kucunshuliang = Db::name('goods_stock')->where('shop_id',$this->shopId)->sum('stock');
        //Total value of inventory (yuan)
        $kucunjine = Db::name('goods_stock')->where('shop_id',$this->shopId)->sum('stock*shop_price');
		
        return json(['code'=>1,'data'=>[
            'shangpinzongshu'=>$shangpinzongshu,
            'xianshang_yishou'=>$xianshang_yishou,
            'xianxia_yishou'=>$xianxia_yishou,
            'xianshang_jine'=>$xianshang_jine,
            'xianxia_jine'=>$xianxia_jine,
            'kucunshuliang'=>$kucunshuliang,
            'kucunjine'=>$kucunjine,
        ],'msg'=>'']);

    }
    //todo::No product sales
    public function shagnpdognxiao_k($type=''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $arr=[
            'd',
            'w',
            'm',
        ];
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
                $from_u=['%Y%m%d','Ymd'];
                break;
            case 'm':
              $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Yw'];
                break;
        }

        $data=[
            'category'=>['2019-06-25','2019-06-28','2019-07-01','2019-07-04','2019-07-07','2019-07-10','2019-07-13','2019-07-16','2019-07-19','2019-07-22','2019-07-25'],
            'zaijia'=>[0,0,0,0,0,0,0,0,0,0,0],
            'dongxiao'=>[0,0,0,0,0,0,0,0,0,0,0],

        ];
        return json(['code'=>1,'data'=>$data,'msg'=>'']);

    }
    //Types of goods sold
    public function zhonglei_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y-%m','Ym'];
                break;
            case 'w':
                $from_u=['%Y-%u','Yw'];
                break;
        }

        $subQuery = Db::name('goods')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',create_time")->where('create_time','>',$start)->where('create_time','<',$end)
            ->group("{$from_u[1]}")
            ->select();
        $data=[
            'category'=>[],
            'value'=>[]
        ];
        foreach ($subQuery as $v){
            if($from_u[1] == 'Yw'){
                $par = explode('-',$v['Yw']);
                $par[1]+=1;
                $time=strtotime("+ $par[1] week" ,strtotime("$par[0]-01-00"));
            }else{
                $time= strtotime("+ 1 day",strtotime($v[$from_u[1]]));
            }
            $data['category'][]=$v[$from_u[1]];
            $data['value'][]=Db::name('goods')->where('shop_id',$this->shopId)->where('create_time','<=',$time)->count();

        }
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    //online sales statistics
    public function xianshang_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y%m%d','Ymd'];
        switch ($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Yw'];
                break;
        }
        $res = Db::name('order_goods')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',sum(goods_num) as count ")->where('create_time','>',$start)->where('create_time','<',$end)->group("{$from_u[1]}")->select();

        $data=[
            'category'=>[],
            'value'=>[]
        ];
        foreach ($res as $v){
            $data['category'][]=$v[$from_u[1]];
            $data['value'][]=$v['count'];
        }
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    //Product offline sales statistics
    public function xianxia_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y%m%d','Ymd'];
        switch($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Yw'];
                break;
        }
        $res = Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',sum(num) as count ")->where('create_time','>',$start)->where('create_time','<',$end)->group("{$from_u[1]}")->select();

        $data=[
            'category'=>[],
            'value'=>[]
        ];
        foreach ($res as $v){
            $data['category'][]=$v[$from_u[1]];
            $data['value'][]=$v['count'];
        }
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    //Commodity ranking
    public function shangpinpaihang(){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $limit= input('limit',5);
        $page= input('page',1);
        //Ranking of the number of online product sales
        $xianshangshuliang = Db::name('order_goods')->where('shop_id',$this->shopId)->field('goods_id,goods_name,sum(goods_num) as count,sum(goods_num*goods_price) as price ,create_time')->where('create_time','>',$start)->where('create_time','<',$end)->group("goods_id")->order('count desc' )->page($page,$limit)->select();

        // Ranking of the number of offline product sales
        $xianxiashuliang = Db::name('shop_order_lists')->where('shop_id',$this->shopId)->field('goods_id,goods_name,sum(goods_num) as count,sum(goods_num*shop_price) as price ,create_time')->where('create_time','>',$start)->where('create_time','<',$end)->group("goods_id")->order('count desc' )->page($page,$limit)->select();

        //Ranking of online product sales amount
        $xianshangjine = Db::name('order_goods')->where('shop_id',$this->shopId)->field('goods_id,goods_name,sum(goods_num) as count,sum(goods_num*goods_price) as price ,create_time')->where('create_time','>',$start)->where('create_time','<',$end)->group("goods_id")->order('price desc' )->page($page,$limit)->select();
        //Offline product sales amount ranking
        $xianxiajine = Db::name('shop_order_lists')->where('shop_id',$this->shopId)->field('goods_id,goods_name,sum(goods_num) as count,sum(goods_num*shop_price) as price ,create_time')->where('create_time','>',$start)->where('create_time','<',$end)->order('price desc')->group("goods_id" )->page($page,$limit)->select();
        //Commodity inventory ranking
        $stock = Db::name('goods_stock')->where('shop_id',$this->shopId)->field('goods_id,sum(stock) as count,create_time')->order('count desc')->group("goods_id")->page($page,$limit)->select();
        $stock =$stock->toArray();
        foreach ($stock as $k => &$v){
            $v['goods_name'] = Db::name('goods')->where('shop_id',$this->shopId)->where(['id'=>$v['goods_id']])->value('goods_name');
        }



        return json(['code'=>1,'data'=>[
            'xianshangshuliang'=>$xianshangshuliang,
            'xianxiashuliang'=>$xianxiashuliang,
            'xianshangjine'=>$xianshangjine,
            'xianxiajine'=>$xianxiajine,
            'stock'=>$stock,
        ],'msg'=>'']);
    }







    //order statistics
    public function dingdan(){

        // beginning of the month
        $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));

        //Total number of orders (pen)
        $xianshang_count = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->count();
        //Total consumption amount (yuan)
        $xianshang_goods_price = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->sum('goods_price');
        //Total offline orders (pen)
        $xianxia_count = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1)->count();
        //Total offline consumption amount (yuan)
        $xianxia_goods_price = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1)->sum('goods_price');


        //Order of this month (pen)
        $xianshang_benyue_count = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->where('add_time','> =',$beginThismonth)->count();
        //The consumption amount of this month (yuan)
        $xianshang_benyue_goods_price = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->where('add_time','> =',$beginThismonth)->sum('goods_price');
        //Offline order of the month (pen)
        $xianxia_benyue_count = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1)->where('create_time',' >=',$beginThismonth)->count();
        //Offline consumption amount this month (yuan)
        $xianxia_benyue_goods_price = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1)->where('create_time','>=',$beginThismonth)->sum('goods_price');




        $shangyue1 =  strtotime(date('Y-m-01', strtotime('-1 month')));
        $shangyue2 =  strtotime(date('Y-m-t', strtotime('-1 month')));

        //Online last month's order (pen)
        $xianshang_shangyue_count = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->where('add_time','> ',$shangyue1)->where('add_time','<',$shangyue2)->count();
        //Online last month's order amount (yuan)
        $xianshang_shangyue_goods_price = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->where('add_time','> ',$shangyue1)->where('add_time','<',$shangyue2)->sum('goods_price');
        //Offline last month's order (pen)
        $xianxia_shangyue_count = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1)->where('create_time',' >',$shangyue1)->where('create_time','<',$shangyue2)->count();
        //Offline last month's order amount (yuan)
        $xianxia_shangyue_goods_price = Db::name('shop_order')->where('shop_id',$this->shopId)->where("pay_status",'>=',1)->where('create_time','>',$shangyue1)->where('create_time','<',$shangyue2)->sum('goods_price');

        return json(['code'=>1,'data'=>[
            'xianshang_count'=>$xianshang_count,
            'xianshang_goods_price'=>$xianshang_goods_price,
            'xianxia_count'=>$xianxia_count,
            'xianxia_goods_price'=>$xianxia_goods_price,

            'xianshang_benyue_count'=>$xianshang_benyue_count,
            'xianshang_benyue_goods_price'=>$xianshang_benyue_goods_price,
            'xianxia_benyue_count'=>$xianxia_benyue_count,
            'xianxia_benyue_goods_price'=>$xianxia_benyue_goods_price,

            'xianshang_shangyue_count'=>$xianshang_shangyue_count,
            'xianshang_shangyue_goods_price'=>$xianshang_shangyue_goods_price,
            'xianxia_shangyue_count'=>$xianxia_shangyue_count,
            'xianxia_shangyue_goods_price'=>$xianxia_shangyue_goods_price,

        ],'msg'=>'']);
    }
    //Number of orders k
    public function dingdanbishu_k($type=''){

            $start= input('start',time()-60*60*24*30);
            $end= input('end',time());
            $from_u=['%Y-%m-%d','Ymd'];
            switch ($type){
                case 'd':
                    $from_u=['%Y-%m-%d','Ymd'];
                    break;
                case 'm':
                    $from_u=['%Y-%m','Ym'];
                    break;
                case 'w':
                    $from_u=['%Y-%u','Yw'];
                    break;
            }

            $xianshang_xiaoshou = Db::name('order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(add_time,'{$from_u[0]}') as '{$from_u[1]}',count(*) as count")->where('add_time','>',$start)->where('add_time','<',$end)
                ->group("{$from_u[1]}")
                ->select();

            $xianshang_tuihuo = Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',count(*) as count")->where('create_time','>',$start)->where('create_time','<',$end)
                ->group("{$from_u[1]}")
                ->select();

            $data=[
                'category'=>[],
                'xianshang'=>[],
                'xianxia'=>[]
            ];

            $xianshang=[];
            $xianxia=[];
            foreach ($xianshang_xiaoshou as $v){
                $xianshang[$v[$from_u[1]]]=$v['count'];
            }
            foreach ($xianshang_tuihuo as $v){
                $xianxia[$v[$from_u[1]]]=$v['count'];
            }


            $data['category']=array_merge($xianshang,$xianxia);
            ksort($data['category']);

            foreach ($data['category'] as $k => $v){
                if(key_exists($k,$xianshang)){
                    $data['xianshang'][]= $xianshang[$k];
                }else{
                    $data['xianshang'][]= 0;
                }
            }
            foreach ($data['category'] as $k => $v){
                if(key_exists($k,$xianxia)){
                    $data['xianxia'][]= $xianxia[$k];
                }else{
                    $data['xianxia'][]= 0;
                }
            }

            $data['category'] = array_keys($data['category']);
            return json(['code'=>1,'data'=>$data,'msg'=>'']);


    }
    //order amount k
    public function dingdanjine_k($type=''){

        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y-%m','Ym'];
                break;
            case 'w':
                $from_u=['%Y-%u','Yw'];
                break;
        }

        $xianshang_xiaoshou = Db::name('order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(add_time,'{$from_u[0]}') as '{$from_u[1]}',sum(goods_price) as count")->where('add_time','>',$start)->where('add_time','<',$end)
            ->group("{$from_u[1]}")
            ->select();

        $xianshang_tuihuo = Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',sum(goods_price) as count")->where('create_time','>',$start)->where('create_time','<',$end)
            ->group("{$from_u[1]}")
            ->select();

        $data=[
            'category'=>[],
            'xianshang'=>[],
            'xianxia'=>[]
        ];

        $xianshang=[];
        $xianxia=[];
        foreach ($xianshang_xiaoshou as $v){
            $xianshang[$v[$from_u[1]]]=$v['count'];
        }
        foreach ($xianshang_tuihuo as $v){
            $xianxia[$v[$from_u[1]]]=$v['count'];
        }

        $data['category']=array_merge($xianshang,$xianxia);
        ksort($data['category']);

        foreach ($data['category'] as $k => $v){
            if(key_exists($k,$xianshang)){
                $data['xianshang'][]= $xianshang[$k];
            }else{
                $data['xianshang'][]= '0.00';
            }
        }
        foreach ($data['category'] as $k => $v){
            if(key_exists($k,$xianxia)){
                $data['xianxia'][]= $xianxia[$k];
            }else{
                $data['xianxia'][]= '0.00';
            }
        }
        $data['category'] = array_keys($data['category']);
        return json(['code'=>1,'data'=>$data,'msg'=>'']);


    }



    //Return statistics
    public function tuihuo(){
        //Number of online returns
       $xianshang_tuihuobishu = Db::name('return_goods')->where('shop_id',$this->shopId)->count();
        //Online exchange ratio
        $xianshang_order = Db::name('order')->where('shop_id',$this->shopId)->where("pay_status",">",0)->count();
        $xianshang_tuihuolv = $xianshang_order==0?0:round($xianshang_tuihuobishu/$xianshang_order,2);

        // number of offline strokes
       $xianxia_tuihuobishu = Db::name('shop_order')->where('shop_id',$this->shopId)->where('returns_status','<>',0)->count();
        //offline exchange ratio
       $xianxia_order = Db::name('shop_order')->where('shop_id',$this->shopId)->count();
        $xianxia_tuihuolv = $xianxia_order==0?0:round($xianxia_tuihuobishu/$xianxia_order,2);
       return json(['code'=>1,'data'=>[
           'xianshang_tuihuobishu'=>$xianshang_tuihuobishu,
           'xianshang_tuihuolv'=>"$xianshang_tuihuolv",
           'xianxia_tuihuobishu'=>$xianxia_tuihuobishu,
           'xianxia_tuihuolv'=>"$xianxia_tuihuolv",
       ],'msg'=>'']);
    }
    //Return K line
    public function tuihuo_k($type=''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y-%m','Ym'];
                break;
            case 'w':
                $from_u=['%Y-%u','Yw'];
                break;
        }
        //Online return K line
        $xianshang_xiaoshou = Db::name('order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(add_time,'{$from_u[0]}') as '{$from_u[1]}',count(*) as count")->where('add_time','>',$start)->where('add_time','<',$end)
            ->group("{$from_u[1]}")
            ->select();

        $xianshang_tuihuo = Db::name('return_goods')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(addtime,'{$from_u[0]}') as '{$from_u[1]}',count(*) as count")->where('addtime','>',$start)->where('addtime','<',$end)
            ->group("{$from_u[1]}")
            ->select();

        $data=[
            'category'=>[],
            'jiaoyi'=>[],
            'tuihuo'=>[]
        ];

        $jiaoyi=[];
        $tuihuo=[];
        foreach ($xianshang_xiaoshou as $v){
            $jiaoyi[$v[$from_u[1]]]=$v['count'];
        }
        foreach ($xianshang_tuihuo as $v){
            $tuihuo[$v[$from_u[1]]]=$v['count'];
        }

        $data['category']=array_merge($jiaoyi,$tuihuo);
        ksort($data['category']);

        foreach ($data['category'] as $k => $v){
           if(key_exists($k,$jiaoyi)){
               $data['jiaoyi'][]= $jiaoyi[$k];
           }else{
               $data['jiaoyi'][]= 0;
           }
        }
        foreach ($data['category'] as $k => $v){
            if(key_exists($k,$tuihuo)){
                $data['tuihuo'][]= $tuihuo[$k];
            }else{
                $data['tuihuo'][]= 0;
            }
        }
        $data['category'] = array_keys($data['category']);






        //Offline return K line
        $xianshangxia_xiaoshou = Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',count(*) as count")->where('create_time','>',$start)->where('create_time','<',$end)
            ->group("{$from_u[1]}")
            ->select();

        $xianxia_tuihuo = Db::name('shop_order')->where('shop_id',$this->shopId)->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',count(*) as count")->where('create_time','>',$start)->where('create_time','<',$end)->where('returns_status','<>',0)
            ->group("{$from_u[1]}")
            ->select();

        $data_xianxia=[
            'category'=>[],
            'jiaoyi'=>[],
            'tuihuo'=>[]
        ];

        $xiaxia_jiaoyi=[];
        $xiaxiatuihuo=[];
        foreach ($xianshangxia_xiaoshou as $v){
            $xiaxia_jiaoyi[$v[$from_u[1]]]=$v['count'];
        }
        foreach ($xianxia_tuihuo as $v){
            $xiaxiatuihuo[$v[$from_u[1]]]=$v['count'];
        }


        $data_xianxia['category']=array_merge($xiaxia_jiaoyi,$xiaxiatuihuo);
        ksort($data_xianxia['category']);

        foreach ($data_xianxia['category'] as $k => $v){
            if(key_exists($k,$xiaxia_jiaoyi)){
                $data_xianxia['jiaoyi'][]= $xiaxia_jiaoyi[$k];
            }else{
                $data_xianxia['jiaoyi'][]= 0;
            }
        }
        foreach ($data_xianxia['category'] as $k => $v){
            if(key_exists($k,$xiaxiatuihuo)){
                $data_xianxia['tuihuo'][]= $xiaxiatuihuo[$k];
            }else{
                $data_xianxia['tuihuo'][]= 0;
            }
        }
        $data_xianxia['category'] = array_keys($data_xianxia['category']);
        return json(['code'=>1,'data'=>[
            'xianshang'=>$data,
            'xianxia'=>$data_xianxia,
        ],'msg'=>'']);

    }




















}