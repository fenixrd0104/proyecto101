<?php
/**
 * Financial Statistics
 */

namespace app\merchant\controller;
use think\Exception;
use think\facade\Db;

class StatisticsUsers extends Base
{

    public function jiaoyi(){
        //Number of payment orders
        // all day yesterday

        // number of payers
        // all day yesterday

        //Total number of orders placed
        // all day yesterday

        //Total number of orders placed
        // all day yesterday



        //Total payment amount today aggregated over 24 hours
        //Total payment amount yesterday aggregated over 24 hours

        //Core indicator payment order K line
        //Core indicator payment amount k line

    }

    public function shangpingtongji(){
        //Number of commodities (species)
          Db::name('goods')->count();
        //Number of items sold (pieces)
        //Amount of goods sold (yuan)
        //Total inventory (pieces)
        //Total value of inventory (yuan)

        //Commodity type K line
        // Statistics on the number of sales of goods
        //Statistics of product sales amount
        //Commodity sales ranking
        //Commodity ranking





    }



    public function renshu_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y%m%d','Ymd'];
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
        $subQuery = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',uid")->where('create_time','>',$start)->where('create_time','<',$end)
          ->group("{$from_u[1]},uid")
          ->fetchSql(true)
          ->select();
        $res =  Db::table('('.$subQuery . ') a')->field("$from_u[1],count(uid) as count")->group("$from_u[1]")->select();
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
    public function bishu_k($type = ''){
        $arr=[
            'd',
            'w',
            'm',
        ];
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y%m%d','Ymd'];
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
        $res = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}')  '{$from_u[1]}',count(uid) as count ")->where('create_time','>',$start)->where('create_time','<',$end)->group("{$from_u[1]}")->select();
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
    public function jine_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $arr=[
            'd',
            'w',
            'm',
        ];
        $from_u=['%Y%m%d','Ymd'];
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
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    public function renjun_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
        $from_u=['%Y%m%d','Ymd'];
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
        $subQuery = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',uid,sum(money) as money")->where('create_time','>',$start)->where('create_time','<',$end)
            ->group("{$from_u[1]},uid")
          ->fetchSql(true)
            ->select();
      // return json(['code'=>1,'data'=>$subQuery,'msg'=>'']);
        $res =  Db::table('('.$subQuery . ') a')->field("$from_u[1],count(uid) as count,sum(money) as money")->group("$from_u[1]")->select();
        $data=[
            'category'=>[],
            'value'=>[]
        ];
        foreach ($res as $v){
            $data['category'][]=$v[$from_u[1]];
            $data['value'][]=number_format($v['money']/$v['count'],2);
        }
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }
    protected function zhifufangshi(){
        $data= [
            [
              'name'=>'Alipay',
             'value'=> 0,
            ],
            [
                'name'=>'WeChat payment',
                'value'=> 0,
            ],
            [
                'name'=>'bank card',
                'value'=> 0,
            ]
        ];
      $res = (Db::name('withdraw')->field('type,count(*) as count')->group('type')->select());

      $count=0;
      foreach ($res as $v){
          $count+=$v['count'];
          $data[$v['type']-1]['value']=$v['count'];
      }
      foreach ($data as &$v){
          $v['value']=(string)round($v['value']/$count*100,2);
      }
      return $data;

    }













}