<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: McDonald's <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace org\image\driver;

use Exception;

class Gd
{
    /**
     * Image resource object
     *
     * @var resource
     */
    private $im;

    /**
     * @var $git org\image\driver\Gif
     */
    private $gif;

    /**
     * Image information, including width, height, type, mime, size
     *
     * @var array
     */
    private $info;

    /**
     * Constructor, which can be used to open an image
     *
     * @param string $imgname image path
     */
    public function __construct($imgname = null)
    {
        $imgname && $this->open($imgname);
    }

    /**
     * open an image
     *
     * @param string $imgname image path
     *
     * @return $this
     * @throws \Exception
     */
    public function open($imgname)
    {
        //detect image file
        if (!is_file($imgname)) {
            throw new \Exception('non-existent image file');
        }

        //Get image information
        $info = getimagesize($imgname);

        //Check image legitimacy
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new Exception('Illegal image file');
        }

        //set image information
        $this->info = [
            'width' => $info[0],
            'height' => $info[1],
            'type' => image_type_to_extension($info[2], false),
            'mime' => $info['mime'],
        ];

        //destroy existing image
        empty($this->im) || imagedestroy($this->im);

        // open the image
        if ('gif' == $this->info['type']) {
            $class     = '\\Think\\Image\\Driver\\Gif';
            $this->gif = new $class($imgname);
            $this->im  = imagecreatefromstring($this->gif->image());
        } else {
            $fun      = "imagecreatefrom{$this->info['type']}";
            $this->im = $fun($imgname);
        }
        return $this;
    }

    /**
     * save image
     *
     * @param string $imgname image save name
     * @param string $type image type
     * @param boolean $interlace whether to set interlace for JPEG type images
     *
     * @return $this
     * @throws Exception
     */
    public function save($imgname, $type = null, $interlace = true)
    {
        if (empty($this->im)) {
            throw new Exception('No image resources can be saved');
        }

        //Automatically get the image type
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
        }

        // JPEG image set interlace
        if ('jpeg' == $type || 'jpg' == $type) {
            $type = 'jpeg';
            imageinterlace($this->im, $interlace);
        }

        // save the image
        if ('gif' == $type && !empty($this->gif)) {
            $this->gif->save($imgname);
        } else {
            $fun = "image{$type}";
            $fun($this->im, $imgname);
        }
        return $this;
    }

    /**
     * return image width
     * @return int image width
     * @throws Exception
     */
    public function width()
    {
        if (empty($this->im)) {
            throw new Exception('No image resource specified');
        }

        return $this->info['width'];
    }

    /**
     * return image height
     * @return int image height
     * @throws Exception
     */
    public function height()
    {
        if (empty($this->im)) {
            throw new Exception('No image resource specified');
        }

        return $this->info['height'];
    }

    /**
     * return image type
     * @return string image type
     * @throws Exception
     */
    public function type()
    {
        if (empty($this->im)) {
            throw new Exception('No image resource specified');
        }

        return $this->info['type'];
    }

    /**
     * Return image MIME type
     * @return string image MIME type
     * @throws Exception
     */
    public function mime()
    {
        if (empty($this->im)) {
            throw new Exception('No image resource specified');
        }

        return $this->info['mime'];
    }

    /**
     * Returns an array of image dimensions 0 - image width, 1 - image height
     * @return array image size
     * @throws Exception
     */
    public function size()
    {
        if (empty($this->im)) {
            throw new Exception('No image resource specified');
        }

        return [$this->info['width'], $this->info['height']];
    }

    /**
     * Crop image
     *
     * @param integer $w crop area width
     * @param integer $h crop area height
     * @param integer $x crop area x coordinate
     * @param integer $y The y coordinate of the clipping area
     * @param integer $width image save width
     * @param integer $height image save height
     *
     * @return $this
     * @throws Exception
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        if (empty($this->im)) {
            throw new Exception('No image resource that can be cropped');
        }

        //set save size
        empty($width) && $width = $w;
        empty($height) && $height = $h;

        do {
            //create new image
            $img = imagecreatetruecolor($width, $height);
            // adjust the default color
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);

            // crop
            imagecopyresampled($img, $this->im, 0, 0, $x, $y, $width, $height, $w, $h);
            imagedestroy($this->im); //Destroy the original image

            //set new image
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext());

        $this->info['width']  = $width;
        $this->info['height'] = $height;
        return $this;
    }

    /**
     * Generate thumbnails
     *
     * @param integer $width Thumbnail maximum width
     * @param integer $height Thumbnail maximum height
     * @param int $type Thumbnail crop type
     *
     * @return $this
     * @throws Exception
     */
    public function thumb($width, $height, $type = THINKIMAGE_THUMB_SCALING)
    {
        if (empty($this->im)) {
            throw new Exception('No image resource that can be abbreviated');
        }

        //Original image width and height
        $w = $this->info['width'];
        $h = $this->info['height'];

        /* Calculate the necessary parameters for thumbnail generation */
        switch ($type) {
            /* Equal scaling */
            case THINKIMAGE_THUMB_SCALING:
                //If the original image size is smaller than the thumbnail size, it will not be thumbnailed
                if ($w < $width && $h < $height) {
                    return false;
                }

                //Calculate the zoom ratio
                $scale = min($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $x = $y = 0;
                $width = $w * $scale;
                $height = $h * $scale;
                break;

            /* Center crop */
            case THINKIMAGE_THUMB_CENTER:
                //Calculate the zoom ratio
                $scale = max($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;

            /* top left corner crop */
            case THINKIMAGE_THUMB_NORTHWEST:
                //Calculate the zoom ratio
                $scale = max($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;

            /* lower right corner crop */
            case THINKIMAGE_THUMB_SOUTHEAST:
                //Calculate the zoom ratio
                $scale = max($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;

            /* padding */
            case THINKIMAGE_THUMB_FILLED:
                //Calculate the zoom ratio
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }

                //Set the coordinates and width and height of the thumbnail
                $neww = $w * $scale;
                $newh = $h * $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;

                do {
                    //create new image
                    $img = imagecreatetruecolor($width, $height);
                    // adjust the default color
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);

                   // crop
                    imagecopyresampled($img, $this->im, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
                    imagedestroy($this->im); //Destroy the original image
                    $this->im = $img;
                } while (!empty($this->gif) && $this->gifNext());

                $this->info['width'] = $width;
                $this->info['height'] = $height;
                return $this;

            /* fixed */
            case THINKIMAGE_THUMB_FIXED:
                $x = $y = 0;
                break;

            default:
                throw new Exception('Unsupported thumbnail crop type');
        }

        /* crop image */
        $this->crop($w, $h, $x, $y, $width, $height);
        return $this;
    }

    /**
     * Add watermark
     *
     * @param string $source watermark image path
     * @param int $locate watermark location
     *
     * @return $this
     * @throws Exception
     * @internal param int $alpha watermark transparency
     */
    public function water($source, $locate = THINKIMAGE_WATER_SOUTHEAST)
    {
        //Resource detection
        if (empty($this->im)) {
            throw new Exception('No image resource that can be watermarked');
        }

        if (!is_file($source)) {
            throw new Exception('watermark image does not exist');
        }

        //Get the watermark image information
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new Exception('Illegal watermark file');
        }

        //Create a watermark image resource
        $fun = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);

        //Set the color mixing mode of the watermark image
        imagealphablending($water, true);

        /* set the watermark position */
        switch ($locate) {
            /* bottom right watermark */
            case THINKIMAGE_WATER_SOUTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;

            /* bottom left watermark */
            case THINKIMAGE_WATER_SOUTHWEST:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;

            /* Top left watermark */
            case THINKIMAGE_WATER_NORTHWEST:
                $x = $y = 0;
                break;

            /* top right watermark */
            case THINKIMAGE_WATER_NORTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;

            /* Center the watermark */
            case THINKIMAGE_WATER_CENTER:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* Bottom center watermark */
            case THINKIMAGE_WATER_SOUTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;

            /* center right watermark */
            case THINKIMAGE_WATER_EAST:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* Top center watermark */
            case THINKIMAGE_WATER_NORTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;

            /* center left watermark */
            case THINKIMAGE_WATER_WEST:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            default:
                /* custom watermark coordinates */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new Exception('Unsupported watermark position type');
                }
        }

        do {
            //add watermark
            $src = imagecreatetruecolor($info[0], $info[1]);
            // adjust the default color
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);

            imagecopy($src, $this->im, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->im, $src, $x, $y, 0, 0, $info[0], $info[1], 100);

            //Destroy the zero-hour image resource
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());

        //Destroy the watermark resource
        imagedestroy($water);
        return $this;
    }

    /**
     * Image added text
     *
     * @param string $text added text
     * @param string $font font path
     * @param integer $size font size
     * @param string $color text color
     * @param int $locate text write location
     * @param integer $offset The offset of the text relative to the current position
     * @param integer $angle text angle
     *
     * @return $this
     * @throws Exception
     */
    public function text($text, $font, $size, $color = '#00000000',
        $locate = THINKIMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0) {
        //Resource detection
        if (empty($this->im)) {
            throw new Exception('No image resource that can be written to text');
        }

        if (!is_file($font)) {
            throw new Exception("non-existent font file: {$font}");
        }

        //get text information
        $info = imagettfbbox($size, $angle, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);

        /* Calculate the initial coordinates and size of the text */
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;

        /* set the text position */
        switch ($locate) {
            /* Bottom right text */
            case THINKIMAGE_WATER_SOUTHEAST:
                $x += $this->info['width'] - $w;
                $y += $this->info['height'] - $h;
                break;

            /* Bottom left text */
            case THINKIMAGE_WATER_SOUTHWEST:
                $y += $this->info['height'] - $h;
                break;

            /* Top left text */
            case THINKIMAGE_WATER_NORTHWEST:
                // The starting coordinate is the upper left corner coordinate, no need to adjust
                break;

            /* upper right corner text */
            case THINKIMAGE_WATER_NORTHEAST:
                $x += $this->info['width'] - $w;
                break;

            /* Center text */
            case THINKIMAGE_WATER_CENTER:
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;

           /* Bottom center text */
            case THINKIMAGE_WATER_SOUTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;

            /* Center right text */
            case THINKIMAGE_WATER_EAST:
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* Center text on top */
            case THINKIMAGE_WATER_NORTH:
                $x += ($this->info['width'] - $w) / 2;
                break;

            /* Center left text */
            case THINKIMAGE_WATER_WEST:
                $y += ($this->info['height'] - $h) / 2;
                break;

            default:
                /* custom text coordinates */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new Exception('Unsupported literal position type');
                }
        }

        /* set offset */
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }

        /* set color */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new Exception('wrong color value');
        }

        do {
            /* write text */
            $col = imagecolorallocatealpha($this->im, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->im, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
        return $this;
    }

    /* switch to the next frame of the GIF and save the current frame, used internally */
    private function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->im);
        $img = ob_get_clean();

        $this->gif->image($img);
        $next = $this->gif->nextImage();

        if ($next) {
            $this->im = imagecreatefromstring($next);
            return $next;
        } else {
            $this->im = imagecreatefromstring($this->gif->image());
            return false;
        }
    }

    /**
     * Destructor method for destroying image resources
     */
    public function __destruct()
    {
        empty($this->im) || imagedestroy($this->im);
    }
}
