<?php
/**
 * User address management
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/26
 * Time: 10:17
 */
namespace app\common\model;

use think\Model;
use think\facade\Db;
use think\facade\Cache;

class Address extends Model
{
    /**
     * Tertiary list of regions
     */
    public function getRegionTree()
    {

        if (!$list = Cache::get('region')) {
            $arr = Db::name('region')->field('id as value,name as text,parent_id')->order('sort asc')->select();
            $list = [];
            foreach ($arr as $k => $v) {
                if ($v['parent_id'] == 0) {
                    foreach ($arr as $k1 => $v1) {
                        if ($v1['parent_id'] == $v['value']) {
                            foreach ($arr as $k2 => $v2) {
                                if ($v2['parent_id'] ==  $v1['value']) {
                                    $v1['children'][] = $v2;
                                }
                            }
                            $v['children'][] = $v1;
                        }
                    }
                    $list[] = $v;
                }

            }
            Cache::set('region',$list,0);
        }

        return $list;
    }

    /**
     * Add & Edit Shipping Address
     * @param $data user_id,consignee,email,country,province,city,district,twon,address,zipcode,mobile
     * @return array
     */
    public function editAddress($data)
    {
        
        if (!$data['consignee']) {
            return ['status' => 0, 'msg' => 'Consignee required'];
        }
        if (!$data['province'] || !$data['city'] || !$data['address']) {
            return ['status' => 0, 'msg' => 'shipping address required'];
        }
        if (!$data['mobile']) {
            return ['status' => 0, 'msg' => 'Contact phone number required'];
        }
        if ($data['is_default'] == 1) {
            Db::name('user_address')->where(['user_id' => $data['user_id']])->update(['is_default' => 0]);//Set all addresses is non-default
        } else {
            //Check if there is a default address, if not, the new one will default to the default address
            if (!$data['address_id']) {
                if (!Db::name('user_address')->where(['user_id' =>$data['user_id'], 'is_default' => 1])->count()) {
                    $data['is_default'] = 1;
                }
            } else {
                if (Db::name('user_address')->where(['user_id' =>$data['user_id']])->count() == 1) {
                    $data['is_default'] = 1;
                }
            }

        }
        
        $cache = cache('db_config_data');
        if(!Db::name('user_address')->where(['user_id' =>$data['user_id'],'mobile'=>$data['mobile']])->find()){
            if(Db::name('user_address')->group('user_id')->where(['mobile' =>$data['mobile']])->count() >= (int)$cache['phonenum']){
                return ['status' => 0, 'msg' => 'The account number bound to the mobile phone number cannot exceed' . $cache['phonenum'] . '个'];
            }
        }

        if ($address_id = $data['address_id']) {
            //Revise
            unset($data['address_id']);
            Db::name('user_address')->where(['address_id' => $address_id, 'user_id' => $data['user_id']])->update($data);
        } else {

            Db::name('user_address')->insert($data);
        }
        return ['status' => 1, 'msg' => 'Save successfully'];
    }

    /**
     * Delete shipping address
     */
    public function delAddress($id, $user_id)
    {
        
        $address = Db::name('user_address')->where(['address_id' => $id, 'user_id' => $user_id])->find();
        if ($address && $address['is_default']) {
            return ['status' => 0, 'msg' => 'The default address cannot be deleted'];
        }
        Db::name('user_address')->where(['address_id' => $id, 'user_id' => $user_id])->delete();
        return ['status' => 1, 'msg' => 'deletion successful'];
    }

    /**
     * Set default shipping address
     */
    public function setNormal($id,$user_id)
    {
        Db::name('user_address')->where(['user_id' => $user_id])->update(['is_default' => 0]);
        Db::name('user_address')->where(['address_id' => $id, 'user_id' => $user_id])->update(['is_default' => 1]);
       return ['status' => 1, 'msg' => 'set successfully'];
    }

    /**
     * Shipping address list
     */
    public function addressList($user_id)
    {
        $list = Db::name('user_address')->where(['user_id' => $user_id])->order('is_default desc,address_id asc')->select()->toArray();
        if ($list) {
            if (!$region = Cache::get('regiondata')) {
                $region = Db::name('region')->column('name','id');
                Cache::set('regiondata', $region, 0);
            }
            foreach ($list as $k => $v) {
                $addr = '';
                isset($region[$v['province']]) && $addr .= $region[$v['province']] . ',';
                isset($region[$v['city']]) && $addr .= $region[$v['city']] . ',';
                isset($region[$v['district']]) && $addr .= $region[$v['district']] . ',';
                //isset($region[$v['twon']]) && $addr .= $region[$v['twon']] . ',';
                $v['address'] && $addr .= $v['address'];
                $list[$k]['address_detail'] = $addr;
            }
            return ['status' => 1, 'msg' => 'Get the delivery address successfully', 'data' => $list];
        }
        return ['status' => 0, 'msg' => 'The user has not set the delivery address', 'data' => ''];
    }

    public function getNormalAddress($user_id, $id = 0)
    {
        if ($id == 0) {
            $info = Db::name('user_address')->where(['user_id' => $user_id, 'is_default' => 1])->find();
        } else {
            $info = Db::name('user_address')->where(['user_id' => $user_id, 'address_id' => $id])->find();
        }
        if ($info) {
            
            if (!$region = Cache::get('regiondata')) {
                $region    = Db::name('region')->column('name','id');
                Cache::set('regiondata', $region, 0);
            }

            // if(!$region['910121']){
            //     $region    = Db::name('region')->column('name','id');
            //     Cache::set('regiondata', $region, 0);
            // }

            $addr = '';
            isset($region[$info['province']]) && $addr .= $region[$info['province']] . '，';
            isset($region[$info['city']])     && $addr .= $region[$info['city']] . '，';
            isset($region[$info['district']]) && $addr .= $region[$info['district']] . '，';
            //isset($region[$info['twon']])     && $addr .= $region[$info['twon']] . '，';
            $info['pcd'] = trim(str_replace('，', ' ', $addr));
            $info['address'] && $addr .= $info['address'];
            $info['address_detail'] = $addr;
           return ['status' => 1, 'msg' => 'Get the delivery address successfully', 'data' => $info];
        }
			return ['status' => 0, 'msg' => 'The user has not set the delivery address', 'data' => ''];
    }

    public function addrList(){
        return Db::name('region')->column('id,name');
    }

    public function getAddr($province,$city,$district){
        $list = $this->addrList();
        $addr='';
        isset($list[$province]) && $addr .= $list[$province] . '，';
        isset($list[$city])     && $addr .= $list[$city] . '，';
        isset($list[$district]) && $addr .= $list[$district] . '，';
        return $addr;
    }

}