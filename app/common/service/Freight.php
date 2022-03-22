<?php
/**
		* General class for freight calculation
		* Created by notepad++.
		* User: wpy
		* Date: 2017/9/118
		* Time: 10:28
		*/
		namespace app\common\service;
 
		use think\facade\Db;
 
		class Freight
		{
		// get shipping information
		//@param $fid The shipping template id corresponding to the product
		//@param $rid area id (secondary area)
     static function goods_freight($fid,$rid){
		$res = Db::name('freight_template')->where('id',$fid)->find();
		$tp = json_decode(unserialize($res['data']),true);
 
		if(isset($tp) && !empty($tp)){
		//Determine in turn whether there is a current area in the three types of shipping information of the shipping template. The priority is special>general>default
		//sepcial free shipping template
			$special = array();
			if(isset($tp['special']) && !empty($tp['special'])){
				foreach($tp['special'] as $k=>$v){
					
					if(in_array($rid,explode(',',$v['region']))){
						if($v['type'] == 'money'){
						$msg = 'full'.$v['range'].'yuan free shipping';
						}elseif($v['type'] == 'weight'){
						$msg = 'Free shipping within '.$v['range'].'kg';
						}
						$special = array('msg'=>$msg,'type'=>$v['type'],'range'=>$v['range']);
						break;
						}
						}
						}
						/ / Determine the billing method (unit)
				switch ($res['charge_type']){
				case 1:
					$unit = '件';
					break;
				case 2:
					$unit = 'kg';
					break;
				case 3:
					$unit = '立方';
					break;	
			}
				//Normal shipping template
					if(isset($tp['general']) && !empty($tp['general'])){
					foreach($tp['general'] as $k=>$v){
						Be
					if(in_array($rid,explode(',',$v['region']))){
					if($v['info']['begin_price']==0 && $v['info']['inc_price']==0){
							$msg = 'Free shipping';
							$back = array('status'=>1,'msg'=>$msg,'general'=>'free');
					}else{
							$msg = 'Super'.$v['info']['begin'].$unit.'Charge'.$v['info']['begin_price'].'RMB shipping, per super'.$v ['info']['inc'].$unit.'plus'.$v['info']['inc_price'].'unit';
					if(!empty($special)){
								$msg .=','.$special['msg'];
							}
							$general = array('type'=>$res['charge_type'],'info'=>$v['info']);
							$back = array('status'=>1,'msg'=>$msg,'general'=>$general,'special'=>$special);
						}
						
						return $back;
					}
				}
			}
			//Default Shipping Template
			if(isset($tp['default']) && !empty($tp['default'])){
				$df = $tp['default'];
				if($df['begin_price']==0 && $df['inc_price']==0){
					$msg = 'free shipping';
					$back = array('status'=>1,'msg'=>$msg,'general'=>'free');
				}else{
					$msg = 'overtake'.$df['begin'].$unit.'receive'.$df['begin_price'].'$$ shipping, per super'.$df['inc'].$unit.'add'.$df['inc_price'].'USD';
					if(!empty($special)){
						$msg .=','.$special['msg'];
					}
					$general = array('type'=>$res['charge_type'],'info'=>$df);
					$back = array('status'=>1,'msg'=>$msg,'general'=>$general,'special'=>$special);
					
				}
				return $back;
			}
			
		}
		return array('status'=>0,'msg'=>'invalid shipping template');
		Be
		}
		//Calculate the shipping cost of the order
		//@param $rid The secondary area id corresponding to the consignee address
		//@param $arr Information list of all items in the order, such as:
		/* array(
		0 => array(
		'id' =>//Commodity id, temporarily useless, as a reserved field
		'fid' => // commodity shipping template id
		'num' =>//Number of items
		'weight' =>//Commodity weight
		'volume' =>//Product volume
		'price' => // commodity price
					),
		1 => array()
		) 
	*/
	
	//The current billing method is that products using the same shipping template participate in the calculation together. (For example: free shipping over 100, the total amount of all products using this template will add up to 100 free shipping)
	static function order_freight($rid,$arr){
	//Group products by shipping template
	foreach($arr as $k=>$v){
	$tp_arr[$v['fid']][] = $v;
	}
	//print_r($tp_arr);
	//Calculate the shipping cost of each template group in turn
	$freight_arr = array();
	foreach($tp_arr as $k=>$v){
	//Get the billing information of the template
			$info = self::goods_freight($k,$rid);
			if($info['status'] == 1){
				if(is_string($info['general']) && $info['general'] == 'free'){
					$freight_arr[$k] = 0;
				}else{
					$general = $info['general'];
					$special = $info['special'];
					//Calculate the value of the relevant billing attributes used by the products in the group
					$num=$weight=$volume=$price=0;
					foreach($v as $k2=>$v2){
						$num += $v2['num'];
						$weight += $v2['num']*$v2['weight'];
						$volume += $v2['num']*$v2['volume'];
						$price +=$v2['num']*$v2['price'];
					}
					//Determine whether the package template exists and whether it is satisfied
					if(!empty($special)){
						if($special['type'] == 'price'){
							if($price >= $special['range']){
								$freight_arr[$k] = 0;
								continue;
							}
						}elseif($special['type'] == 'weight'){
							if($weight < $special['range']){
								$freight_arr[$k] = 0;
								continue;
							}
						}
					}
					//Calculate the regular mode shipping fee
					$base_num = 0;
					switch ($general['type']){
					//by the number of pieces
					case 1:
					$base_num = $num;
					break;
					//by weight
					case 2:
					$base_num = $weight;
					break;
					//by volume
						case 3:
							$base_num = $volume;
							break;	
					}
					if($base_num >= $general['info']['begin']){
						$begin_price = $general['info']['begin_price'];//first freight
						$inc_price = ceil(($base_num-$general['info']['begin'])/$general['info']['inc'])*$general['info']['inc_price'];//Renewal freight
						$freight_arr[$k] = $begin_price+$inc_price;

					}else{
						$freight_arr[$k] = 0;
					}
				}
			}else{
				$freight_arr[$k] = $info['msg'];
			}
		}
		return array_sum($freight_arr);
	}
}






















