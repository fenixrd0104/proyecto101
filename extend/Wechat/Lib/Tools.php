<?php

namespace Wechat\Lib;

use CURLFile;

/**
* WeChat interface general class
 *
 * @category WechatSDK
 * @subpackage library
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/05/28 11:55
 */
class Tools {

    /**
     * Generate random string
     * @param int $length
     * @param string $str
     * @return string
     */
    static public function createNoncestr($length = 32, $str = "") {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * Get signature
     * @param array $arrdata signature array
     * @param string $method signature method
     * @return bool|string signature value
     */
    static public function getSignature($arrdata, $method = "sha1") {
        if (!function_exists($method)) {
            return false;
        }
        ksort($arrdata);
        $params = array();
        foreach ($arrdata as $key => $value) {
            $params[] = "{$key}={$value}";
        }
        return $method(join('&', $params));
    }

    /**
     * Generate payment signature
     * @param array $option
     * @param string $partnerKey
     * @return string
     */
    static public function getPaySign($option, $partnerKey) {
        ksort($option);
        $buff = '';
        foreach ($option as $k => $v) {
            $buff .= "{$k}={$v}&";
        }
        return strtoupper(md5("{$buff}key={$partnerKey}"));
    }

    /**
     * XML encoding
     * @param mixed $data data
     * @param string $root root node name
     * @param string $item numeric index child node name
     * @param string $id numeric index child node key conversion attribute name
     * @return string
     */
    static public function arr2xml($data, $root = 'xml', $item = 'item', $id = 'id') {
        return "<{$root}>" . self::_data_to_xml($data, $item, $id) . "</{$root}>";
    }

    static private function _data_to_xml($data, $item = 'item', $id = 'id', $content = '') {
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "{$item} {$id}=\"{$key}\"";
            $content .= "<{$key}>";
            if (is_array($val) || is_object($val)) {
                $content .= self::_data_to_xml($val);
            } elseif (is_numeric($val)) {
                $content .= $val;
            } else {
                $content .= '<![CDATA[' . preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/", '', $val) . ']]>';
            }
            list($_key,) = explode(' ', $key . ' ');
            $content .= "</$_key>";
        }
        return $content;
    }


    /**
     * 将xml转为array
     * @param string $xml
     * @return array
     */
    static public function xml2arr($xml) {
        return json_decode(Tools::json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * Generate secure JSON data
     * @param array $array
     * @return string
     */
    static public function json_encode($array) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'), json_encode($array));
    }

    /**
     * Submit a request with get
     * @param $url
     * @return bool|mixed
     */
    static public function httpGet($url) {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * Submit a request by post
     * @param string $url
     * @param array|string $data
     * @return bool|mixed
     */
    static public function httpPost($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_string($value) && stripos($value, '@') === 0 && class_exists('CURLFile', FALSE)) {
                    $value = new CURLFile(realpath(trim($value, '@')));
                }
            }
            $data = http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * Using the certificate, submit the xml to the corresponding interface url by post
     * @param string $url POST submitted content
     * @param array $postdata the requested address
     * @param string $ssl_cer certificate Cer path | certificate content
     * @param string $ssl_key certificate key path | certificate content
     * @param int $second sets the request timeout
     * @return bool|mixed
     */
    static public function httpsPost($url, $postdata, $ssl_cer = null, $ssl_key = null, $second = 30) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        /* Require the result to be a string and output to the screen */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        /* set certificate */
        if (!is_null($ssl_cer) && file_exists($ssl_cer) && is_file($ssl_cer)) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $ssl_cer);
        }
        if (!is_null($ssl_key) && file_exists($ssl_key) && is_file($ssl_key)) {
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $ssl_key);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($postdata)) {
            foreach ($postdata as &$data) {
                if (is_string($data) && stripos($data, '@') === 0 && class_exists('CURLFile', FALSE)) {
                    $data = new CURLFile(realpath(trim($data, '@')));
                }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     *Read WeChat client IP
     * @return null|string
     */
    static public function getAddress() {
        foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP', 'REMOTE_ADDR') as $header) {
            if (!isset($_SERVER[$header]) || ($spoof = $_SERVER[$header]) === NULL) {
                continue;
            }
            sscanf($spoof, '%[^,]', $spoof);
            if (!filter_var($spoof, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $spoof = NULL;
            } else {
                return $spoof;
            }
        }
        return '0.0.0.0';
    }

    /**
     * Set cache, reload on demand
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return bool
     */
    static public function setCache($cachename, $value, $expired = 0) {
        return Cache::set($cachename, $value, $expired);
    }

    /**
     * Get cache, reload on demand
     * @param string $cachename
     * @return mixed
     */
    static public function getCache($cachename) {
        return Cache::get($cachename);
    }

    /**
     * Clear cache, reload on demand
     * @param string $cachename
     * @return bool
     */
    static public function removeCache($cachename) {
        return Cache::del($cachename);
    }

    /**
     * SDK log processing method
     * @param string $msg log line content
     * @param string $type log level
     */
    static public function log($msg, $type = 'MSG') {
        Cache::put($type . ' - ' . $msg);
    }

}
