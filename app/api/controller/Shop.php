<?php
/**
 * Created by PhpStorm.
 * User: tianfeiwen
 * Date: 2017/9/25
 * Time: 8:59
 */

namespace app\api\controller;

use think\facade\Db;
use think\facade\Config;
use app\common\model\Cart;
use app\common\model\Goods as GoodsModel;
use app\common\model\Member;

class Shop extends Base
{	
	//Store list
	public function shop_list($province=0,$city=0,$area=0,$industry_id=0, $page = 1, $num = 8){

		$map = [];
		//Regional conditions
		if($province){
			$map[] = ['province','=',$province];
		}
		if($city){
			$map[] = ['city','=',$city];
		}
		if($area){
			$map[] = ['area','=',$area];
		}
		if($industry_id){
			$map[] = ['industry_id','=',$industry_id];
		}

		$order = "rank desc, follow_num desc";

		$shop_list = Db::name('shop_lists')->field('id,name,phone,image,status,address')->where('status',1)->where($map)->order($order)->page($page, $num)->select()->toArray();
		foreach ($shop_list as $k => $v) {
			$shop_list[$k]['comment_num'] = Db::name('goods_comment')->where(['shopId' => $v['id']])->count();
			//$shop_list[$k]['rank'] = Db::name('goods_comment')->alias('a')->join('goods_comment_content b','a.id=b.commentId')->where(['shopId' => $v['id']])->avg('b.rank');
			$shop_list[$k]['follow_num'] = Db::name('shop_follow')->where(['shop_id' => $v['id']])->count();
			$shop_list[$k]['is_follow'] = -1;
			if ($user_id = get_uid()) {
				$is_follow = Db::name('shop_follow')->where(['user_id' => $user_id, 'shop_id' => $v['id']])->count();
				$shop_list[$k]['is_follow'] = $is_follow ? 1 : 0;
			}
			$shop_list[$k]['goods_list'] = Db::name('goods')->where([['shop_id','=',$v['id']],['is_on_sale','=',1],['status','=',1],['spec_num','<>',0]])->order('click_count desc')->limit(3)->select();
		}

		//$shop_list = $this->sortArrByManyField($shop_list,'rank',SORT_DESC,'follow_num',SORT_DESC);
		return json(['status' => 1, 'data' => $shop_list, 'msg' => 'Get the store list successfully']);
	}

	//Get industry classification
	public function getIndustry(){
		$industry = Db::name('industry')->where('parent_id',0)->select()->toArray();
		$getIndustry = [];
		foreach ($industry as $k => $v) {
			$industry[$k]['child'] = Db::name('industry')->where('parent_id',$v['id'])->select()->toArray();
			if($industry[$k]['child']){
				foreach ($industry[$k]['child'] as $kk => $vv) {
					$getIndustry[($k+1).$kk]['ids'] = $v['id'].','.$vv['id'];
					$getIndustry[($k+1).$kk]['value'] = $v['gname'].'/'.$vv['gname'];
				}
			}else{
				$getIndustry[$k+1]['ids'] = $v['id'];
				$getIndustry[$k+1]['value'] = $v['gname'];
			}
			sort($getIndustry);
		}

		return json(['status' => 1, 'data' => $getIndustry, 'msg' => 'Get industry classification success']);
	}

	//Watchlist
	public function follow_list(){
		$user_id = $this->uid();
		$list = Db::name('shop_follow')->alias('a')->join('shop_lists b', 'a.shop_id = b.id', 'left')->field('a.*, b.name,b.image')->where(['user_id' => $user_id])->select();
		$collection_num = Db::name('goods_collection')->alias('a')->field('g.*, a.id, a.goods_id')->join('goods g', 'a.goods_id = g.id', 'left')->where('a.user_id', $user_id)->count();
        $follow_num = Db::name('shop_follow')->where(['user_id' => $user_id])->count();
        return json(['status' => 1,'data'=>$list, 'count'=>['collection_num'=>$collection_num,'follow_num'=>$follow_num], 'msg' => 'search successful']);
	}

	/**
		*@param $id 店铺id

	*/
	public function follow($id){
		$user_id = $this->uid();
        if (!Db::name('shop_lists')->where('id', $id)->find()) {
            return json(['status' => 0, 'msg' => 'The store does not exist']);
        }
        if (Db::name('shop_follow')->where(['user_id' => $user_id, 'shop_id' => $id])->find()) {
            //unsubscribe
            Db::name('shop_follow')->where(['user_id' => $user_id, 'shop_id' => $id])->delete();
            Db::name('shop_lists')->where('id',$id)->dec('follow_num')->update();
            return json(['status' => 1, 'msg' => 'Unfollow success']);
        } else {
            //collect

            Db::name('shop_follow')->insert(['user_id' => $user_id, 'shop_id' => $id, 'create_time' => time(), 'update_time' => time()]);
            Db::name('shop_lists')->where('id',$id)->inc('follow_num')->update();
            return json(['status' => 1, 'msg' => 'Follow success']);
        }
	}

	public function shipping(){
		$shipping = Db::name('shipping')->select();
		return json(['status' => 1,'data'=>$shipping, 'msg' => 'get successful']);
		}
		
		public function sortArrByManyField(){
		$args = func_get_args(); // get an array of arguments to the function
		if(empty($args)){
		return [];
		}
		$arr = array_shift($args);
		if(!is_array($arr)){
		throw new \Exception("The first parameter is not an array");
		}
		foreach($args as $key => $field){
			if(is_string($field)){
			  $temp = array();
			  foreach($arr as $index=> $val){
			    $temp[$index] = $val[$field];
			  }
			  $args[$key] = $temp;
			}
		}
		$args[] = &$arr;//reference value
		call_user_func_array('array_multisort',$args);
		return array_pop($args);
	}
}