<?php

namespace app\admin\controller;
use app\common\service\Upload as UploadService;
use app\common\model\UploadLog;
class Upload extends Base
{
    public function log(){
        $Nowpage=input('page',1);
        $limit=input('limit',10);
        $name=input('name','');
        $status=input('status','');
        $map=[];
        if($name&&$name!=''){
            //$map[]=['name','like',$name.'%'];
        }if($status||$status==0&&$status!=''){
            $map[]=['status','=',$status];
        }
        $UploadLog = new UploadLog;
        $db=$UploadLog->where($map);
        $data['list']=$db->order('update_time desc')->page($Nowpage,$limit)->select()->toArray();
        $data['count']=$UploadLog->count();
        return json(['code'=>1,'data'=>$data,'msg'=>'']);
    }

    public function log_del(){
        $id=input('id','');
        $UploadLog = new UploadLog;
        $data=$UploadLog->where('id',$id)->delete();
        if(!$data){
            return json(['code'=>0,'data'=>'','msg'=>'删除失败，请重试']);
        }
        return json(['code'=>1,'data'=>'','msg'=>'删除成功']);
    }
    
    public function um(){
   /* die('{"originalName":"14_avatar_middle.jpg","name":"15624830495283.jpg","url":"upload\/20190707\/15624830495283.jpg","size":2666,"type":".jpg","state":"SUCCESS"}');*/
        $uploadService = new UploadService();
        $res = $uploadService->upload('upfile');
        if($res['code'] ==1){
            $res['data']['size']=2666;
            $res['data']['state']='SUCCESS';
            echo json_encode($res['data']);die;
        }
    }
	//upload picture
    public function upload(){
        $uploadService = new UploadService();
        $res = $uploadService->upload();
        if($res['code'] == 1){
            return json(['code'=>1,'data'=>$res['data']['url'],'msg'=>'上传成功']);
        }
       return json($res);
    }

    //Member avatar upload
    public function uploadface(){
        $uploadService = new UploadService();
        $res = $uploadService->upload();
        echo $res['data'];
    }

    //Upload the picture to Qiniu
    public function upload2qiniu(){
        $uploadService = new UploadService();
        return json($uploadService->upload());
    }

}
