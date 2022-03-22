<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app;

use think\App;
use think\facade\Db;
use think\Validate;
use think\facade\Request;
/**
 * Controller base class
 */
abstract class BaseController
{
    /**
     * Request instance
     * @var \think\Request
     */
    protected $request;

    /**
     * Applications
     * @var \think\App
     */
    protected $app;

    /**
     * Whether to batch verify
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * Controller middleware
     * @var array
     */
    protected $middleware = [];

    /**
     * Construction method
     * @access public
     * @param App $app application object
     */
    public function __construct(App $app)
    {
        Request::filter(['strip_tags','htmlspecialchars']);
        $this->app = $app;
        $this->request = $this->app->request;
        // controller initialization
        $this->initialize();
    }

    // initialize
    protected function initialize()
    {}

    /**
    * verify the data
     * @access protected
     * @param array $data data
     * @param string|array $validate validator name or validation rule array
     * @param array $message prompt information
     * @param bool $batch is batch validation
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // Support scene
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // Whether to batch verify
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * Get a list of configurations in the database
     * @return array configuration array
     */
    public static function configLists(){
        $map = array('status' => 1);
        $data = Db::name('Config')->where($map)->field('type,name,value')->select();
        $config = array();
        if($data){
            foreach ($data as $value) {
                $config[$value['name']] = self::parse($value['type'], $value['value']);
            }
        }
        return $config;
    }

    private static function parse($type, $value){
        switch ($type) {
            case 3: //parse the array
                $array = preg_split('/[,;\r\n]+/', trim($value, ",;\r\n"));
                if(strpos($value,':')){
                    $value  = array();
                    foreach ($array as $val) {
                        list($k, $v) = explode(':', $val);
                        $value[$k]   = $v;
                    }
                }else{
                    $value =    $array;
                }
                break;
        }
        return $value;
    }

}
