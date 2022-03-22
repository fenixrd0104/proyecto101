<?php

namespace app\merchant\model;
use think\Model;
use think\facade\Db;

class SupplierMoneyLog extends Model
{
    // Enable automatic writing of timestamp fields
    protected $autoWriteTimestamp = true;
    protected $name='goods_supplier_money_log';

    public $typeStatus=[
        1=>'restock',
        2=>'return',
    ];
    public $settlementStatus=[
        0=>'to be settled',
        1=>'settled',
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
     * create log
     * @param $supplier_id
     * @param $shop_id
     * @param $money
     * @param $type 1 purchase order 2 return order
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
            //Here think about whether to add a balance to the supplier supplier_balance
            Db::name('goods_supplier')->where(['supplier_id'=>$supplier_id])->update(['supplier_balance'=>Db::raw("supplier_balance+$money")]);
            return $data;
        }else{
            return '';
        }
    }

    /**
     * increase money
     * @param $uid
     * @param $money
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setInc($supplier_id, $money)
    {
        if (Db::name('goods_supplier')->where(['supplier_id'=>$supplier_id])->inc('supplier_balance', $money)->update())  {
            return ['code'=>1,'msg'=>'Successful operation'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }

    /**
     * reduce money
     * @param $uid
     * @param $money
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public static function setDec($supplier_id, $money)
    {

        if (Db::name('goods_supplier')->where(['supplier_id'=>$supplier_id])->dec('supplier_balance', $money)->update())  {
            return ['code'=>1,'msg'=>'Successful operation'];
        } else {
            return ['code'=>0,'msg'=>Db::name('member')->getLastSql()];
        }
    }





}

