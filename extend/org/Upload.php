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
namespace org;

class Upload
{
    /**
     * Default upload configuration
     * @var array
     */
    private $config = [
        // Allowed upload file MiMe types
        'mimes' => [],
        // upload file size limit (0-no limit)
        'maxSize' => 0,
        // Allow uploaded file suffixes
        'exts' => [],
        // Automatically save files in subdirectories
        'autoSub' => true,
        // Subdirectory creation method, [0]-function name, [1]-parameter, multiple parameters use array
        'subName' => ['date', 'Y-m-d'],
        // save the root path
        'rootPath' => './Uploads/',
        // save route
        'savePath' => '',
        // upload file naming rules, [0]-function name, [1]-parameter, multiple parameters use array
        'saveName' => ['uniqid', ''],
        // file save suffix, if empty, use the original suffix
        'saveExt' => '',
        // Is there an overwrite with the same name?
        'replace' => false,
        // Whether to generate hash code
        'hash'         => true,
        // Check if there is a callback in the file, if there is, return the file information array
        'callback' => false,
        // file upload driver e,
        'driver' => '',
        // upload driver configuration
        'driverConfig' => [],
    ];

    /**
     * Upload error message
     * @var string
     */
    private $error = ''; //Upload error message

    /**
     * Upload driver instance
     * @var Object
     */
    private $uploader;

    /**
     * Constructor, used to construct the upload instance
     * @param array $config configuration
     * @param string $driver The upload driver to be used LOCAL-local upload driver, FTP-FTP upload driver
     */
    public function __construct($config = [], $driver = '', $driverConfig = null)
    {
        /* get configuration */
        $this->config = array_merge($this->config, $config);

        /* set upload driver */
        $this->setDriver($driver, $driverConfig);

        /* Adjust configuration, convert string configuration parameters to array */
        if (!empty($this->config['mimes'])) {
            if (is_string($this->mimes)) {
                $this->config['mimes'] = explode(',', $this->mimes);
            }
            $this->config['mimes'] = array_map('strtolower', $this->mimes);
        }
        if (!empty($this->config['exts'])) {
            if (is_string($this->exts)) {
                $this->config['exts'] = explode(',', $this->exts);
            }
            $this->config['exts'] = array_map('strtolower', $this->exts);
        }
    }

    /**
     * Use $this->name to get configuration
     * @param string $name Configuration name
     * @return multitype configuration value
     */
    public function __get($name)
    {
        return $this->config[$name];
    }

    public function __set($name, $value)
    {
        if (isset($this->config[$name])) {
            $this->config[$name] = $value;
            if ('driverConfig' == $name) {
                // Reset the upload driver after changing the driver configuration
                //Note: You must choose to change the driver and then change the driver configuration
                $this->setDriver();
            }
        }
    }

