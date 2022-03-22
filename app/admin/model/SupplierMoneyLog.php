<?php

namespace app\admin\model;
use think\Model;
use think\facade\Db;

class SupplierMoneyLog extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $name='goods_supplier_money_log';

    public $typeStatus=[
        1=>'进货',
        2=>'退货',
    ];
    public $settlementStatus=[
        0=>'待结算',
        1=>'已结算',
    ];

    public function getTypeAttr($value){
        return $this->typeStatus[$value];
    }
    public function getSettlementAttr($value){
        return $this->settlementStatus[$value];
    }

    public function getCount($map){
        return $this->join('think_goods_supplier','think_goods_supplier_money_log.supplier_id=think_goods_supplier.supplier_id','left')->join('think_shop_lists','think_shop_lists.id=think_goods_supplier_money_log.shop_id','left')->where($map)->count();
    }
    public function getLists($map,$page,$limit){
        return $this->field('think_goods_supplier_money_log.*,think_goods_supplier.supplier_name,think_shop_lists.name as shop_name')->join('think_goods_supplier','think_goods_supplier_money_log.supplier_id=think_goods_supplier.supplier_id','left')->join('think_shop_lists','think_shop_lists.id=think_goods_supplier_money_log.shop_id','left')->page($page,$limit)->where($map)->select();
    }



    /**
     * 创建日志
     * @param $supplier_id
     * @param $shop_id
     * @param $money
     * @param $type  1 采购订单  2 退货订单
     * @param $source_id
     * @param int $settlement
     * @param string $uid
     * @param string $remark
     * @return array|string
     */
    public static function operate($supplier_id,$shop_id,$money,$type,$source_id,$settlement=0,$uid=0,$remark=''){

        $data=[
            'supplier_id'=>$supplier_id,
            'shop_id'=>$shop_id,
            'uid'=>$uid,
            'money'=>$money,
            'type'=>$type,
            'settlement'=>$settlement,
            'source_id'=>$source_id,
            'remark'=>$remark,
        ];

        if(self::create($data)){
            //这里思考要不要给 供应商 加余额 supplier_balance
            Db::name('goods_supplier')->where(['supplier_id'=>$supplier_id])->update(['supplier_balance'=>Db::raw("supplier_balance+$money")]);
            return $data;
        }else{
            return '';
        }
    }

    /**
     * 增加钱
     * @param $uid
     * @param $money
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setInc($supplier_id, $money)
    {
        if (Db::name('goods_supplier')->where(['supplier_id'=>$supplier_id])->inc('supplier_balance', $money)->update())  {
            return ['code'=>1,'msg'=>'操作成功'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }

    /**
     * 减少钱
     * @param $uid
     * @param $money
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setDec($supplier_id, $money)
    {

        if (Db::name('goods_supplier')->where(['supplier_id'=>$supplier_id])->dec('supplier_balance', $money)->update())  {
            return ['code'=>1,'msg'=>'操作成功'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }





}

