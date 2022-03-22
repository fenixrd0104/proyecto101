<?php
/**
 * Shipping address management
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/26
 * Time: 13:34
 */

namespace app\api\controller;

use think\facade\Db;
use think\facade\Config;
use app\common\model\Address as AddressModel;

class Address extends Base
{
    protected $AddressModel;

    public function __construct(AddressModel $AddressModel)
    {
        $this->AddressModel = new AddressModel();
    }

    /**
     * Save the new shipping address
     * @return \think\response\Json
     */
    public function edit_address()
    {
        $data   = input('');
        $arr    = [];

        $user_id = $this->uid();
        $arr['user_id'] = $user_id;
        
        $arr['consignee']  =  isset($data['consignee']) && !empty($data['consignee']) ? $data['consignee'] : '';
        $arr['email']      =  isset($data['email']) && !empty($data['email']) ? $data['email'] : '';
        $arr['country']    =  isset($data['country']) && !empty($data['country']) ? $data['country'] : 0;
        $arr['province']   =  isset($data['province']) && !empty($data['province']) ? $data['province'] : 0;
        $arr['district']   =  isset($data['district']) && !empty($data['district']) ? $data['district'] : 0;
        $arr['city']       =  isset($data['city']) && !empty($data['city']) ? $data['city'] : 0;
        $arr['twon']       =  isset($data['twon']) && !empty($data['twon']) ? $data['twon'] : 0;
        $arr['address']    =  isset($data['address']) && !empty($data['address']) ? $data['address'] : '';
        $arr['zipcode']    =  isset($data['zipcode']) && !empty($data['zipcode']) ? $data['zipcode'] : '';
        $arr['mobile']     =  isset($data['mobile']) && !empty($data['mobile']) ? $data['mobile'] : '';
        $arr['address_id'] =  isset($data['address_id']) && !empty($data['address_id']) ? $data['address_id'] : 0;
        $arr['is_default'] =  isset($data['is_default']) && $data['is_default'] == 1 ? 1 : 0;
        $res = $this->AddressModel->editAddress($arr);
        
        return json($res);
    }

    /**
     * Set default shipping address
     */
    public function set_normal($id)
    {
        $user_id = $this->uid();
        $res = $this->AddressModel->setNormal($id,$user_id);
        return json($res);
    }

    /**
     * delete shipping address
     * @param $id
     * @return \think\response\Json
     */
    public function del_address($id)
    {
        $user_id = $this->uid();
        $res = $this->AddressModel->delAddress($id,$user_id);
        return json($res);
    }

    /**
     * Get the specified delivery address information
     * @param $id
     * @return \think\response\Json
     */
    public function get_address($id)
    {
        $user_id = $this->uid();
        $res = $this->AddressModel->getNormalAddress($user_id, $id);
        return json($res);
    }
    /**
     * Get a list of shipping addresses
     * @return \think\response\Json
     */
    public function get_addressList()
    {
        $user_id = $this->uid();
        $res = $this->AddressModel->addressList($user_id);
        return json($res);
    }

    public function get_region_tree()
    {
        $res = $this->AddressModel->getRegionTree();
        //var_dump($res);
        return json(['status' => 1, 'msg' => 'get success', 'data' => $res]);
    }
}
