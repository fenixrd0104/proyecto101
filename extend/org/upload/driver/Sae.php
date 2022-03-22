<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: luofei614<weibo.com/luofei614>
// +----------------------------------------------------------------------

namespace org\upload\driver;

class Sae
{
    /**
    * Domain of Storage
     * @var string
     */
    private $domain = '';

    private $rootPath = '';

    /**
     * Local upload error message
     * @var string
     */
    private $error = '';

    /**
     * Constructor, set the domain of the storage. If there is a configuration, the domain is the configuration item. If there is no domain, it is the directory name of the first path.
     * @param mixed $config upload configuration
     */
    public function __construct($config = null)
    {
        if (is_array($config) && !empty($config['domain'])) {
            $this->domain = strtolower($config['domain']);
        }
    }

    /**
     * Detect upload root directory
     * @param string $rootpath root directory
     * @return boolean true - test passed, false - test failed
     */
    public function checkRootPath($rootpath)
    {
        $rootpath = trim($rootpath, './');
        if (!$this->domain) {
            $rootpath = explode('/', $rootpath);
            $this->domain = strtolower(array_shift($rootpath));
            $rootpath = implode('/', $rootpath);
        }

        $this->rootPath = $rootpath;
        $st = new \SaeStorage();
        if (false === $st->getDomainCapacity($this->domain)) {
            $this->error = 'You don't seem to have created the storage domain[' . $this->domain . ']';
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
        $filename = ltrim($this->rootPath . '/' . $file['savepath'] . $file['savename'], '/');
        $st       = new \SaeStorage();
        /* Do not overwrite files with the same name */
        if (!$replace && $st->fileExists($this->domain, $filename)) {
            $this->error = 'A file with the same name exists' . $file['savename'];
            return false;
        }

        /* move file */
        if (!$st->upload($this->domain, $filename, $file['tmp_name'])) {
            $this->error = 'File upload and save error! [' . $st->errno() . ']:' . $st->errmsg();
            return false;
        } else {
            $file['url'] = $st->getUrl($this->domain, $filename);
        }
        return true;
    }

    public function mkdir()
    {
        return true;
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
