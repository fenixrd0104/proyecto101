<?php

namespace app\common\service;
use think\facade\Cache;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use OSS\OssClient;
use OSS\Core\OssException;
use app\common\model\UploadLog;

class Upload
{

    /*crop*/
        private static $site_w = 0;//allowable width
        private static $site_h = 0;//Allowable height
        private static $site_t = 0 ;// do not process 1 thumbnail 2 crop
        private static $site_left = 0;//Crop position from left to right
        private static $site_top = 0;//Cropping position from top to bottom

    public function upload($file_name='file', $app='default',$site_w=0,$site_h=0,$site_t=0,$site_left=0,$site_top=0){

        self::$site_w = $site_w;
        self::$site_h = $site_h;
        self::$site_t = $site_t;
        self::$site_left = $site_left;
        self::$site_top = $site_top;

        $conf = Cache::get('db_config_data');
        //return $this->uploadOss($file_name,$app);
        if ($conf['upload_switch'] == 2) {
           //Seven cattle upload is enabled
            return $this->upload2qiniu($app);
           
        }elseif($conf['upload_switch'] == 3) {
            //Ali OSS
            return $this->uploadOss($file_name,$app);
        }else{
            //local
            return $this->upload2local($file_name,$app);
        }
    }

    //Upload the picture to Qiniu
    private function upload2qiniu($app){
        $conf = Cache::get('db_config_data');
        $file = request()->file('file');
        $info = $file->validate(['size' => $conf['upload_image_size'] * 1024,'ext' => $conf['upload_image_extensions']])->move(ROOT_PATH . 'public_html' . DS . 'uploads/images');
        //$info = \think\facade\Filesystem::disk('public_html')->putFile( 'uploads/images/'.date('Y/m-d'), $file,'uniqid');
        
        if($info){
            $filePath = UPLOAD_PATH . '/uploads/images/' . $info->getSaveName();
            $accessKey = $conf['upload_accesskey'];
            $secretKey = $conf['upload_secretkey'];
           // Build the authentication object
            $auth = new Auth($accessKey, $secretKey);
            // space to upload
            $bucket = $conf['upload_bucket'];
            // Generate upload token
            $token = $auth->uploadToken($bucket);
            // The file name saved after uploading to Qiniu
            $key =md5(time()).".".substr($filePath,(stripos($filePath,'.')+1));
            // Initialize the UploadManager object and upload files.
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            $size = strlen(file_get_contents($filePath));
            $mime = getimagesize($filePath)['mime'];
            $this->upload_log($conf['upload_domain'] . $ret['key'],$ret['key'],$size,$mime, 2, $app, $desc='None');
            @unlink($filePath);
            if ($err == null) {
                return ['code' => 1, 'data' => $conf['upload_domain'] . $ret['key']];
            } else {
                return ['code' => 0, 'data' => "Upload error"];
            }
        }else{
            return ['code' => 0, 'data' => $file->getError()];
        }
    }

    //Upload image to local
    private function upload2local($file_name, $app){
        // upload to local server
        $file = request()->file($file_name);
        $conf = Cache::get('db_config_data');
        try{
            validate(['file'=>'fileSize:'.$conf['upload_image_size'] * 1024 .'|fileExt:'.$conf['upload_image_extensions']])->check(['file'=>$file ]);
            $savename = \think\facade\Filesystem::disk('public')->putFile( 'uploads/images/'.date('Y/m-d'), $file,'uniqid');
            $this->proces($savename);//Cutting
            $this->upload_log($conf['upload_domain'].$savename,$savename,$file->getSize(), $file->getMime(), 1, $app, $desc='none');
            return ['code' => 1, 'data' =>['originalName'=>$file->getOriginalName(),'name'=>$savename,'url'=>$conf['upload_domain'].$ savename,'type'=>'.'.$file->getOriginalExtension()]];
        }catch (\Exception $e){
            return ['code'=>0,'data'=>[],'msg'=>$e->getMessage()];
        }
    }


