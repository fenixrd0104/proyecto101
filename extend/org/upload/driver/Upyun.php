<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: McDonald's <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace org\upload\driver;

class Upyun
{
    /**
     * Upload file root directory
     * @var string
     */
    private $rootPath;

    /**
     * Upload error message
     * @var string
     */
    private $error = '';

    private $config = [
        'host' => '', // shot the cloud server again
        'username' => '', // Take another cloud user
        'password' => '', //take cloud password again
        'bucket' => '', //space name
        'timeout' => 90, //timeout time
    ];

    /**
     * Constructor for setting the upload root path
     * @param array $config FTP configuration
     */
    public function __construct($config)
    {
        /* Default FTP configuration */
        $this->config = array_merge($this->config, $config);
        $this->config['password'] = md5($this->config['password']);
    }

    /**
     * Detect the upload root directory (supports automatic creation of directories when uploading to the cloud, and returns directly)
     * @param string $rootpath root directory
     * @return boolean true - test passed, false - test failed
     */
    public function checkRootPath($rootpath)
    {
        /* set the root directory */
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * Detect the upload directory (supports automatic creation of a directory when uploading to the cloud, and returns directly)
     * @param string $savepath upload directory
     * @return boolean test result, true-pass, false-fail
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * Create a folder (supports automatic creation of a directory when uploading to the cloud, and returns directly)
     * @param string $savepath directory name
     * @return boolean true - creation succeeded, false - creation failed
     */
    public function mkdir($savepath)
    {
        return true;
    }

    /**
     * save the specified file
     * @param array $file saved file information
     * @param boolean $replace whether to overwrite the file with the same name
     * @return boolean save state, true-success, false-failure
     */
    public function save($file, $replace = true)
    {
        $header['Content-Type'] = $file['type'];
        $header['Content-MD5']  = $file['md5'];
        $header['Mkdir']        = 'true';
        $resource               = fopen($file['tmp_name'], 'r');

        $save = $this->rootPath . $file['savepath'] . $file['savename'];
        $data = $this->request($save, 'PUT', $header, $resource);
        return false === $data ? false : true;
    }

    /**
     * Get the last upload error information
     * @return string error message
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Request to shoot cloud server again
     * @param string $path the requested PATH
     * @param string $method request method
     * @param array $headers request headers
     * @param resource $body upload file resource
     * @return boolean
     */
    private function request($path, $method, $headers = null, $body = null)
    {
        $uri = "/{$this->config['bucket']}/{$path}";
        $ch  = curl_init($this->config['host'] . $uri);

        $_headers = ['Expect:'];
        if (!is_null($headers) && is_array($headers)) {
            foreach ($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }

        $length = 0;
        $date   = gmdate('D, d M Y H:i:s \G\M\T');

        if (!is_null($body)) {
            if (is_resource($body)) {
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);

                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            } else {
                $length = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        } else {
            array_push($_headers, "Content-Length: {$length}");
        }

        array_push($_headers, 'Authorization: ' . $this->sign($method, $uri, $date, $length));
        array_push($_headers, "Date: {$date}");

        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config['timeout']);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ('PUT' == $method || 'POST' == $method) {
            curl_setopt($ch, CURLOPT_POST, 1);
        } else {
            curl_setopt($ch, CURLOPT_POST, 0);
        }

        if ('HEAD' == $method) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        list($header, $body) = explode("\r\n\r\n", $response, 2);

        if (200 == $status) {
            if ('GET' == $method) {
                return $body;
            } else {
                $data = $this->response($header);
                return count($data) > 0 ? $data : true;
            }
        } else {
            $this->error($header);
            return false;
        }
    }

    /**
    * Get response data
     * @param string $text response header string
     * @return array response data list
     */
    private function response($text)
    {
        $headers = explode("\r\n", $text);
        $items   = [];
        foreach ($headers as $header) {
            $header = trim($header);
            if (strpos($header, 'x-upyun') !== false) {
                list($k, $v)     = explode(':', $header);
                $items[trim($k)] = in_array(substr($k, 8, 5), ['width', 'heigh', 'frame']) ? intval($v) : trim($v);
            }
        }
        return $items;
    }

    /**
     * Generate request signature
     * @param string $method request method
     * @param string $uri Request URI
     * @param string $date request time
     * @param integer $length request content size
     * @return string request signature
     */
    private function sign($method, $uri, $date, $length)
    {
        $sign = "{$method}&{$uri}&{$date}&{$length}&{$this->config['password']}";
        return 'UpYun ' . $this->config['username'] . ':' . md5($sign);
    }

    /**
     * Get request error information
     * @param string $header Request return header information
     */
    private function error($header)
    {
        list($status, $stash)     = explode("\r\n", $header, 2);
        list($v, $code, $message) = explode(" ", $status, 3);
        $message                  = is_null($message) ? 'File Not Found' : "[{$status}]:{$message}";
        $this->error              = $message;
    }

}
