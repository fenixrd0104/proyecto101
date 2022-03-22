<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace org;

/* Thumbnail related constant definitions */
define('THINKIMAGE_THUMB_SCALING', 1); //Constant, identifies the scaling type of thumbnails
define('THINKIMAGE_THUMB_FILLED', 2); //Constant, identifies the fill type after the thumbnail is zoomed
define('THINKIMAGE_THUMB_CENTER', 3); //Constant, identifies the type of thumbnail center cropping
define('THINKIMAGE_THUMB_NORTHWEST', 4); //Constant, identifies the crop type in the upper left corner of the thumbnail
define('THINKIMAGE_THUMB_SOUTHEAST', 5); //Constant, identifies the crop type in the lower right corner of the thumbnail
define('THINKIMAGE_THUMB_FIXED', 6); //Constant, identifies the thumbnail fixed size zoom type

/* Watermark related constant definitions */
define('THINKIMAGE_WATER_NORTHWEST', 1); //constant, identifies the upper left watermark
define('THINKIMAGE_WATER_NORTH', 2); //Constant, center watermark on the logo
define('THINKIMAGE_WATER_NORTHEAST', 3); //Constant, identifies the watermark in the upper right corner
define('THINKIMAGE_WATER_WEST', 4); //constant, identifies the left-centered watermark
define('THINKIMAGE_WATER_CENTER', 5); //Constant, identifies the centered watermark
define('THINKIMAGE_WATER_EAST', 6); //constant, identifies the right-centered watermark
define('THINKIMAGE_WATER_SOUTHWEST', 7); //Constant, identifies the watermark in the lower left corner
define('THINKIMAGE_WATER_SOUTH', 8); //Constant, center the watermark under the logo
define('THINKIMAGE_WATER_SOUTHEAST', 9); //Constant, identifies the watermark in the lower right corner

/**
 * Image processing driver class, configurable image processing library
 * Currently supports GD library and imagick
 * @author McDonald Miaoer <zuojiazi.cn@gmail.com>
 */
class Image
{
    /**
     * Image resources
     * @var resource
     */
    private static $im;

    /**
     * Initialization method for instantiating an image processing object
     *
     * @param string $type The class library to use, the GD library is used by default
     * @param null $imgname
     *
     * @return resource
     */
    public static function init($type = 'Gd', $imgname = null)
    {
        /* Import the processing library, instantiate the image processing object */
        $class    = '\\org\\image\\driver\\' . ucwords($type);
        self::$im = new $class($imgname);
        return self::$im;
    }

    // Call the method of the driver class
    public static function __callStatic($method, $params)
    {
        self::$im || self::init();
        return call_user_func_array([self::$im, $method], $params);
    }
}
