<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yangweijie <yangweijiester@gmail.com> <http://www.code-tech.diandian.com>
// +----------------------------------------------------------------------

namespace org\upload\driver;

use org\upload\driver\qiniu\QiniuStorage;

class Qiniu
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
        'secretKey' => '', //Qiniu server
        'accessKey' => '', //Qiniu user
        'domain' => '', //Qiniu password
        'bucket' => '', //space name
        'timeout' => 300, //timeout time
    ];

    /**
     * Constructor for setting the upload root path
     * @param array $config FTP configuration
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
        /* set the root directory */
        $this->qiniu = new QiniuStorage($config);
    }

    /**
     * Detect the upload root directory (Qiniu supports automatic creation of directories when uploading, and returns directly)
     * @param string $rootpath root directory
     * @return boolean true - test passed, false - test failed
     */
    public function checkRootPath($rootpath)
    {
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * Detect upload directory (Qiniu supports automatic creation of directory when uploading, and returns directly)
     * @param string $savepath upload directory
     * @return boolean test result, true-pass, false-fail
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * Create a folder (Qiniu supports automatic creation of a directory when uploading, and returns directly)
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
    public function save(&$file, $replace = true)
    {
        $file['name'] = $file['savepath'] . $file['savename'];
        $key          = str_replace('/', '_', $file['name']);
        $upfile       = [
            'name'     => 'file',
            'fileName' => $key,
            'fileBody' => file_get_contents($file['tmp_name']),
        ];
        $config      = [];
        $result      = $this->qiniu->upload($config, $upfile);
        $url         = $this->qiniu->downlink($key);
        $file['url'] = $url;
        return false === $result ? false : true;
    }

    /**
     * Get the last upload error information
     * @return string error message
     */
    public function getError()
    {
        return $this->qiniu->errorStr;
    }
}
