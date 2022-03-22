<?php

		namespace app\admin\model;
		use think\Model;
		use think\facade\Db;
		use think\Cache;
		
		class FreightTemplate extends Model
		{
		
			public $charge_type=[
		1=>'By the number of pieces',
				2=>'by weight',
				3=>'By volume',
			];
			// custom initialization
			protected function initialize()
			{
				//Need to call the initialize method of the Model
				parent::initialize();
				//TODO: custom initialization
			}
		//list of shipping templates
		function get_list(){
		$list = Db::name("freight_template")->alias('ft')->join('shop_lists s','ft.shop_id=s.id','LEFT')->field('ft. *,s.name as shop_name')->select()->toArray();
		foreach($list as $k=>$v){
		switch($v['charge_type']){
		case 1:
		$list[$k]['type_name'] = 'By Piece';
		break;
		case 2:
		$list[$k]['type_name'] = 'By weight';
		break;
		case 3:
		$list[$k]['type_name'] = 'By volume';
					break;
			}
		}
		return $list;
	}
	//Get the reorganized area array
	function get_region(){
		//cache('region_arr',NULL);
		$cache = cache('region_arr');
		if(isset($cache) && $cache){
			$data = $cache;
		}else{
			$res = Db::name("region")
					->alias("r")->field("r.id,r.name,r.parent_id,r2.name as province")
					->join("think_region r2","r.parent_id=r2.id","LEFT")
					->where("r.level","2")
					->select(); 

			$list = array();
			foreach($res as $k=>$v){
				if(isset($list[$v['parent_id']])){
					$list[$v['parent_id']]['city'][$v['id']] = $v['name'];
				}else{
					$list[$v['parent_id']]['name'] =$v['province'];
					$list[$v['parent_id']]['city'][$v['id']] = $v['name'];
				}
			}
			//Arrange the region array corresponding to the province into a region string
			$provice = array();
			foreach($list as $k=>$v){
				$key_arr = array_keys($v['city']);
				$key_str = implode(',',$key_arr);
				$provice[$k]['name'] = $v['name'];
				$provice[$k]['city'] = $key_str;
			}
			//The province id corresponding to the region
			$region = array(
				'East China' => array(310000,320000,330000,340000,360000),
				'North China' => array(110000,120000,140000,370000,130000,150000),
				'Huazhong' => array(430000,420000,410000),
				'South China' => array(440000,450000,350000,460000),
				'Northeast' => array(210000,220000,230000),
				'Northwest' => array(610000,620000,630000,640000,650000),
				'Southwest' => array(500000,510000,520000,530000,540000),
				'Hong Kong, Macao and Taiwan' => array(710000,810000,820000)
							);
			$arr = array();
			foreach($list as $k=>$v){
				foreach($region as $k2=>$v2){
					if(in_array($k,$v2)){
						$arr[$k2][$k] = $v;
						break;
					}
				}
			}
			$data = array('region'=>$arr,'provice'=>$provice);
			cache('region_arr',$data,3600);
		}
		return $data;
	}
			//Read the database template information and reconstruct the data for display on the edit page
			function get_info($id){
			$res = Db::name("freight_template")->where("id",$id)->find();
			$res['data'] = unserialize($res['data']);
			$res['data_arr'] = json_decode($res['data'],true);
			//Get each group, the province to which the region belongs
			$region_info = $this->get_region();
			$provice = $region_info['provice'];//Array of secondary regions grouped by province
		foreach($provice as $k=>$v){
			$provice[$k]['city_arr'] = explode(',',$v['city']);
		}
		
		if(isset($res['data_arr']['general'])){
			foreach($res['data_arr']['general'] as $k=>$v){
				$region_arr = explode(',',$v['region']);
				$provice_str = '';
				foreach($provice as $k2=>$v2){
					if(!empty(array_intersect($v2['city_arr'],$region_arr)) && stripos($v2['name'],$provice_str)>=0){
						$provice_str .= $provice_str==''?$v2['name']:','.$v2['name'];
					}
				}
				$res['data_arr']['general'][$k]['provice'] = $provice_str;
			}
		}
		if(isset($res['data_arr']['special'])){
			foreach($res['data_arr']['special'] as $k=>$v){
				$region_arr = explode(',',$v['region']);
				$provice_str = '';
				foreach($provice as $k2=>$v2){
					if(!empty(array_intersect($v2['city_arr'],$region_arr)) && stripos($v2['name'],$provice_str)>=0){
						$provice_str .= $provice_str==''?$v2['name']:','.$v2['name'];
					}
				}
				$res['data_arr']['special'][$k]['provice'] = $provice_str;
			}
		}
		//print_r($res);
		return $res;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}