    public function __isset($name)
    {
        return isset($this->config[$name]);
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
     * Upload a single file
     * @param array $file array of files
     * @return array file information after successful upload
     */
    public function uploadOne($file)
    {
        $info = $this->upload([$file]);
        return $info ? $info[0] : $info;
    }

    /**
     * upload files
     * @param file information array $files , usually $_FILES array
     */
    public function upload($files = '')
    {
        if ('' === $files) {
            $files = $_FILES;
        }
        if (empty($files)) {
            $this->error = 'No file uploaded! ';
            return false;
        }

        /* Check the upload root directory */
        if (!$this->uploader->checkRootPath($this->rootPath)) {
            $this->error = $this->uploader->getError();
            return false;
        }

        /* Check the upload directory */
        if (!$this->uploader->checkSavePath($this->savePath)) {
            $this->error = $this->uploader->getError();
            return false;
        }

        /* Check and upload files one by one */
        $info = [];
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
        }
        // Process the uploaded file array information
        $files = $this->dealFiles($files);
        foreach ($files as $key => $file) {
            $file['name'] = strip_tags($file['name']);
            if (!isset($file['key'])) {
                $file['key'] = $key;
            }

           /* Obtaining the file type by extension can solve the problem that the FLASH upload $FILES array returns the wrong file type */
            if (isset($finfo)) {
                $file['type'] = finfo_file($finfo, $file['tmp_name']);
            }

            /* Get the suffix of the uploaded file, allow uploading files without suffix */
            $file['ext'] = pathinfo($file['name'], PATHINFO_EXTENSION);

            /* File upload detection */
            if (!$this->check($file)) {
                continue;
            }

            /* Get the file hash */
            if ($this->hash) {
                $file['md5'] = md5_file($file['tmp_name']);
                $file['sha1'] = sha1_file($file['tmp_name']);
            }

            /* Call the callback function to check if the file exists */
            if (is_callable($this->callback)) {
                $data = call_user_func($this->callback, $file);
                if ($data) {
                    if (file_exists('.' . $data['path'])) {
                        $info[$key] = $data;
                        continue;
                    } elseif ($this->removeTrash) {
                        call_user_func($this->removeTrash, $data); //Remove garbage data
                    }
                }
            }

            /* Generate save file name */
            $savename = $this->getSaveName($file);
            if (false == $savename) {
                continue;
            } else {
                $file['savename'] = $savename;
            }

            /* Detect and create subdirectories */
            $subpath = $this->getSubPath($file['name']);
            if (false === $subpath) {
                continue;
            } else {
                $file['savepath'] = $this->savePath . $subpath;
            }

            /* Strict detection of image files */
            $ext = strtolower($file['ext']);
            if (in_array($ext, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                $imginfo = getimagesize($file['tmp_name']);
                if (empty($imginfo) || ('gif' == $ext && empty($imginfo['bits']))) {
                  $this->error = 'Illegal image file! ';
                    continue;
                }
            }

            /* Save the file and record the successfully saved file */
            if ($this->uploader->save($file, $this->replace)) {
                unset($file['error'], $file['tmp_name']);
                $info[$key] = $file;
            } else {
                $this->error = $this->uploader->getError();
            }
        }
        if (isset($finfo)) {
            finfo_close($finfo);
        }
        return empty($info) ? false : $info;
    }

    /**
     * Convert the uploaded file array variable to the correct way
     * @access private
     * @param array $files  uploaded file variable
     * @return array
     */
    private function dealFiles($files)
    {
        $fileArray = [];
        $n         = 0;
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $keys  = array_keys($file);
                $count = count($file['name']);
                for ($i = 0; $i < $count; $i++) {
                    $fileArray[$n]['key'] = $key;
                    foreach ($keys as $_key) {
                        $fileArray[$n][$_key] = $file[$_key][$i];
                    }
                    $n++;
                }
            } else {
                $fileArray = $files;
                break;
            }
        }
        return $fileArray;
    }

    /**
     * Set upload driver
     * @param string $driver driver name
     * @param array $config driver configuration
     */
    private function setDriver($driver = null, $config = null)
    {
        $driver = $driver ?: $this->config['driver'];
        $config = $config ?: $this->config['driverConfig'];
        var_dump($this->config);exit;
        $class = strpos($driver, '\\') ? $driver : '\\org\\upload\\driver' . ucfirst(strtolower($driver));
        $this->uploader = new $class($config);
    }

    /**
     * Check uploaded files
     * @param array $file file information
     */
    private function check($file)
    {
        /* File upload failed, catch error code */
        if ($file['error']) {
            $this->error($file['error']);
            return false;
        }

        /* invalid upload */
        if (empty($file['name'])) {
            $this->error = 'Unknown upload error! ';
        }

        /* Check if it is legal to upload */
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->error = 'Illegal file upload! ';
            return false;
        }

        /* check file size */
        if (!$this->checkSize($file['size'])) {
            $this->error = 'The upload file size does not match! ';
            return false;
        }

        /* Check file Mime type */
        //TODO: The mime type obtained by the files uploaded by FLASH is application/octet-stream
        if (!$this->checkMime($file['type'])) {
            $this->error = 'Upload file MIME type not allowed! ';
            return false;
        }

        /* Check file suffix */
        if (!$this->checkExt($file['ext'])) {
            $this->error = 'Upload file extension not allowed';
            return false;
        }

        /* pass detection */
        return true;
    }

    /**
     * Get error code information
     * @param string $errorNo error number
     */
    private function error($errorNo)
    {
        switch($errorNo) {
            case 1:
                $this->error = 'The uploaded file exceeds the limit of the upload_max_filesize option in php.ini! ';
                break;
            case 2:
                $this->error = 'The size of the uploaded file exceeds the value specified by the MAX_FILE_SIZE option in the HTML form! ';
                break;
            case 3:
                $this->error = 'The file was only partially uploaded! ';
                break;
            case 4:
            $this->error = 'No file was uploaded! ';
                break;
            case 6:
                $this->error = 'Could not find temp folder! ';
                break;
            case 7:
                $this->error = 'File writing failed! ';
                break;
            default:
                $this->error = 'Unknown upload error! ';
        }
    }

    /**
     * Check if the file size is legal
     * @param integer $size data
     */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (0 == $this->maxSize);
    }

    /**
     * Check if the uploaded file MIME type is legal
     * @param string $mime data
     */
    private function checkMime($mime)
    {
        return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->mimes);
    }

    /**
     * Check whether the uploaded file suffix is ​​legal
     * @param string $ext suffix
     */
    private function checkExt($ext)
    {
        return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->exts);
    }

    /**
     * Obtain the save file name according to the upload file naming rules
     * @param string $file file information
     */
    private function getSaveName($file)
    {
        $rule = $this->saveName;
        if (empty($rule)) {
            // keep the file name unchanged
            /* Solve pathinfo Chinese file name BUG */
            $filename = substr(pathinfo("_{$file['name']}", PATHINFO_FILENAME), 1);
            $savename = $filename;
        } else {
            $savename = $this->getName($rule, $file['name']);
            if (empty($savename)) {
                $this->error = 'File naming rule error! ';
                return false;
            }
        }

        /* File save suffix, support forcibly changing file suffix */
        $ext = empty($this->config['saveExt']) ? $file['ext'] : $this->saveExt;

        return $savename . '.' . $ext;
    }

    /**
     * Get the name of the subdirectory
     * @param array $file uploaded file information
     */
    private function getSubPath($filename)
    {
        $subpath = '';
        $rule    = $this->subName;
        if ($this->autoSub && !empty($rule)) {
            $subpath = $this->getName($rule, $filename) . '/';

            if (!empty($subpath) && !$this->uploader->mkdir($this->savePath . $subpath)) {
                $this->error = $this->uploader->getError();
                return false;
            }
        }
        return $subpath;
    }

    /**
     * Get the file or directory name according to the specified rules
     * @param array $rule rules
     * @param string $filename original filename
     * @return string file or directory name
     */
    private function getName($rule, $filename)
    {
        $name = '';
        if (is_array($rule)) {
            //array rules
            $func = $rule[0];
            $param = (array) $rule[1];
            foreach ($param as &$value) {
                $value = str_replace('__FILE__', $filename, $value);
            }
            $name = call_user_func_array($func, $param);
        } elseif (is_string($rule)) {
            //string rules
            if (function_exists($rule)) {
                $name = call_user_func($rule);
            } else {
                $name = $rule;
            }
        }
        return $name;
    }

}
