<?php
/**Shipping Template Controller***/
/**editor：wpy*****/
/**date:2017.9.14**/
namespace app\merchant\controller;
use app\merchant\model\FreightTemplate;

class Freight extends Base
{
	private $m;
	public function initialize()
    {
		parent::initialize();
		$this->m = new FreightTemplate();
    }

	public function index(){
		return json(['code'=>1,'data'=>$this->m->get_list($this->shopId),'msg'=>'']);

	}
	public function add(){
	    if(request()->isPost()){
            $res = input("post.");
            $data = array(
                'shop_id'       => $this->shopId,
                'name' 			=> $res['name'],
                'charge_type' 	=> $res['charge_type'],
                'data'			=> serialize(json_encode($res['data']))
            );
            if($this->m->save($data)){
               return json(['code'=>1,'data'=>[],'msg'=>'Added successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'Add failed']);
            }

        }
        $data = $this->m->get_region();
	    return json(['code'=>1,'data'=>['provice_data'=>$data['provice'],'reg_data'=>$data['region'],'charge_type'=>$this->m->charge_type],'msg'=>'']);

    }
	public function edit(){
        if(request()->isPost()){
            $res = input("post.");
            //print_r($res);
            $data = array(
                'shop_id'       => $this->shopId,
                'name' 			=> $res['name'],
                'charge_type' 	=> $res['charge_type'],
                'data'			=> serialize(json_encode($res['data']))
            );
            if($this->m->where(['id'=>$res['id']])->update($data)){
                return json(['code'=>1,'data'=>[],'msg'=>'modified successfully']);
            }else{
                return json(['code'=>0,'data'=>[],'msg'=>'modification failed']);
            }
        }

        $id = input('param.id');
        $info = $this->m->get_info($id);
        $data = $this->m->get_region();
        return json(['code'=>1,'data'=>['info'=>$info,'provice_data'=>$data['provice'],'reg_data'=>$data['region'],'charge_type'=>$this->m->charge_type],'msg'=>'']);

		
	}
	public function del(){
		$id = input("get.id");
		$back = array('code'=>0,"msg"=>'');
		//It is also necessary to first judge whether there is a product in use in the shipping template, otherwise it cannot be deleted
		Be
		if($this->m->where("id","=",$id)->delete()){
		$back = array('code'=>1,"msg"=>'deletion successful');
		}else{
		$back['msg'] = 'Delete failed';
		}
		return json($back);
	}



}





































