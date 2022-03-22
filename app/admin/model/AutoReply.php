<?php
namespace app\admin\model;
use think\Model;

class AutoReply extends Model
{
     protected $name = 'wxin_autoreply';
     /**
      * Added WeChat auto-reply
      * @param array $param
      */
     public function addReply($param){
     try{
     $param['key_word']=trim($param['key_word']);
     $param['key_word']='|'.trim($param['key_word'],'|').'|';
     $result = $this->save($param);
     if($result){
     return array('code'=>1,'msg'=>'Added successfully');
     }else{
     return array('code'=>0,'msg'=>'Add failed');
     		}
     	}catch( PDOException $e){
     		return array('code'=>0,'msg'=>$e->getMessage());
     	}
     }  
}