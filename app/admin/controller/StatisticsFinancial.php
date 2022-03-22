<?php
/**
 * Financial Statistics
 */

namespace app\admin\controller;
use think\Exception;
use think\facade\Db;

class StatisticsFinancial extends Base
{
    public function tixian(){
		//Total withdrawals
      $zong_tixian_renshu = Db::name('withdraw')->where(['status'=>1])->group('uid')->count();
        //Total withdrawals
     $zong_bishu= Db::name('withdraw')->where(['status'=>1])->count();
        //Total withdrawal amount
     $zong_jine= Db::name('withdraw')->where(['status'=>1])->sum('money');

        // beginning of the month
        $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
        //The number of withdrawals this month
     $benyue_tixianrenshu = Db::name('withdraw')->where(['status'=>1])->where('create_time','>',$beginThismonth)->group('uid')-> count();
        //The number of withdrawals this month
     $benyue_tixianbishu = Db::name('withdraw')->where(['status'=>1])->where('create_time','>',$beginThismonth)->count();
        //Withdrawal amount this month
     $benyue_tixianjine = Db::name('withdraw')->where(['status'=>1])->where('create_time','>',$beginThismonth)->sum('money');
        // Past statistics
        $shangyue1 = strtotime(date('Y-m-01', strtotime('-1 month')));
        $shangyue2 = strtotime(date('Y-m-t', strtotime('-1 month')));
        //The number of withdrawals last month (person)
       $shangyue_tixianrenshu = Db::name('withdraw')->where(['status'=>1])->where('create_time','>',$shangyue1)->where('create_time','< ',$shangyue2)->group('uid')->count();
        //The number of withdrawals in the last month
        $shangyue_tixianbishu = Db::name('withdraw')->where(['status'=>1])->where('create_time','>',$shangyue1)->where('create_time','< ',$shangyue2)->count();
        //The withdrawal amount of the previous month
        $shangyue_tixianjine = Db::name('withdraw')->where(['status'=>1])->where('create_time','>',$shangyue1)->where('create_time','<',$shangyue2)->sum('money');

        return json(['code'=>1,'data'=>[
			'zong_tixian_renshu'=>$zong_tixian_renshu,//Total withdrawals
            'zong_bishu'=>$zong_bishu, //Total number of withdrawals
            'zong_jine'=>$zong_jine,//Total withdrawal amount
            'benyue_tixianrenshu'=>$benyue_tixianrenshu, //Number of withdrawals this month
            'benyue_tixianbishu'=>$benyue_tixianbishu, //Number of withdrawals this month
            'benyue_tixianjine'=>$benyue_tixianjine, //The withdrawal amount this month
            'shangyue_tixianrenshu'=>$shangyue_tixianrenshu,//The number of withdrawals last month (person)
            'shangyue_tixianbishu'=>$shangyue_tixianbishu,//The number of withdrawals in the last month
            'shangyue_tixianjine'=>$shangyue_tixianjine, //The withdrawal amount of the previous month
            'zhifufangshi'=>$this->zhifufangshi() //Pie chart of withdrawal method
        ],'msg'=>'']);


    }
    public function renshu_k($type = ''){
        $start= input('start',time()-60*60*24*30);
        $end= input('end',time());
				$from_u=['%Y-%m-%d','Ymd'];
        switch($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Ym'];
                break;
        }
        $subQuery = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',uid")->where('create_time','>',$start)->where('create_time','<',$end)->where(['status'=>1])
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
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y年%m月','Ym'];
                break;
            case 'w':
                $from_u=['%Y年%u周','Ym'];
                break;
        }
        $res = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}')  '{$from_u[1]}',count(uid) as count ")->where('create_time','>',$start)->where('create_time','<',$end)->group("{$from_u[1]}")->where(['status'=>1])->select();
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
				$from_u=['%Y-%m-%d','Ymd'];
        switch($type){
            case 'd':
                $from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Ym'];
                break;
        }
        $res = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}')  '{$from_u[1]}',sum(money) as sum ")->where('create_time','>',$start)->where('create_time','<',$end)->group("{$from_u[1]}")->where(['status'=>1])->select();
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
        $from_u=['%Y-%m-%d','Ymd'];
        switch ($type){
            case 'd':
				$from_u=['%Y-%m-%d','Ymd'];
                break;
            case 'm':
                $from_u=['%Y year%m month','Ym'];
                break;
            case 'w':
                $from_u=['%Y year%u week','Ym'];
                break;
        }
        $subQuery = Db::name('withdraw')->field("FROM_UNIXTIME(create_time,'{$from_u[0]}') as '{$from_u[1]}',uid,sum(money) as money")->where('create_time','>',$start)->where('create_time','<',$end)
            ->group("{$from_u[1]},uid")->where(['status'=>1])
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
      $res = (Db::name('withdraw')->field('type,count(*) as count')->where(['status'=>1])->group('type')->select());

      $count=0;
      foreach ($res as $v){
          $count+=$v['count'];
          $data[$v['type']-1]['value']=$v['count'];
      }
      foreach ($data as &$v){
          $v['value']=$count==0?0:(string)round($v['value']/$count*100,2);
      }
      return $data;

    }













}