    //Upload to Alibaba Cloud OSS
    private function uploadOss($file_name,$app){
        $conf = Cache::get('db_config_data');

        $file = request()->file($file_name);
        try{
            validate(['file'=>'fileSize:'. $conf['upload_image_size'] * 1024 .'|fileExt:'.$conf['upload_image_extensions']])->check(['file'=>$file]);
        }catch (\Exception $e){
            return ['code'=>0,'data'=>[],'msg'=>$e->getMessage()];
        }
        try{
            $savename = \think\facade\Filesystem::disk('local')->putFile( 'uploads/images/'.date('Y/m-d'), $file,'uniqid');
            
            $true_src =config('filesystem.disks.local.root').DIRECTORY_SEPARATOR.$savename;
            $this->proces($true_src);//Cutting
            $aliyun_oss = [
                'accessKeyId' => $conf['upload_accesskey'], //your Access Key ID LTAIcvNvz51eJENq LTAI4FvmxoDkKopVzCMJuPjg
                'accessKeySecret' => $conf['upload_secretkey'], //your Access Key Secret
                'endpoint' => $conf['endpoint'], //Alibaba cloud oss ​​external network address endpoint//https://rab-images.oss-cn-hangzhou.aliyuncs.com/
                'bucket'=>$conf['upload_bucket'], //Bucket name
                'url'=>$conf['upload_domain'], // Access address
            ];
            $ossClient = new OssClient($aliyun_oss['accessKeyId'], $aliyun_oss['accessKeySecret'], $aliyun_oss['endpoint']);
            //Determine whether the bucketname exists, and create it if it does not exist
            if( !$ossClient->doesBucketExist($aliyun_oss['bucket'])){
                $ossClient->createBucket($aliyun_oss['bucket']);
            }
            $res = $ossClient->uploadFile($aliyun_oss['bucket'],$savename,$true_src);
            if($res){
                $this->upload_log($aliyun_oss['url'].$savename,$savename,$file->getSize(), $file->getMime(),2, $app, $desc='without');
                return ['code' => 1, 'data' =>['originalName'=>$file->getOriginalName(),'name'=>$savename,'url'=> $aliyun_oss['url'].$savename]];
            }else{
                return ['code' => 0, 'data' =>[],'msg'=>'upload failed'];
            }
        }catch (\Exception $e){
            return ['code'=>0,'data'=>[],'msg'=>$e->getMessage()];
        }
    }


    public function ercodeOss($true_src,$savename,$app='wxercode',$desc='Merchant QR code'){
        $conf = Cache::get('db_config_data');
        try{
            $aliyun_oss = [
                'accessKeyId' => $conf['upload_accesskey'], //your Access Key ID LTAIcvNvz51eJENq LTAI4FvmxoDkKopVzCMJuPjg
                'accessKeySecret' => $conf['upload_secretkey'], //your Access Key Secret
                'endpoint' => $conf['endpoint'], //Alibaba cloud oss ​​external network address endpoint//https://rab-images.oss-cn-hangzhou.aliyuncs.com/
                'bucket'=>$conf['upload_bucket'], //Bucket name
                'url'=>$conf['upload_domain'], // Access address
            ];
            $ossClient = new OssClient($aliyun_oss['accessKeyId'], $aliyun_oss['accessKeySecret'], $aliyun_oss['endpoint']);
            //Determine whether the bucketname exists, and create it if it does not exist
            if( !$ossClient->doesBucketExist($aliyun_oss['bucket'])){
                $ossClient->createBucket($aliyun_oss['bucket']);
            }

            $res = $ossClient->uploadFile($aliyun_oss['bucket'],$savename,$true_src);

            if($res){
                $this->upload_log($aliyun_oss['url'].$savename,$savename,500, 'image/jpeg',2, $app, $desc='without');
                if(is_file($true_src)){ unlink($true_src); } //Delete Files
                return ['code' => 1, 'data' =>['originalName'=>$savename,'name'=>$savename,'url'=> $aliyun_oss['url'].$savename]];
            }else{
                return ['code' => 0, 'data' =>[],'msg'=>'upload failed'];
            }
        }catch (\Exception $e){
            return ['code'=>0,'data'=>[],'msg'=>$e->getMessage()];
        }
    }

    /**
      *@param image processing
      *@param savename ;//Path
      *@param site_w = 150;//allowable width
      *@param site_h = 300;//Allowable height
      *@param site_t = 0; // do not process 1 thumbnail 2 crop
      *@param site_left = 10;//Crop position from left to right
      *@param site_top = 0;//Crop position from top to bottom

    */
    public function proces($savename){
        $site_w = self::$site_w ;
        $site_h = self::$site_h ;
        $site_t = self::$site_t ;
        $site_left = self::$site_left;
        $site_top = self::$site_top;
        //whether to process
        if($site_t != 0){
            $image_w = getimagesize($savename)[0];
            $image_h = getimagesize($savename)[1];
            if($image_w > $site_w || $image_h > $site_h){

                $new_w = $image_w;
                $new_h = $image_h;

                if($image_w > $site_w){ $new_w = $site_w; }
                if($image_h > $site_h){ $new_h = $site_h; }

                if($site_t == 1){
                    $image = \think\Image::open($savename);
                    $image->thumb($new_w, $new_h)->save($savename);
                }else if($site_t == 2){
                    $image = \think\Image::open($savename);
                    $image->crop($new_w, $new_h, $site_left,$site_top)->save($savename);
                }
            }
        }
    }

    // upload log
    /*
        src path
        type Type 1 Local 2 Alibaba Cloud 3 Qiniu Cloud
    */
    private function upload_log($src,$path,$size,$mime,$type,$app,$desc=''){
        //if(is_file($src)){
            $UploadLog = new UploadLog;
                $add = [
                    //'uid'      => session('uid'),
                    'file_src'  => $src,
                    'file_type' => $mime,
                    'size'      => $size,
                    'path'      => $path,
                    'app'       => $app,
                    'type'      => $type,
                    'desc'      => $desc
                ];
            $UploadLog->save($add);
        //}
    }

}