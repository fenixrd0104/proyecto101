<?php

/**
  * PKCS7 algorithm encryption and decryption
  * @category WechatSDK
  * @subpackage library
  * @date 2016/06/28 11:59
  */
 class PKCS7Encoder {
 
     public static $block_size = 32;
 
     /**
      * Pad the plaintext that needs to be encrypted
      * @param string $text The plaintext that needs to be filled and filled
      * @return string Complete plaintext string
      */
     function encode($text) {
         $amount_to_pad = PKCS7Encoder::$block_size - (strlen($text) % PKCS7Encoder::$block_size);
         if ($amount_to_pad == 0) {
             $amount_to_pad = PKCS7Encoder::$block_size;
         }
         $pad_chr = chr($amount_to_pad);
         $tmp = "";
         for ($index = 0; $index < $amount_to_pad; $index++) {
             $tmp .= $pad_chr;
         }
         return $text . $tmp;
     }
 
     /**
      * Complement and delete the decrypted plaintext
      * @param string $text decrypted plaintext
      * @return string delete the plaintext after padding
     */
    function decode($text) {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > PKCS7Encoder::$block_size) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

}

/**
* Encryption and decryption of messages received and pushed to the public platform
 * @category WechatSDK
 * @subpackage library
 * @date 2016/06/28 11:59
 */
class Prpcrypt {

    public $key;

    function __construct($k) {
        $this->key = base64_decode($k . "=");
    }

    /**
     * Encrypt plaintext
     * @param string $text the plaintext to be encrypted
     * @param string $appid official account APPID
     * @return string encrypted ciphertext
     */
    public function encrypt($text, $appid) {
        try {
            //Get a 16-bit random string and fill it before the plaintext
            $random = $this->getRandomStr();//"aaaabbbbccccdddd";
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            $iv = substr($this->key, 0, 16);
            $pkc_encoder = new PKCS7Encoder;
            $text = $pkc_encoder->encode($text);
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', substr($this->key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
            return array(ErrorCode::$OK, $encrypted);
        } catch (Exception $e) {
            return array(ErrorCode::$EncryptAESError, null);
        }
    }

    /**
     * Decrypt the ciphertext
     * @param string $encrypted The ciphertext to be decrypted
     * @param string $appid official account APPID
     * @return string decrypted plaintext
     */
    public function decrypt($encrypted, $appid) {
        try {
            $iv = substr($this->key, 0, 16);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', substr($this->key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
        } catch (Exception $e) {
            return array(ErrorCode::$DecryptAESError, null);
        }
        try {
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);
            if (strlen($result) < 16) {
                return "";
            }
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
            if (!$appid) {
                $appid = $from_appid;
            }
        } catch (Exception $e) {
            return array(ErrorCode::$IllegalBuffer, null);
        }
        return array(0, $xml_content, $from_appid);
    }

    /**
    * Randomly generate a 16-bit string
     * @return string generated string
     */
    function getRandomStr() {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

}

/**
 * For internal use only
 * errCode code not used for official API interface
 * Class ErrorCode
 */
class ErrorCode {

    public static $OK = 0;
    public static $ValidateSignatureError = 40001;
    public static $ParseXmlError = 40002;
    public static $ComputeSignatureError = 40003;
    public static $IllegalAesKey = 40004;
    public static $ValidateAppidError = 40005;
    public static $EncryptAESError = 40006;
    public static $DecryptAESError = 40007;
    public static $IllegalBuffer = 40008;
    public static $EncodeBase64Error = 40009;
    public static $DecodeBase64Error = 40010;
    public static $GenReturnXmlError = 40011;
    public static $errCode = array(
        '0' => 'Processing successful',
        '40001' => 'Signature verification failed',
        '40002' => 'Failed to parse xml',
        '40003' => 'Failed to calculate signature',
        '40004' => 'Illegal AESKey',
        '40005' => 'Verification of AppID failed',
        '40006' => 'AES encryption failed',
        '40007' => 'AES decryption failed',
        '40008' => 'The xml sent by the public platform is illegal',
        '40009' => 'Base64 encoding failed',
        '40010' => 'Base64 decoding failed',
        '40011' => 'Failed to generate return package xml for public account'
    );

    /**
     * Get the error message content
     * @param string $err
     * @return bool
     */
    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        }
        return false;
    }

}
