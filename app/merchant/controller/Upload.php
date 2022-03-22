<?php

namespace app\merchant\controller;
use app\common\service\Upload as UploadService;

class Upload extends Base
{
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
            return json(['code'=>1,'data'=>$res['data']['url'],'msg'=>'Uploaded successfully']);
        }
       return json($res);
    }

    // member avatar upload
    public function uploadface(){
        $uploadService = new UploadService();
        $res = $uploadService->upload();
        echo $res['data'];
    }

    //Upload the image to Qiniu
    public function upload2qiniu(){
        $uploadService = new UploadService();
        return json($uploadService->upload());
    }

}
