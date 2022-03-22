<?php

namespace Wechat;

use Wechat\Lib\Cache;

/**
 * Register the SDK auto-loading mechanism
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/10/26 10:21
 */
spl_autoload_register(function ($class) {
    if (0 === stripos($class, 'Wechat\\')) {
        $filename = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        file_exists($filename) && require($filename);
    }
});

/**
 * WeChat SDK Loader
 * @author Anyon <zoujingli@qq.com>
 * @date 2016-08-21 11:06
 */
class Loader {

    /**
     * Event registration function
     * @var array
     */
    static public $callback = array();

    /**
     * Configuration parameters
     * @var array
     */
    static protected $config = array();

    /**
     * Object cache
     * @var array
     */
    static protected $cache = array();

    /**
     * Dynamically register SDK event handlers
     * @param string $event event name (getAccessToken|getJsTicket)
     * @param string $method processing method (can be a normal method or a method in a class)
     * @param string|null $class Handling object (class instance that can be used directly)
     */
    static public function register($event, $method, $class = NULL) {
        if (!empty($class) && class_exists($class, FALSE) && method_exists($class, $method)) {
            self::$callback[$event] = array($class, $method);
        } else {
            self::$callback[$event] = $method;
        }
    }

    /**
     * Get WeChat SDK interface object (alias function)
     * @param string $type interface type (Card|Custom|Device|Extends|Media|Menu|Oauth|Pay|Receive|Script|User|Poi)
     * @param array $config SDK configuration (token, appid, appsecret, encodingaeskey, mch_id, partnerkey, ssl_cer, ssl_key, qrc_img)
     * @return WechatCard|WechatCustom|WechatDevice|WechatExtends|WechatMedia|WechatMenu|WechatOauth|WechatPay|WechatPoi|WechatReceive|WechatScript|WechatService|WechatUser
     */
    static public function & get_instance($type, $config = array()) {
        return self::get($type, $config);
    }

    /**
     * Get WeChat SDK interface object
     * @param string $type interface type (Card|Custom|Device|Extends|Media|Menu|Oauth|Pay|Receive|Script|User|Poi)
     * @param array $config SDK configuration (token, appid, appsecret, encodingaeskey, mch_id, partnerkey, ssl_cer, ssl_key, qrc_img)
     * @return WechatCard|WechatCustom|WechatDevice|WechatExtends|WechatMedia|WechatMenu|WechatOauth|WechatPay|WechatPoi|WechatReceive|WechatScript|WechatService|WechatUser
     */
    static public function & get($type, $config = array()) {
        $index = md5(strtolower($type) . md5(json_encode(self::$config)));
        if (!isset(self::$cache[$index])) {
            $basicName = 'Wechat' .ucfirst(strtolower($type));
            $className = "\\Wechat\\{$basicName}";
            // No namespace alias of the registered class, compatible with older SDK versions without namespace
            !class_exists($basicName, FALSE) && class_alias($className, $basicName);
            self::$cache[$index] = new $className(self::config($config));
        }
        return self::$cache[$index];
    }

    /**
     * Set configuration parameters
     * @param array $config
     * @return array
     */
    static public function config($config = array()) {
        !empty($config) && self::$config = array_merge(self::$config, $config);
        if (!empty(self::$config['cachepath'])) {
            Cache::$cachepath = self::$config['cachepath'];
        }
        if (empty(self::$config['component_verify_ticket'])) {
            self::$config['component_verify_ticket'] = Cache::get('component_verify_ticket');
        }
        if (empty(self::$config['token']) && !empty(self::$config['component_token'])) {
            self::$config['token'] = self::$config['component_token'];
        }
        if (empty(self::$config['appsecret']) && !empty(self::$config['component_appsecret'])) {
            self::$config['appsecret'] = self::$config['component_appsecret'];
        }
        if (empty(self::$config['encodingaeskey']) && !empty(self::$config['component_encodingaeskey'])) {
            self::$config['encodingaeskey'] = self::$config['component_encodingaeskey'];
        }
        return self::$config;
    }

}
