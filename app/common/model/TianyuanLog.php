<?php

namespace app\common\model;
use think\facade\Db;
use think\Model;

class TianyuanLog extends Model
{
    // Enable automatic writing of timestamp fields
        protected $autoWriteTimestamp = true;
    
        /**
         * Points record
         * @param $uid User ID
         * @param $money amount of money
         * @param int $act action 1 self 2 admin 3 payment 4 refund
         * @param int $status status 0?1 success
         * @param string $remark remark
         * @param int $executor executor 0 self
     */
    public static function operate($uid,$num,$type,$remark='',$executor=0,$relation_id=0){
        $data=[
            'uid'=>$uid,
            'num'=>$num,
            'executor'=>$executor,
            'type'=>$type,
            'remark'=>$remark,
            'relation_id'=>$relation_id,
        ];

        if(self::create($data)){
            return $data;
        }
    }

    public static function tianyuanDes($uid,$num,$type,$remark,$executor=0,$relation_id=0){

    }
    //add newfman beans
    public static function tianyuanInc($uid,$num,$type,$remark,$executor=0,$relation_id=0){
        if($num < 0){
            return ['code'=>0,'msg'=>'Number error!'];
        }
        if(Db::name('member')->where(['id'=>$uid])->inc('tydou',$num)->update()){
            self::operate($uid,$num,$type,$remark,$executor,$relation_id);
        };
        return ['code'=>1,'msg'=>'increase release'];
    }
    //Add frozen Newfman beans
    public static function tianjiaDongjie($uid,$num,$type,$remark,$executor=0,$relation_id=0){
        if($num < 0){
            return ['code'=>0,'msg'=>'Number error!'];
        }
        if(Db::name('member')->where(['id'=>$uid])->inc('dj_tydou',$num)->update()){
            self::operate($uid,$num,$type,$remark,$executor,$relation_id);
        };
        return ['code'=>1,'msg'=>'freeze release'];
    }
    // release the newfman bean
    public static function shifang($uid,$num,$type,$remark,$executor=0,$relation_id=0){
        //First judge whether the user is frozen enough
        $dj_tydou = Db::name('member')->where(['id'=>$uid])->value('dj_tydou');
        if($num < 0){
            return ['code'=>0,'msg'=>'Number error!'];
        }
        if($dj_tydou < $num){
            return ['code'=>0,'msg'=>'Insufficient number of available freezes'];
        }
        // release first
        if(Db::name('member')->where(['id'=>$uid])->dec('dj_tydou',$num)->update()){
            //Then add Newfman beans to the account
           if(Db::name('member')->where(['id'=>$uid])->inc('tydou',$num)->update()){
               self::operate($uid,$num,$type,$remark,$executor,$relation_id);
           }
           return ['code'=>1,'msg'=>'successfully released'];

        };
    }


    //total
    public static function tianyuan_sum($map){
        return self::where($map)->sum('num');
    }
    public static function tianyuan_sum2($map,$map2){
        return self::where($map)->whereOr($map2)->sum('num');
    }
    public static function tianyuan_log($map,$page,$limit=15){
        return self::where($map)->order('id desc')->page($page,$limit)->select();
    }

    public static function tianyuan_log2($map,$map2,$page,$limit=15){
        return self::where($map)->whereOr($map2)->order('id desc')->page($page,$limit)->select();
    }

}

