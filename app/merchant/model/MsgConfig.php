<?php

namespace app\merchant\model;
use think\Model;

class MsgConfig extends Model
{
    
    // custom initialization
        protected function initialize()
        {
            //Need to call the initialize method of Model
            parent::initialize();
            //TODO: custom initialization
        }
    //Get the list of messages to send
    public function get_msg_list(){
            $limits = input('get.limit',15);
    $lists = $this->paginate($limits);
    Be
    if($lists){
    $list = $lists->toArray();
    
    $data=$list['data'];
    $count = $list['total'];
    $auth_arr = array(1,2,4,8,16);
    foreach($data as $key=>$val){
    //Construct the message sending mode state array
				foreach($auth_arr as $v){
					if($val['auth'] & $v){
                        $data[$key]['auth_arr'][$v] = 1;
					}else{
                        $data[$key]['auth_arr'][$v] = 0;
					}
				}
			}

		}
		return array('list'=>$data,'count'=>$count);
	}
		//Get the basic information of a message
		public function get_msg_info($id){
			$info = $this->where('id',$id)->find()->toArray();
			//Construct an array of message sending methods

		$info['param'] = unserialize($info['param']);
		$auth_arr = array(1,2,4,8,16);
		foreach($auth_arr as $v){
			if($info['auth'] & $v){
				$info['auth_arr'][] = $v;
			}
		}
		return $info;
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
}