<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace org\upload\driver;

use think\Exception;

class Ftp
{
    /**
     * Upload file root directory
     * @var string
     */
    private $rootPath;

    /**
     * Local upload error message
     * @var string
     */
    private $error = ''; //Upload error message

    /**
     * FTP connection
     * @var resource
     */
    private $link;

    private $config = [
        'host' => '', //server
        'port' => 21, //port
        'timeout' => 90, //timeout time
        'username' => '', //username
        'password' => '', //password
    ];

    /**
     * Constructor for setting the upload root path
     * @param array $config FTP configuration
     */
    public function __construct($config)
    {
        /* Default FTP configuration */
        $this->config = array_merge($this->config, $config);

        /* Log in to the FTP server */
        if (!$this->login()) {
            throw new Exception($this->error);
        }
    }

    /**
     * Detect upload root directory
     * @param string $rootpath root directory
     * @return boolean true - test passed, false - test failed
     */
    public function checkRootPath($rootpath)
    {
        /* set the root directory */
        $this->rootPath = ftp_pwd($this->link) . '/' . ltrim($rootpath, '/');

        if (!@ftp_chdir($this->link, $this->rootPath)) {
            $this->error = 'The upload root directory does not exist! ';
            return false;
        }
        return true;
    }

    /**
     * Detect upload directory
     * @param string $savepath upload directory
     * @return boolean test result, true-pass, false-fail
     */
    public function checkSavePath($savepath)
    {
        /* Detect and create directory */
        if (!$this->mkdir($savepath)) {
            return false;
        } else {
            //TODO: Check if the directory is writable
            return true;
        }
    }

    /**
     * save the specified file
     * @param array $file saved file information
     * @param boolean $replace whether to overwrite the file with the same name
     * @return boolean save state, true-success, false-failure
     */
    public function save($file, $replace = true)
    {
        $filename = $this->rootPath . $file['savepath'] . $file['savename'];

        /* Do not overwrite files with the same name */
        // if (!$replace && is_file($filename)) {
        // $this->error = 'A file with the same name exists' . $file['savename'];
        // return false;
        // }

        /* move file */
        if (!ftp_put($this->link, $filename, $file['tmp_name'], FTP_BINARY)) {
            $this->error = 'File upload and save error! ';
            return false;
        }
        return true;
    }

    /**
     * Create a directory
     * @param string $savepath directory to create
     * @return boolean create state, true-success, false-failure
     */
    public function mkdir($savepath)
    {
        $dir = $this->rootPath . $savepath;
        if (ftp_chdir($this->link, $dir)) {
            return true;
        }

        if (ftp_mkdir($this->link, $dir)) {
            return true;
        } elseif ($this->mkdir(dirname($savepath)) && ftp_mkdir($this->link, $dir)) {
            return true;
        } else {
            $this->error = "Failed to create directory {$savepath}!";
            return false;
        }
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
     * Login to FTP server
     * @return boolean true - login successful, false - login failed
     */
    private function login()
    {
        extract($this->config);
        $this->link = ftp_connect($host, $port, $timeout);
        if ($this->link) {
            if (ftp_login($this->link, $username, $password)) {
                return true;
            } else {
                $this->error = "Cannot log in to FTP server: username - {$username}";
            }
        } else {
            $this->error = "Cannot connect to FTP server: {$host}";
        }
        return false;
    }

    /**
     * Destructor method for disconnecting the current FTP connection
     */
    public function __destruct()
    {
        ftp_close($this->link);
    }

}
