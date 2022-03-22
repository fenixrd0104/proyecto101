<?php
// +----------------------------------------------------------------------
// | message sending generic class
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | date:  2017.9. 11
// +----------------------------------------------------------------------
// | Author: wpy
// +----------------------------------------------------------------------
namespace app\common\service;

use think\facade\Db;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Msg {
   private static $msginfo; //Basic information of the message to be sent (including sending permissions, templates, etc.)
       private static $config; //Basic configuration of the website
       private static $back = array('code'=>0,'msg'=>''); //return information
   
       private static function start($id){
           self::$config = cache('db_config_data');
           self::$msginfo = Db::name("msg_config")->where('id',$id)->find();
           if(empty(self::$msginfo)){
               self::$back['msg'] = 'The message ID sent does not exist';
            return self::$back;
        }
    }

    //Send SMS without logging in
    static function send_sms($id,$phone,$param,$tyid=1){
        /*      self::start($id);
                if(self::check_param(unserialize(self::$msginfo['param']),$param) == false){
                    return self::$back;
                }
                $tpinfo = self::set_tpinfo('sms',$param);*/
        if($tyid==1) {
            $dossc = substr($phone,0,2);
            if ($dossc == 86) {
                $phone = substr($phone,2);
                $res = self::sms($param['code'], $phone, $param);
            } else {
                $res = self::gwsms($param['code'], $phone, $param);
            }
        }elseif ($tyid==2){
            $res = self::sms($param['code'], $phone, $param);
        }

        $back['code'] = self::$back['detail']['sms']['code'];
        $back['msg'] = self::$back['detail']['sms']['msg'];
        return $back;
    }

    //Send email without logging in
    static function send_email($id,$email,$param){
        if($id){
            self::start($id);
            if(self::check_param(unserialize(self::$msginfo['param']),$param) == false){
                return self::$back;
            }
            $tpinfo = self::set_tpinfo('email',$param);
        }else{
            self::$config =config('config');
            $tpinfo['title']=$param['title'];
            $tpinfo['content']=$param['content'];
        }
        $res = self::email($email,$tpinfo['title'],$tpinfo['content']);
        $back['code'] = self::$back['detail']['email']['code'];
        $back['msg'] = self::$back['detail']['email']['info'];
        return $back;
    }


   /*Send SMS in Palm Jun Media*/
       //$code SMS platform template id
       //$phone is the phone number to receive the message
       //$param is an array corresponding to the predefined variables in the specified template
       private static function sms($code,$phone,$param){
           if(empty($phone)){
               self::$back['detail']['sms']['code'] = 0;
               self::$back['detail']['sms']['msg'] = 'Mobile phone number cannot be empty';
        }else{


            /**
             * Media SMS platform
             */
            if(isset($param['content'])){
                $content = $param['content'];
            }else{
                //$content = "Your verification code is:" . $code . "Please don't reveal the verification code to others.";
                $content = "Your verification code is ".$code.".";
            }

            $time=date('YmdHis',time());//Timestamp, the current time string of the system, year, month, day, hour, minute, second
            $md5=md5("krcshop"."krcshop00".$time);//MD5 encryption, account + password + timestamp
            $content="[WE Mall]".$content;//SMS content

            $curl = curl_init();
           //Set the fetched url
            curl_setopt($curl, CURLOPT_URL, 'http://120.77.14.55:8888/v2sms.aspx?');
            / / Set the information of the header file as the data stream output
            //curl_setopt($curl, CURLOPT_HEADER, 1);
            //Set the obtained information to be returned in the form of a file stream, rather than output directly.
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //Set the post method to submit
            curl_setopt($curl, CURLOPT_POST, 1);
            //set post data
            $post_data = array(
                'action' => 'send',
                'userid' => '12668',
                'timestamp'=>$time,
                'sign'=> $md5,
                'mobile' => $phone,
                'content' => $content,
                'sendtime'=>'',
                'extno'=>'',
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
            //Excuting an order
            $data = curl_exec($curl);
            //close the URL request
            curl_close($curl);
            //display the obtained data

            $result=$data;
            if (preg_match("|<returnstatus>Success|is", $result)) {
                self::$back['detail']['sms']['code'] = 1;
                self::$back['detail']['sms']['msg'] = 'SMS sent successfully';
            }else {
                self::$back['detail']['sms']['code'] = 0;
                self::$back['detail']['sms']['msg'] = 'SMS failed to send';
            }


        }
    }

    /*Send text messages from Zhangjun Media*/
        //$code SMS platform template id
        //$phone is the phone number to receive the message
        //$param is an array corresponding to the predefined variables in the specified template
        private static function gwsms($code,$phone,$param){
            if(empty($phone)){
                self::$back['detail']['sms']['code'] = 0;
                self::$back['detail']['sms']['msg'] = 'Mobile phone number cannot be empty';
        }else{

           $cpid = '0vk1c8';
            $cppwd = 'HCbzpOA4';
            $to = $phone;
            if(isset($param['content'])){
                $content = $param['content'];
            }else{
                //$content = "Your verification code is:" . $code . "Please don't reveal the verification code to others.";
                $content = "Your verification code is ".$code.".";
            }
            $content="[WE MALL]".$content;//SMS content
            $c = urlencode($content);
            // http interface, supports https access, if you have security requirements, you can visit https at the beginning
            $api = "http://api2.santo.cc/submit?command=MT_REQUEST&cpid={$cpid}&cppwd={$cppwd}&da={$to}&sm={$c}";
            // It is recommended to record $resp to the log file, $resp contains detailed error information
            try {
                $resp = file_get_contents($api);
            } catch(Exception $e){
                self::$back['detail']['sms']['code'] = 0;
                self::$back['detail']['sms']['msg']   = $e->getMessage();
            }

            preg_match('/mtmsgid=(.*?)&/', $resp, $re);
            if (!empty($re) && count($re) >= 2){
                self::$back['detail']['sms']['code'] = 1;
                self::$back['detail']['sms']['msg'] = 'SMS sent successfully';
            }else{
                self::$back['detail']['sms']['code'] = 0;
                self::$back['detail']['sms']['msg'] = 'SMS sending failed';
            }

        }
    }



    /* Converged communication (internal/external) SMS sending*/
    //$code SMS platform template id
    //$phone the phone number to receive the message
    //$param is an array corresponding to the predefined variables in the specified template
    /* private static function sms($code,$phone,$param){
         if(empty($phone)){
             self::$back['detail']['sms']['code'] = 0;
             self::$back['detail']['sms']['info'] = 'Mobile phone number cannot be empty';
         }else{

            //if(!isMobile($phone)){
            // self::$back['detail']['sms']['code'] = 0;
            // self::$back['detail']['sms']['info'] = 'The phone number is invalid';
            // }else{

            // }

             if(isset($param['content'])){
                $content = '[KRC Cross-border Mall]'.$param['content'];
             }else{
                 $content = '[KRC Cross-border Mall]'.' Hello, your verification code is: ' . $code . ', valid for 5 minutes, please do not disclose it to others. ';
             }
             // Determine where the phone number is
             if(substr($phone,0,2) == 86){
                 $url ='http://service2.winic.org/service.asmx/SendMessages?';
             }else{
                 $url="http://service2.winic.org:8003/Service.asmx/SendInternationalMessages?";
             }

             $data = "uid=%s&pwd=%s&tos=%s&msg=%s&otime=";
             $id = '15178768653';//account
             $pwd = '15178768653';//password
             $to = $phone;
             $content = urlencode($content);
             $rdata = sprintf($data, $id, $pwd, $to, $content);
             $ch = curl_init();
             curl_setopt($ch, CURLOPT_POST,1);
             curl_setopt($ch, CURLOPT_POSTFIELDS,$rdata);
             curl_setopt($ch, CURLOPT_URL,$url);
             curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
             $result = curl_exec($ch);
             if (preg_match("/[0-9]{16}/", $result)) {
                 self::$back['detail']['sms']['code'] = 1;
                self::$back['detail']['sms']['msg'] = 'Send successfully';
             }else {
                 self::$back['detail']['sms']['code'] = 0;
                 self::$back['detail']['sms']['msg'] = 'Send failed'.$result;
             }

         }
     }*/


   // send mail
    private static function email($email,$title,$content){
        if(empty($email)){
            self::$back['detail']['email']['code'] = 0;
            self::$back['detail']['email']['info'] = 'Email address cannot be empty';
        }else{
            if(!isEmail($email)){
                self::$back['detail']['email']['code'] = 0;
                self::$back['detail']['email']['info'] = 'The email address is invalid';
            }else{
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP(); // Set mailer to use SMTP
                    $mail->CharSet = "utf8"; // content format
                    $mail->Host = self::$config['email_smtp']; // sender STMP server address
                    $mail->SMTPAuth = true; // Whether to use authentication
                    $mail->Username = self::$config['email_address']; // sender's email address
                    $mail->Password = self::$config['email_passwd']; // SMTP password
                    $mail->SMTPSecure = 'ssl'; // use ssl protocol
                    $mail->Port = self::$config['email_port']; // port

                    $mail->setFrom(self::$config['email_address'],self::$config['email_sender']); // set sender name
                    $mail->addAddress($email); // recipient address
                    $mail->addReplyTo(self::$config['email_address'],self::$config['email_sender']); // Email reply address

                    $mail->isHTML(true); // Set email format to HTML
                    $mail->Subject = $title; // Mail title
                    $mail->Body = $content; // Mail content
                    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';// This is the body content displayed in plain text mode. If Html mode is not supported, this is used. Basically useless

                    $mail->send();
                    self::$back['detail']['email']['code'] = 1;
                    self::$back['detail']['email']['info'] = 'Email sent';
                } catch (Exception $e) {
                    self::$back['detail']['email']['code'] = 0;
                    self::$back['detail']['email']['info'] = 'Mail sending failed: ' . $mail->ErrorInfo;
                }
            }
        }
    }
    //send Message
    private static function mail($send_uid,$receive_uid,$title,$content){
        $back = self::insert_msg($send_uid,$receive_uid,$title,$content);
        if($back){
            self::$back['detail']['mail']['code'] = 1;
            self::$back['detail']['mail']['info'] = 'Internal message has been sent';
        }else{
            self::$back['detail']['mail']['code'] = 0;
            self::$back['detail']['mail']['info'] = 'Failed to send internal message';
        }
    }

    //Send WeChat push
    private static function weichat($uid,$tpid,$data){
        $wx_config = array(
            'token'         =>  self::$config['wx_token'],
            'appid'         =>  self::$config['wx_appid'],
            'appsecret'     =>  self::$config['wx_appSecret'],
            'encodingaeskey'=>  self::$config['wx_encodingAESKey']
        );

        $we=new \Wechat\WechatReceive($wx_config);

        //Get user openid
        $openid = Db::name("oauth_user")->where(array('from'=>'weixin','uid'=>$uid))->value("openid");
        if(!isset($openid) || !$openid){
            self::$back['detail']['weichat']['code'] = 0;
            self::$back['detail']['weichat']['info']   = 'User openid does not exist';
            return;
        }
        //get template id
        //$template_id = $we->addTemplateMessage($tpid);
        //send messages
        if(!$tpid){
            self::$back['detail']['weichat']['code'] = 0;
            self::$back['detail']['weichat']['info'] = 'The template id is not filled in';
            return;
        }
        //Construct the key-value pair of the WeChat template into an array that conforms to the WeChat interface
        $new_data = array();
        foreach($data as $k=>$v){
            $new_data[$v['param_name']] = array("value" => $v["param_val"],"color" => $v['param_color']);
        }
        $back = $we->sendTemplateMessage([
            'touser'=>$openid,
            'template_id'=>$tpid,
            //'url'=>url('weixin/shanghu/index','',true,true),
            'data'=>$new_data
        ]);
        if($back['errcode'] === 0){
            self::$back['detail']['weichat']['code'] = 1;
           self::$back['detail']['weichat']['info'] = 'WeChat message push successfully';
        }else{
            self::$back['detail']['weichat']['code'] = 0;
            self::$back['detail']['weichat']['info'] = 'WeChat message'.$back['errmsg'];
        }
    }

    //Insert data into the message table
    private static function insert_msg($send_uid,$receive_uid,$title,$content){
        $data = array(
            'receive_uid' => $receive_uid,
            'send_uid'    => $send_uid,
            'title'       => $title,
            'content'     => serialize($content),
            'status'      => 0,
            'send_time'   => time()
        );
        return Db :: name("member_msg")->insert($data);
    }
    //Check whether the incoming parameter and the message preset variable are consistent, match to the left
    private static function check_param($tp_param_str,$param){
        $tp_param =explode(',',preg_replace("/\s/","",$tp_param_str));
        if(!empty($tp_param)){
            foreach($tp_param as $k=>$v){
                preg_match_all("/#(.*)#/",$v,$r);
                if(!empty($r[1])){
                    $param_key[] = $r[1][0];
                }else{
                    self::$back['msg'] = 'Background message predefined variable format error';
                    return false;
                    break;
                }

            }
            if(!empty($param)){
                $data_key = array_keys($param);
                foreach($param_key as $k=>$v){
                    if(!in_array($v,$data_key)){
                        self::$back['msg'] = 'The incoming parameter array does not correspond to the message preset variable';
                        return false;
                        break;
                    }
                }
            }
        }
        return true;
    }
    //Check whether the current sending channel template information is empty
    private static function check_tpinfo($type){
        if(!empty(unserialize(self::$msginfo[$type]))){
            return true;
        }else{
            self::$back['detail'][$type]['code'] = 0;
            self::$back['detail'][$type]['info'] = $type.'The template is empty';
        }
    }
    //Replace the predetermined variable in the template content
    private static function set_tpinfo($type,$param){
        $tpinfo = unserialize(self::$msginfo[$type]);
        if($type == 'weichat'){
            //Wechat template content is different from other template content, it is not a string. is an array
            foreach($param as $k=>$v){
                foreach($tpinfo['param_data'] as $k2=>$v2){
                    $tpinfo['param_data'][$k2]['param_val'] = str_replace("#$k#",$v,$v2['param_val']);
                }
            }
        }else{
            foreach($param as $k=>$v){
                $tpinfo['content'] = str_replace("#$k#",$v,$tpinfo['content']);
            }
        }

        return $tpinfo;
    }

}
