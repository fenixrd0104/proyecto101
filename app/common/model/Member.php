<?php

namespace app\common\model;
use app\common\service\Users;
use think\Exception;
use think\Model;
use think\facade\Db;


class Member extends Model
{
    protected $name = 'member';
    protected $autoWriteTimestamp = true; // Enable automatic write timestamp
    
        /**
         * Get user list information based on search criteria
         */
        public function getMemberByWhere($map, $Nowpage, $limits)
        {
            return $this->field('think_member.*,group_name')->join('think_member_group', 'think_member.group_id = think_member_group.id')
                ->where($map)->page($Nowpage, $limits)->order('id desc')->select();
        }
    
        /**
         * Get the number of all users based on search criteria
     * @param $where
     */
    public function getAllCount($map)
    {
        return $this->where($map)->count();
    }


    /**
     * insert information
     */
    public function insertMember($param)
    {
        try{
            $result = $this->validate('MemberValidate')->allowField(true)->save($param);
            if(false === $result){
                return ['code' => -1, 'data' => '', 'msg' => $this->getError()];
            }else{
                //Add the number of recharge records to the record table
                if(isset($param['money']) && $param['money']>0){
                    MoneyLog::operate($param['id'],$param['money'],2,1,'Administrator manual processing',session('uid'));
                }
                //add points
                if(isset($param['integral']) && $param['integral']>0){
                    IntegralLog::operate($param['id'],$param['integral'],2,1,'Administrator manual processing',session('uid'));
                }
                return ['code' => 1, 'data' => '', 'msg' => 'Added successfully'];
            }
        }catch( PDOException $e){
            return ['code' => -2, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Edit information
     * @param $param
     */
    public function editMember($param)
    {
        try{
            $info=$this->get($param['id']);
            $money=$param['money']-$info->money;
            $integral=$param['integral']-$info->integral;
            $result =  $this->validate('MemberValidate')->allowField(true)->save($param, ['id' => $param['id']]);
            if(false === $result){
                return ['code' => 0, 'data' => '', 'msg' => $this->getError()];
            }else{
                if($money != 0){
                    MoneyLog::operate($param['id'],$money,2,1,'Administrator manual processing',session('uid'));
                }
                if($integral != 0){
                    IntegralLog::operate($param['id'],$integral,2,1,'Administrator manual processing',session('uid'));
                }

                return ['code' => 1, 'data' => '', 'msg' => 'edited successfully'];
            }
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }


    public function delMember($id)
    {
        try{
            $map['closed']=1;
            $this->save($map, ['id' => $id]);
            return ['code' => 1, 'data' => '', 'msg' => 'successfully deleted'];
        }catch( PDOException $e){
            return ['code' => 0, 'data' => '', 'msg' => $e->getMessage()];
        }
    }

    /**
     * Add money to users
     * @param $uid
     * @param $money
     * @return array
     */
    public function incMoney($uid,$money){
        if($this->where(['id'=>$uid])->inc('money',$money)->update()){
            return ['code'=>1,'msg'=>'set successfully'];
        }else{
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }

    /**
     * Reduce money for users
     * @param $uid
     * @param $money
     * @return array
     */
    public function decMoney($uid,$money){
        if($this->where(['id'=>$uid])->dec('money',$money)->update()){
            return ['code'=>1,'msg'=>'set successfully'];
        }else{
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }


    public function incIntegral($uid, $integral)
    {
        if ($this->where(['id'=>$uid])->inc('integral', $integral)->update())  {
            return ['code'=>1,'msg'=>'set successfully'];
        } else {
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }

    public function decIntegral($uid, $integral)
    {
        if ($this->where(['id'=>$uid])->dec('integral', $integral)->update())  {
            return ['code'=>1,'msg'=>'set successfully'];
        } else {
            return ['code'=>0,'msg'=>$this->getLastSql()];
        }
    }

    /**
     * Freeze user balance "Replace available balance to frozen balance"
     * @param $uid User ID
     * @param $currency_id currency ID
     * @param $num number
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public  function dongjie($uid,$num)
    {
//         $info = self::where(['uid'=>$uid,'currency'=>$currency_id])->find();
        $info = self::where(['id'=>$uid])->find();
        if($info){
            if($info->money < $num){
              throw new Exception('Insufficient available balance', 10001);
            }
            $info->money=$info->money-$num;
            $info->lock_money=$info->lock_money+$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Frozen failed', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }

    }
    /**
     * Debit user balance "Direct deduction from available balance"
     * @param $uid User ID
     * @param $currency_id currency ID
     * @param $num number
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static public  function kouchu($uid,$num)
    {
        $info = self::where(['id'=>$uid])->find();
        if($info){
            if($info->money < $num){
               throw new Exception('Insufficient available balance', 10001);
            }
            $info->money=$info->money-$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Balance deduction failed', 10002);
            }
        }else{
            throw new Exception('The balance wallet was not found', 10000);
        }

    }

    /**
     * Deduction Freeze "Used to Reduce Freeze"
     * @param $uid
     * @param $currency_id
     * @param $num
     * @return bool
     * @throws Exception
     */
    static public function kouchu_dongjie($uid,$num,$pid){
        $info = self::where(['id'=>$uid])->find();
        if($info){
            if($info->$pid < $num){
                throw new Exception('Insufficient frozen balance', 10001);
            }
            $info->$pid=$info->$pid-$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Failure to deduct freeze', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }
    }

    /**
     * Freeze "Replace available frozen balance to available balance"
     * @param $uid
     * @param $currency_id
     * @param $num
     * @return bool
     * @throws Exception
     * @throws \think\exception\DbException
     */
    static public function shifang_dongjie($uid,$num,$pid)
    {
        $info = self::where(['id'=>$uid])->find();
        if($info){
            if($info->$pid < $num){
                throw new Exception('Insufficient frozen balance', 10001);
            }
            $info->money=$info->money+$num;
            $info->$pid=$info->$pid-$num;
            if($info->save()){
                return true;
            }else{
                throw new Exception('Failed to release freeze', 10002);
            }
        }else{
            throw new Exception('The wallet was not found', 10000);
        }

    }

    /**
    * @param $uid user id
     * @param string $field change field
     * @param string $type increase or decrease
     * @param $num number of changes
     * @return bool
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    static public function Onefield($uid,$field='',$type='up',$num)
    {
        $info = self::where(['id'=>$uid])->find();
        if($info){
            if($type=='up'){
                $info->$field=$info->$field+$num;
            }else{
                if($info[$field]<$num){
                    throw new Exception('Insufficient balance', 10003);
                }
                $info->$field=$info->$field-$num;
            }
            if($info->save()){
                return true;
            }else{
                throw new Exception('Balance deduction failed', 10002);
            }
        }else{
            throw new Exception('The balance wallet was not found', 10000);
        }

    }

    /**
    * Get the user's assigned wallet address No creation
     * @param $uid user id
     */
    public function cz_address($uid){
        $data = $this->where('id',$uid)->find();
        if($data['cz_address'] == ''){
            $arr = new Users();
            $url = 'getnewaddress';
            $coin = 'eth';
            $params['method'] = 'getnewaddress';
            $params['params'][] = "1";
            $params['id'] = 1;
            $arr = $arr->signedRequest($coin,$url,$params);
            var_dump($arr); exit();
            $this->where('id',$uid)->update(['cz_address'=>$arr['result']]);
            $data = $this->where(['id'=>$uid])->field('cz_address')->find();
        }

        return $data;
    }
}