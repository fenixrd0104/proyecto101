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

class Local
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
     * Constructor for setting the upload root path
     */
    public function __construct($config = null)
    {

    }

    /**
     * Detect upload root directory
     * @param string $rootpath root directory
     * @return boolean true - test passed, false - test failed
     */
    public function checkRootPath($rootpath)
    {
        if (!(is_dir($rootpath) && is_writable($rootpath))) {
            $this->error = 'The upload root directory does not exist! Please try to create manually: ' . $rootpath;
            return false;
        }
        $this->rootPath = $rootpath;
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
            /* Check if the directory is writable */
            if (!is_writable($this->rootPath . $savepath)) {
                $this->error = 'Upload directory' . $savepath . ' is not writable! ';
                return false;
            } else {
                return true;
            }
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
        if (!$replace && is_file($filename)) {
            $this->error = 'A file with the same name exists' . $file['savename'];
            return false;
        }

        /* move file */
        if (!move_uploaded_file($file['tmp_name'], $filename)) {
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
        if (is_dir($dir)) {
            return true;
        }

        if (mkdir($dir, 0777, true)) {
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

}
